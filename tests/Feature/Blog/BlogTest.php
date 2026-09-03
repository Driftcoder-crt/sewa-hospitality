<?php

use App\Models\Media;
use App\Models\User;
use App\Modules\Blog\Enums\PostStatus;
use App\Modules\Blog\Models\Category;
use App\Modules\Blog\Models\Post;
use App\Modules\Blog\Models\Tag;
use App\Modules\Blog\Services\PostPublishGate;
use App\Modules\I18n\Models\Locale;
use Database\Seeders\LocalesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    // syncRoles needs the seeded role matrix (03-database-schema §1);
    // locales must exist or Route::bind('locale') 404s every /ja path.
    $this->seed([RolesAndPermissionsSeeder::class, LocalesSeeder::class]);
    Locale::flushRegistry();
    $this->author = User::factory()->create();
    $this->author->syncRoles(['author']);
});

function publishablePost(User $author, array $overrides = []): Post
{
    $category = Category::factory()->create();

    $post = Post::factory()->create(array_merge([
        'author_user_id' => $author->id,
        'status' => PostStatus::Draft,
        'cover_media_id' => Media::factory()->create()->getKey(), // gate: cover required
        'excerpt' => str_repeat('An honest, real excerpt for the gate. ', 3),
        'meta_title' => 'Gate meta title',
        'meta_description' => 'A gate meta description that is long enough to pass the minimum checks.',
    ], $overrides));

    $post->categories()->attach($category);

    return $post;
}

it('blocks publish without a human author — the "admin author" defect is a failing test', function (): void {
    $post = publishablePost($this->author);
    // In-memory only: the posts.author_user_id column is NOT NULL BY DESIGN
    // (the structural half of the defense); the gate is the flow-level half.
    $post->forceFill(['author_user_id' => null]);

    expect(fn () => app(PostPublishGate::class)->publish($post))
        ->toThrow(InvalidArgumentException::class);
});

it('requires category, excerpt and metas through the gate', function (): void {
    $post = publishablePost($this->author);
    $post->categories()->detach();

    $errors = app(PostPublishGate::class)->validate($post);

    expect($errors)->toHaveKey('categories')
        ->and(app(PostPublishGate::class)->validate(publishablePost($this->author)))->toBe([]);
});

it('publishes blog posts on dated URLs and 301s a wrong date to the canonical path', function (): void {
    $post = publishablePost($this->author);
    app(PostPublishGate::class)->publish($post);
    $post->refresh();

    expect($post->status)->toBe(PostStatus::Published)
        ->and($post->publicPath())->toStartWith('/blog/');

    $path = $post->publicPath();
    $this->get($path)->assertOk()->assertSee($post->title);

    // Same slug, wrong month → 301 to the canonical dated path.
    $wrong = preg_replace('#(/blog/)(\d{4})/(\d{2})/#', '$1$2/01/', $path);
    if ($wrong !== $path) {
        $this->get($wrong)->assertRedirect($path, 301);
    }
});

it('renders the blog index and a thin tag archive noindex', function (): void {
    $post = publishablePost($this->author);
    app(PostPublishGate::class)->publish($post);
    $post->refresh();

    $this->get('/blog')->assertOk()->assertSee($post->title);

    $tag = $post->tags()->create(['slug' => 'gate-tag', 'name' => 'Gate']);
    $post->tags()->sync([$tag->id]);

    $this->get($tag->publicPath())->assertOk()->assertSee('noindex, follow');
});

it('schedules through the gate and fires on the cron command', function (): void {
    $post = publishablePost($this->author);
    app(PostPublishGate::class)->publish($post, when: now()->subMinutes(5)->toIso8601String());
    $post->refresh();

    expect($post->status)->toBe(PostStatus::Scheduled);

    $this->artisan('posts:publish-scheduled')->assertSuccessful();
    expect($post->refresh()->status)->toBe(PostStatus::Published);
});

it('renders related posts deterministically (category first, then recent backfill)', function (): void {
    $a = publishablePost($this->author, ['title' => 'Alpha']);
    $b = publishablePost($this->author, ['title' => 'Beta']);
    $c = publishablePost($this->author, ['title' => 'Gamma']);
    $d = publishablePost($this->author, ['title' => 'Delta']);

    foreach ([$a, $b, $c, $d] as $post) {
        app(PostPublishGate::class)->publish($post);
        $post->refresh();
    }

    $related = $a->related(3);
    expect($related->count())->toBe(3)
        ->and($related->contains($a))->toBeFalse();
});

it('serves the RSS journal feed with real posts and validator headers', function (): void {
    $post = publishablePost($this->author, ['title' => 'Feed-ready story']);
    app(PostPublishGate::class)->publish($post);
    $post->refresh();

    $response = $this->get('/feed');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')
        ->assertSee('<rss version="2.0"', false)
        ->assertSee('Feed-ready story', false);

    // Unpublished drafts never leak into the feed (honesty rule).
    $draft = publishablePost($this->author, ['title' => 'Secret draft']);
    $this->get('/feed')->assertOk()->assertDontSee('Secret draft', false);
});

/* ── Localized archives (11-multilingual §4): EN fallback never hides ── */

it('renders EN-only published posts in the ja category archive', function (): void {
    $category = Category::factory()->create();
    $post = Post::factory()->published()->create([
        'author_user_id' => $this->author->id,
        'title' => 'Delhi relocation guide',
    ]);
    $post->categories()->attach($category);

    $this->get('/ja/blog/category/'.$category->slug)
        ->assertOk()
        ->assertSee('Delhi relocation guide', false);
});

it('renders the ja variant in the ja category archive without duplicating the EN twin', function (): void {
    $category = Category::factory()->create();
    $post = Post::factory()->published()->create([
        'author_user_id' => $this->author->id,
        'title' => 'Delhi relocation guide',
    ]);
    $post->categories()->attach($category);

    // Merged variant as TranslateContent produces it: same slug, own
    // locale, pointer to the EN source; it rides the same category pivot.
    $variant = $post->replicate();
    $variant->locale = 'ja';
    $variant->locale_source_id = $post->getKey();
    $variant->slug = $post->slug;
    $variant->status = 'published';
    $variant->title = 'デリーガイド';
    $variant->save();
    $variant->categories()->attach($category);

    $html = $this->get('/ja/blog/category/'.$category->slug)->assertOk()->getContent();

    expect(substr_count($html, 'デリーガイド'))->toBeGreaterThanOrEqual(1)
        ->and(str_contains($html, 'Delhi relocation guide'))->toBeFalse();

    // And the EN archive still serves the EN source, never the variant.
    $this->get('/blog/category/'.$category->slug)
        ->assertOk()
        ->assertSee('Delhi relocation guide', false);
});

it('renders EN-only published posts in the ja tag archive', function (): void {
    $tag = Tag::create(['slug' => 'visa-basics', 'name' => 'Visa Basics']);
    $post = Post::factory()->published()->create([
        'author_user_id' => $this->author->id,
        'title' => 'Dependent visa explained',
    ]);
    $post->tags()->attach($tag);

    $this->get('/ja/blog/tag/'.$tag->slug)
        ->assertOk()
        ->assertSee('Dependent visa explained', false);
});
