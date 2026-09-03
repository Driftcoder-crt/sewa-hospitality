<?php

namespace App\Modules\Cms\Services;

use App\Modules\Blog\Models\Category;
use App\Modules\Blog\Models\Post;
use App\Modules\Careers\Models\JobPosting;
use App\Modules\Cities\Models\City;
use App\Modules\Cities\Models\HousingUnit;
use App\Modules\Cms\Models\Page;
use App\Modules\I18n\Models\Locale;
use App\Modules\I18n\Services\ContentVariants;
use App\Modules\I18n\Services\LocaleUrls;
use App\Modules\Services\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

/**
 * Sitemap generator (06-content-seo/02-seo-technical.md §3): the
 * sitemap_index + child files, written to public/ on nightly schedule
 * (02:00) AND on publish events. Rules: lastmod = real modified time;
 * excludes noindex, search, portal, admin, filters.
 *
 * I18n (11-multilingual §5): ONE sitemap with hreflang alternates per
 * URL — every URL carries <xhtml:link> for each published locale
 * variant + x-default → en, and published non-EN variants are listed
 * as their own <url> rows. hreflang always matches reality.
 */
class SitemapGenerator
{
    /** @return array<string, string> filename => xml */
    public function generate(): array
    {
        $base = rtrim(config('app.url', 'https://sewahospitality.com'), '/');
        $files = [];

        // ---- sitemap-pages.xml --------------------------------------
        $pages = Page::query()->published()->where('noindex', false)->get();

        // The fixed home/about/contact rows follow the SAME published +
        // noindex rules as the loop below — a noindexed /about must
        // leave the sitemap, not just the custom pages.
        $fixed = ['home' => '/', 'about' => '/about', 'contact' => '/contact'];
        $entries = [];
        foreach ($fixed as $slug => $path) {
            if (($page = $pages->firstWhere('slug', $slug)) !== null) {
                $entries[] = ['loc' => $base.$path, 'lastmod' => $page->updated_at, 'alternates' => self::prefixAlternates($path)];
            }
        }
        foreach ($pages as $page) {
            if (in_array($page->slug, ['home', 'about', 'contact'], true)) {
                continue;
            }
            $entries[] = ['loc' => $base.$page->publicPath(), 'lastmod' => $page->updated_at, 'alternates' => ContentVariants::alternatesFor($page)];
        }
        foreach (['/services', '/cities', '/housing', '/housing/verified', '/careers'] as $staticPath) {
            $entries[] = ['loc' => $base.$staticPath, 'lastmod' => now(), 'alternates' => self::prefixAlternates($staticPath)];
        }
        // Open job postings are indexable surfaces (06-hr §3): closed
        // roles carry noindex/follow — excluded here.
        JobPosting::query()
            ->open()
            ->get()
            ->each(function ($posting) use (&$entries, $base): void {
                $entries[] = ['loc' => $base.$posting->publicPath(), 'lastmod' => $posting->updated_at, 'alternates' => self::prefixAlternates($posting->publicPath())];
            });
        $files['sitemap-pages.xml'] = $this->urlset($entries);

        // ---- sitemap-services.xml -----------------------------------
        // publicPath() resolves ->parent for leaf URLs — eager-load it so
        // the non-production preventLazyLoading guard never trips here.
        $entries = Service::query()
            ->published()
            ->where('noindex', false)
            ->with('parent')
            ->get()
            ->map(fn (Service $service): array => [
                'loc' => $base.$service->publicPath(),
                'lastmod' => $service->updated_at,
                'alternates' => ContentVariants::alternatesFor($service),
            ])->all();
        $files['sitemap-services.xml'] = $this->urlset($entries);

        // ---- sitemap-cities.xml --------------------------------------
        $entries = City::query()
            ->published()
            ->where('noindex', false)
            ->get()
            ->map(fn (City $city): array => [
                'loc' => $base.$city->publicPath(),
                'lastmod' => $city->updated_at,
                'alternates' => ContentVariants::alternatesFor($city),
            ])->all();
        $files['sitemap-cities.xml'] = $this->urlset($entries);

        // ---- sitemap-housing.xml -------------------------------------
        // Housing inventory is EN-only at launch — alternates stay the
        // truthful prefix map (every locale serves the same URL).
        $entries = HousingUnit::query()
            ->where('published', true)
            ->whereNotNull('verified_at')
            ->with('city')
            ->get()
            ->filter(fn (HousingUnit $unit): bool => $unit->city !== null)
            ->map(fn (HousingUnit $unit): array => [
                'loc' => $base.$unit->publicPath(),
                'lastmod' => $unit->updated_at,
                'alternates' => self::prefixAlternates($unit->publicPath()),
            ])->all();
        $files['sitemap-housing.xml'] = $this->urlset($entries);

        // ---- sitemap-posts.xml / sitemap-categories.xml (M4) ---------
        // Dated permalinks only for published, indexable posts; news
        // permalinks live on /news/{slug}. Thin archives (categories
        // with < 3 published posts) are noindex,follow — excluded.
        $files['sitemap-posts.xml'] = $this->urlset(
            Post::query()
                ->published()
                ->where('noindex', false)
                ->get()
                ->map(fn (Post $post): array => [
                    'loc' => $base.$post->publicPath(),
                    'lastmod' => $post->updated_at,
                    'alternates' => ContentVariants::alternatesFor($post),
                ])->all()
        );
        $files['sitemap-categories.xml'] = $this->urlset(
            Category::query()
                ->withCount('posts')
                ->get()
                ->filter(fn (Category $category): bool => $category->posts_count >= 3)
                ->map(fn (Category $category): array => [
                    'loc' => $base.$category->publicPath(),
                    'lastmod' => $category->updated_at,
                    'alternates' => self::prefixAlternates($category->publicPath()),
                ])->all()
        );

        // ---- sitemap_index.xml ----------------------------------------
        $index = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach (array_keys($files) as $filename) {
            $index .= '  <sitemap><loc>'.$base.'/'.$filename."</loc></sitemap>\n";
        }
        $index .= '</sitemapindex>';
        $files['sitemap_index.xml'] = $index;

        return $files;
    }

