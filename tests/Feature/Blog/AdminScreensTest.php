<?php

use App\Models\Media;
use App\Models\User;
use App\Modules\Blog\Enums\PostStatus;
use App\Modules\Blog\Livewire\PostEditor;
use App\Modules\Blog\Livewire\PostsTable;
use App\Modules\Blog\Models\Category;
use App\Modules\Blog\Models\Post;
use App\Modules\Csr\Livewire\CsrManager;
use App\Modules\Csr\Models\CsrStory;
use App\Modules\Csr\Models\NgoPartner;
use App\Modules\Testimonials\Livewire\TestimonialsManager;
use App\Modules\Testimonials\Models\Testimonial;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed([RolesAndPermissionsSeeder::class]);
});

/* ── Posts table + review workflow ─────────────────────────────────── */

it('lets an editor open the posts table and see the workflow controls', function (): void {
    $editor = User::factory()->create();
    $editor->syncRoles(['editor']);
    test()->actingAs($editor, 'web');

    Livewire::test(PostsTable::class)->assertOk();
});

it('scopes the posts table to authors who cannot review (four-eyes)', function (): void {
    $author = User::factory()->create();
    $author->syncRoles(['author']);
    test()->actingAs($author, 'web');

    $mine = Post::factory()->create(['author_user_id' => $author->getKey()]);
    $foreign = Post::factory()->create(); // different auto-created author

    $component = Livewire::test(PostsTable::class);
    $ids = $component->viewData('posts')->getCollection()->pluck('id');

    expect($ids)->toContain($mine->getKey())
        ->not->toContain($foreign->getKey());
});

it('enforces four-eyes at the policy level: authors cannot approve or publish', function (): void {
    $author = User::factory()->create();
    $author->syncRoles(['author']);

    expect($author->can('review', Post::class))->toBeFalse()
        ->and($author->can('publish', Post::factory()->create()))->toBeFalse();

    $editor = User::factory()->create();
    $editor->syncRoles(['editor']);

    expect($editor->can('review', Post::class))->toBeTrue()
        ->and($editor->can('publish', Post::factory()->create()))->toBeTrue();
});

it('moves a post through submit → approve → publish from the table', function (): void {
    $editor = User::factory()->create();
    $editor->syncRoles(['editor']);
    test()->actingAs($editor, 'web');

    $author = User::factory()->create();
    $category = Category::factory()->create();
    $post = Post::factory()->create([
        'author_user_id' => $author->getKey(),
        'status' => PostStatus::Draft,
        'cover_media_id' => Media::factory()->create()->getKey(), // gate: cover required
        'excerpt' => str_repeat('An honest, real excerpt for the gate. ', 3),
        'meta_title' => 'Workflow meta title',
        'meta_description' => 'A workflow meta description long enough to pass minimum checks.',
    ]);
    $post->categories()->attach($category);

    Livewire::test(PostsTable::class)
        ->call('submitForReview', $post->getKey())
        ->assertHasNoErrors();

    $post->refresh();
    expect($post->status)->toBe(PostStatus::Review);

    Livewire::test(PostsTable::class)
        ->call('approve', $post->getKey())
        ->assertHasNoErrors();

    Livewire::test(PostsTable::class)
        ->call('publish', $post->getKey())
        ->assertHasNoErrors();

    $post->refresh();
    expect($post->status)->toBe(PostStatus::Published)
        ->and($post->approved_by_user_id)->not->toBeNull();
});

it('opens the post editor for editors', function (): void {
    $editor = User::factory()->create();
    $editor->syncRoles(['editor']);
    test()->actingAs($editor, 'web');

    $post = Post::factory()->create();

    Livewire::test(PostEditor::class, ['post' => $post])
        ->assertOk()
        ->assertSet('title', $post->title);
});

/* ── Testimonials manager ──────────────────────────────────────────── */

