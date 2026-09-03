<?php

use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Services\PageRenderer;
use Database\Seeders\CmsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed([RolesAndPermissionsSeeder::class, SettingsSeeder::class, CmsSeeder::class]);
});

it('renders the seeded home through the CMS block pipeline', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('Care, delivered.', false)
        ->assertSee('Corporate relocation, global mobility and hospitality services', false)
        ->assertSee('Talk to a consultant', false);
});

it('renders exactly one H1 on the home page', function (): void {
    $html = $this->get('/')->getContent();

    expect(preg_match_all('/<h1[\s>]/', $html))->toBe(1)
        ->and(substr_count($html, '<h2'))->toBeGreaterThanOrEqual(1);
});

it('emits the seeded meta title and description on the home page', function (): void {
    $html = $this->get('/')->getContent();

    expect($html)->toContain('<title>Corporate Relocation &amp; Global Mobility in India — Sewa Hospitality</title>')
        ->toContain('name="description"')
        ->toContain('rel="canonical"');
});

it('serves the about page and a legal page through their families', function (): void {
    $this->get('/about')->assertOk()->assertSee('A mobility partner, not a vendor list.', false);
    $this->get('/legal/privacy-policy')->assertOk()->assertSee('Privacy Policy');
    $this->get('/legal/no-such-page')->assertNotFound();
    $this->get('/p/no-such-landing')->assertNotFound();
});

it('never serves drafts or archived pages', function (): void {
    Page::query()->where('slug', 'about')->update(['status' => 'draft']);
    cache()->flush();

    $this->get('/about')->assertNotFound();
});

it('bumps the render cache version so edits go live immediately', function (): void {
    $first = $this->get('/')->getContent();

    // Model save (not bulk update) so the PageObserver bumps the version.
    $page = Page::query()->where('slug', 'home')->first();
    $page->blocks = [[
        'type' => 'hero',
        'data' => ['headline' => 'Freshly edited headline', 'sub' => 'x', 'ctas' => []],
    ]];
    $page->save();

    $second = $this->get('/')->getContent();

    expect($second)->toContain('Freshly edited headline')
        ->and($second)->not->toBe($first);
});

it('renders the seeded menus in the header and footer', function (): void {
    $html = $this->get('/')->getContent();

    expect($html)->toContain('aria-label="Primary"')
        ->toContain('href="/about"')
        ->toContain('href="/legal/privacy-policy"')
        ->toContain('+91 98732 55531');
});

it('renders the publish probe clean over the whole seed', function (): void {
    $renderer = app(PageRenderer::class);

    Page::query()->each(function (Page $page) use ($renderer): void {
        expect($renderer->probe($page))->toBe([]);
    });
});
