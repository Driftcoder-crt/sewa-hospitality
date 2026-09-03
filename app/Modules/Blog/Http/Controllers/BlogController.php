<?php

namespace App\Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Blog\Enums\PostType;
use App\Modules\Blog\Models\Category;
use App\Modules\Blog\Models\Post;
use App\Modules\Blog\Models\Tag;
use App\Modules\I18n\Services\ContentVariants;
use App\Modules\I18n\Services\LocaleUrls;
use App\Support\Cms\Identity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public blog/news surface (07-blog-news §3): /blog index + archives +
 * dated post URLs + /news. Thin archives (< 3 posts) render noindex,
 * follow (§5); tag archives with 1 post noindex; pagination canonicals
 * resolve to page 1.
 */
class BlogController extends Controller
{
    public function index(): View
    {
        $posts = ContentVariants::localizedList(Post::query()->published()->type(PostType::Blog))
            ->with(['author.authorProfile', 'cover', 'tags'])
            ->orderByDesc('published_at')
            ->paginate(9);

        return view('blog.index', [
            'posts' => $posts,
            'recent' => $this->recent(),
            'categories' => $this->categoriesWithCounts(),
        ]);
    }

    public function category(string $slug): View
    {
        $category = Category::query()->where('slug', $slug)->first();

        if (! $category) {
            throw new NotFoundHttpException('Category not found.');
        }

        // Localized archive (11-multilingual §4): ja variants first, EN
        // sources render where no variant exists — fallback never hides.
        $posts = ContentVariants::localizedList($category->posts()->published())
            ->with(['author.authorProfile', 'cover', 'tags'])
            ->orderByDesc('published_at')
            ->paginate(9);

        return view('blog.category', [
            'category' => $category,
            'posts' => $posts,
            'recent' => $this->recent(),
            'categories' => $this->categoriesWithCounts(),
            'thin' => $posts->total() < 3, // archives noindex,follow when thin (§5)
        ]);
    }

    public function tag(string $slug): View
    {
        $tag = Tag::query()->where('slug', $slug)->first();

        if (! $tag) {
            throw new NotFoundHttpException('Tag not found.');
        }

        // Localized archive: same fallback doctrine as the category page.
        $posts = ContentVariants::localizedList($tag->posts()->published())
            ->with(['author.authorProfile', 'cover'])
            ->orderByDesc('published_at')
            ->paginate(9);

        return view('blog.tag', [
            'tag' => $tag,
            'posts' => $posts,
            'thin' => $posts->total() < 2, // tag archives noindex with 1 post (§5)
        ]);
    }

    /** /blog/{yyyy}/{mm}/{slug} — dated permalink. */
    public function show(string $year, string $month, string $slug): View
    {
        $post = $this->findPublished(PostType::Blog, $slug, (int) $year, (int) $month);

        return $this->renderPost($post);
    }

    /** /news/{slug}. */
    public function newsShow(string $slug): View
    {
        $post = $this->findPublished(PostType::News, $slug);

        return $this->renderPost($post);
    }

    /**
     * /feed — RSS 2.0 of recent published posts (AEO surface, 07 doc §6):
     * full honesty — real dates, real authors, absolute URLs, no fake
     * freshness. Cached 10 minutes; validator headers for conditional GET.
     */
    public function feed(): Response
    {
        // Localized feed (same fallback doctrine as the archives —
        // 11-multilingual §4): ja variants first, EN sources render where
        // no variant exists. A ja reader must never see an empty feed.
        $posts = ContentVariants::localizedList(Post::query()->published())
            ->with(['author:id,name'])
            ->orderByDesc('published_at')
            ->limit(30)
            ->get();

        $base = rtrim(config('app.url', 'https://sewahospitality.com'), '/');
        $build = (string) $posts->min('updated_at');

        $identity = Identity::current();
        $brand = (string) ($identity['brand'] ?? 'Sewa Hospitality');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'."\n<channel>\n";
        $xml .= '  <title>'.e($brand.' — Journal').'</title>'."\n";
        $xml .= '  <link>'.$base.'/blog</link>'."\n";
        $xml .= '  <description>'.e('Relocation guides, immigration explainers and city notes from the Sewa Hospitality team.').'</description>'."\n";
        $xml .= '  <language>'.e(str_replace('_', '-', app()->getLocale())).'</language>'."\n";
        $xml .= '  <lastBuildDate>'.e(optional($posts->first()?->published_at)->toRfc2822String() ?? now()->toRfc2822String()).'</lastBuildDate>'."\n";
        $xml .= '  <atom:link href="'.$base.'/feed" rel="self" type="application/rss+xml" />'."\n";

        foreach ($posts as $post) {
            $xml .= "  <item>\n";
            $xml .= '    <title>'.e($post->title).'</title>'."\n";
            $xml .= '    <link>'.e($base.$post->publicPath()).'</link>'."\n";
            $xml .= '    <guid isPermaLink="true">'.e($base.$post->publicPath()).'</guid>'."\n";
            $xml .= '    <pubDate>'.e(optional($post->published_at)->toRfc2822String() ?? now()->toRfc2822String()).'</pubDate>'."\n";
            if ($post->author) {
                $xml .= '    <dc:creator xmlns:dc="http://purl.org/dc/elements/1.1/">'.e($post->author->name).'</dc:creator>'."\n";
            }
            $xml .= '    <description>'.e(Str::limit(strip_tags((string) $post->excerpt), 300)).'</description>'."\n";
            foreach ($post->categories as $category) {
                $xml .= '    <category>'.e($category->name).'</category>'."\n";
            }
            $xml .= "  </item>\n";
        }
        $xml .= "</channel>\n</rss>\n";

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=600',
            'ETag' => '"'.sha1($build.$posts->count()).'"',
        ]);
    }

    private function findPublished(PostType $type, string $slug, ?int $year = null, ?int $month = null): Post
    {
        $post = ContentVariants::firstInLocale(
            Post::query()->published()->type($type)->where('slug', $slug),
        );

        // Dated blog URLs: a wrong month/year on a real slug 301s to the
        // canonical path (dates preserved per 07 doc §2). Locale-prefix
        // aware: compare the prefix-stripped path and re-prefix the
        // redirect target so /ja/blog/2026/03/x never escapes its locale.
        if ($post && $type === PostType::Blog) {
            $expected = (string) $post->publicPath();
            $actual = '/'.LocaleUrls::stripPrefix((string) request()->path());

            if ($expected !== $actual) {
                $localized = app()->getLocale() === 'en'
                    ? $expected
                    : '/'.app()->getLocale().$expected;

                throw new HttpResponseException(
                    redirect()->to($localized, 301),
                );
            }
        }

        if (! $post) {
            throw new NotFoundHttpException('Post not found.');
        }

        return $post->load(['author.authorProfile', 'cover', 'categories', 'tags']);
    }

    private function renderPost(Post $post): View
    {
        return view('blog.show', [
            'post' => $post,
            'related' => $post->related(3),
        ]);
    }

    private function recent(int $count = 5): Collection
    {
        return ContentVariants::localizedList(Post::query()->published())
            ->with(['author', 'cover'])
            ->orderByDesc('published_at')
            ->limit($count)
            ->get();
    }

    /** @return Collection<int, array{category: Category, count: int}> */
    private function categoriesWithCounts(): Collection
    {
        return Category::query()
            ->orderBy('sort')
            ->get()
            ->map(fn (Category $category): array => [
                'category' => $category,
                'count' => $category->publishedCount(),
            ])
            ->filter(fn (array $row): bool => $row['count'] > 0);
    }
}