    /** Write all files to public/ and return the list written. */
    public function write(): array
    {
        $written = [];
        foreach ($this->generate() as $filename => $xml) {
            File::put(public_path($filename), $xml);
            $written[] = $filename;
        }

        // /llms.txt (06-content-seo/05-aeo-llm-presence §2): curated
        // Markdown index maintained by the CMS — regenerated with every
        // sitemap run so the AEO surface never drifts.
        File::put(public_path('llms.txt'), $this->generateLlms());

        return $written;
    }

    /**
     * Curated Markdown index for LLM crawlers: what Sewa is + canonical
     * links to the best answers (services, cities, housing standards,
     * guides). Descriptions come from the entity metas — never retyped.
     */
    public function generateLlms(): string
    {
        $base = rtrim(config('app.url', 'https://sewahospitality.com'), '/');

        $md = "# Sewa Hospitality — Care, delivered.\n\n";
        $md .= 'Corporate relocation, global mobility and hospitality services across India: ';
        $md .= 'immigration & FRRO assistance, serviced corporate housing, settling-in, home search, ';
        $md .= "schooling and moving services for international teams and families.\n\n";
        $md .= "Headquarters: DT Mega Mall, DLF Phase 1, Gurugram. Phone: +91 98732 55531.\n\n";

        $md .= "## Services\n\n";
        $services = Service::query()
            ->published()
            ->where('locale', Locale::DEFAULT)
            // publicPath() resolves ->parent for leaf URLs — same
            // eager-load as the sitemap-services query (the
            // non-production preventLazyLoading guard otherwise trips).
            ->with('parent')
            ->orderBy('sort')
            ->get();

        foreach ($services as $service) {
            $desc = (string) ($service->short_desc ?: $service->meta_description);
            $md .= '- ['.$service->name.']('.$base.$service->publicPath().')'.($desc !== '' ? ': '.str_replace("\n", ' ', $desc) : '')."\n";
        }

        $md .= "\n## Cities we serve\n\n";
        $cities = City::query()
            ->published()
            ->where('locale', Locale::DEFAULT)
            ->orderByDesc('is_hub')
            ->orderBy('name')
            ->take(12)
            ->get();

        foreach ($cities as $city) {
            $md .= '- [Relocating to '.$city->name.']('.$base.$city->publicPath().")\n";
        }

        $md .= "\n## Housing\n\n";
        $md .= '- [The Sewa Verified standard]('.$base."/housing/verified): the published inspection checklist every listed home passes.\n";
        $md .= '- [Browse verified homes]('.$base."/housing)\n";

        $md .= "\n## Guides\n\n";
        $posts = Post::query()
            ->published()
            ->where('locale', Locale::DEFAULT)
            ->where('noindex', false)
            ->orderByDesc('published_at')
            ->take(10)
            ->get();

        foreach ($posts as $post) {
            $md .= '- ['.$post->title.']('.$base.$post->publicPath().")\n";
        }

        $md .= "\n## Company\n\n";
        $md .= '- [About Sewa]('.$base."/about)\n";
        $md .= '- [Careers]('.$base."/careers)\n";
        $md .= '- [Community programme]('.$base."/csr)\n";
        $md .= '- [Contact]('.$base."/contact)\n";

        return $md;
    }

    /**
     * @param  array<array{loc: string, lastmod: Carbon|null, alternates?: array<string, string>}>  $entries
     */
    private function urlset(array $entries): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";

        foreach ($entries as $entry) {
            $lastmod = $entry['lastmod']?->toIso8601String() ?? now()->toIso8601String();
            $xml .= '  <url><loc>'.e($entry['loc']).'</loc><lastmod>'.$lastmod.'</lastmod>';

            foreach ($entry['alternates'] ?? [] as $lang => $href) {
                $xml .= '<xhtml:link rel="alternate" hreflang="'.e($lang).'" href="'.e($this->absolute($href)).'"/>';
            }

            $xml .= "</url>\n";
        }

        return $xml.'</urlset>';
    }

    /** Every enabled locale serves the same path — truthful fallback alternates. */
    private static function prefixAlternates(string $path): array
    {
        $map = [];

        foreach (Locale::enabledCodes() as $code) {
            $map[$code] = LocaleUrls::localized($code, $path);
        }

        $map['x-default'] = $map[Locale::DEFAULT] ?? $path;

        return $map;
    }

    private function absolute(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim(config('app.url', 'https://sewahospitality.com'), '/').$path;
    }
}