it('applies the honesty rule: a testimonial without body or rating cannot publish', function (): void {
    $editor = User::factory()->create();
    $editor->syncRoles(['editor']);
    test()->actingAs($editor, 'web');

    $testimonial = Testimonial::factory()->create([
        'body' => '',
        'status' => 'pending',
        'published_at' => null,
    ]);

    Livewire::test(TestimonialsManager::class)
        ->call('publish', $testimonial->getKey());

    $testimonial->refresh();
    expect($testimonial->status->value)->toBe('pending');
});

it('publishes a complete testimonial and archives it back', function (): void {
    $editor = User::factory()->create();
    $editor->syncRoles(['editor']);
    test()->actingAs($editor, 'web');

    $testimonial = Testimonial::factory()->create([
        'body' => 'The lease signing was handled in two days.',
        'rating' => 5,
        'status' => 'pending',
        'published_at' => null,
    ]);

    Livewire::test(TestimonialsManager::class)
        ->call('publish', $testimonial->getKey());

    $testimonial->refresh();
    expect($testimonial->status->value)->toBe('published')
        ->and($testimonial->published_at)->not->toBeNull();

    Livewire::test(TestimonialsManager::class)
        ->call('archive', $testimonial->getKey());

    $testimonial->refresh();
    expect($testimonial->status->value)->toBe('archived');
});

it('toggles named-display consent (privacy gate on the rendered name)', function (): void {
    $editor = User::factory()->create();
    $editor->syncRoles(['editor']);
    test()->actingAs($editor, 'web');

    $testimonial = Testimonial::factory()->create([
        'client_name' => 'Ananya Sharma',
        'consent_named' => false,
    ]);

    expect($testimonial->displayName())->not->toContain('Sharma');

    Livewire::test(TestimonialsManager::class)
        ->call('toggleConsent', $testimonial->getKey());

    $testimonial->refresh();
    expect($testimonial->consent_named)->toBeTrue()
        ->and($testimonial->displayName())->toContain('Sharma');
});

/* ── CSR manager ───────────────────────────────────────────────────── */

it('requires the claim trio: a claim without as-of and source fails validation', function (): void {
    $editor = User::factory()->create();
    $editor->syncRoles(['editor']);
    test()->actingAs($editor, 'web');

    Livewire::test(CsrManager::class)
        ->set('tab', 'partners')
        ->set('pName', 'Hope Works')
        ->set('pSlug', 'hope-works')
        ->set('pClaim', '600 women trained')
        // no claim_as_of / claim_source
        ->call('createPartner')
        ->assertHasErrors(['pClaimAsOf', 'pClaimSource']);

    expect(NgoPartner::query()->where('slug', 'hope-works')->exists())->toBeFalse();
});

it('creates a partner with the full claims ledger and a draft story', function (): void {
    $editor = User::factory()->create();
    $editor->syncRoles(['editor']);
    test()->actingAs($editor, 'web');

    Livewire::test(CsrManager::class)
        ->set('tab', 'partners')
        ->set('pName', 'Hope Works')
        ->set('pSlug', 'hope-works')
        ->set('pClaim', '600 women trained')
        ->set('pClaimAsOf', 'Aug 2026')
        ->set('pClaimSource', 'Partner letter #12')
        ->call('createPartner')
        ->assertHasNoErrors();

    $partner = NgoPartner::query()->where('slug', 'hope-works')->first();
    expect($partner)->not->toBeNull()
        ->and($partner->claim_as_of)->toBe('Aug 2026');

    Livewire::test(CsrManager::class)
        ->set('tab', 'stories')
        ->set('sTitle', 'A new training cohort')
        ->set('sPartnerId', $partner->getKey())
        ->set('sBody', '<h2>Cohort two</h2><p>Sixty women started the second cohort.</p>')
        ->call('createStory')
        ->assertHasNoErrors();

    $story = CsrStory::query()->where('slug', 'a-new-training-cohort')->first();
    expect($story)->not->toBeNull()
        ->and($story->status)->toBe('draft')
        ->and($story->ngo_partner_id)->toBe($partner->getKey());
});
