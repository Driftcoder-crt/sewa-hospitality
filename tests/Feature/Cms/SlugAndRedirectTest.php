<?php

use App\Modules\Cms\Enums\PageStatus;
use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Models\Redirect;
use App\Modules\Cms\Services\RedirectService;
use Database\Seeders\CmsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed([RolesAndPermissionsSeeder::class, SettingsSeeder::class, CmsSeeder::class]);
});

it('normalizes redirect sources to one canonical key', function (): void {
    expect(RedirectService::normalize('Old-Page/'))->toBe('/old-page')
        ->and(RedirectService::normalize('about'))->toBe('/about')
        ->and(RedirectService::normalize('https://example.com/A/?x=1'))->toBe('/a');
});

it('serves a 301 and counts the hit on the fallback route', function (): void {
    $redirect = Redirect::query()->create([
        'from' => '/legacy-path',
        'to' => '/about',
        'code' => 301,
        'active' => true,
    ]);

    $this->get('/legacy-path')
        ->assertRedirect('/about', 301);

    expect($redirect->fresh()->hits)->toBe(1);

    // Inactive rules stop serving immediately (map flushed on save).
    $redirect->update(['active' => false]);
    $this->get('/legacy-path')->assertNotFound();
});

it('moves published slugs into 301s without collision drama', function (): void {
    $page = Page::query()->create([
        'title' => 'Campaign',
        'slug' => 'summer-offer',
        'type' => 'landing',
        'status' => PageStatus::Published->value,
        'published_at' => now(),
        'meta_title' => 'Summer offer',
        'meta_description' => 'Seasonal program page.',
        'blocks' => [['type' => 'hero', 'data' => ['headline' => 'Offer', 'ctas' => []]]],
    ]);

    // Slug move + redirect offer honored (PageEditor.handleSlugMove path).
    $page->update(['slug' => 'winter-offer']);
    Redirect::query()->firstOrCreate(['from' => 'summer-offer'], [
        'to' => '/p/winter-offer', 'code' => 301, 'note' => 'slug move', 'active' => true,
    ]);

    $this->get('/p/summer-offer')->assertNotFound(); // old landing path is gone…
    $this->get('/summer-offer')->assertRedirect('/p/winter-offer', 301); // …the 301 catches it
    $this->get('/p/winter-offer')->assertOk();
});

it('rejects reserved and colliding slugs at the model boundary', function (): void {
    expect(Page::isReservedSlug('admin'))->toBeTrue()
        ->and(Page::isReservedSlug('blog'))->toBeTrue()
        ->and(Page::isReservedSlug('anything-goes'))->toBeFalse();

    Page::query()->create([
        'title' => 'One', 'slug' => 'unique-check', 'status' => PageStatus::Draft->value,
        'blocks' => [],
    ]);

    expect(fn (): int => Page::query()->create([
        'title' => 'Two', 'slug' => 'unique-check', 'status' => PageStatus::Draft->value,
        'blocks' => [],
    ])->id)->toThrow(QueryException::class);
});

it('never dead-ends: the 404 carries navigation and contact', function (): void {
    $this->get('/definitely-not-a-page')
        ->assertNotFound()
        ->assertSee('This page has moved on.', false)
        ->assertSee('Back to home', false);
});
