<?php

use App\Modules\Cities\Events\CityPublished;
use App\Modules\Cities\Models\City;
use App\Modules\Cms\Enums\PageStatus;
use App\Modules\Cms\Events\PagePublished;
use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Services\SitemapGenerator;
use App\Modules\Search\Models\SearchQuery;
use App\Modules\Search\Services\SearchService;
use App\Modules\Services\Events\ServicePublished;
use App\Support\Seo\RegenerateSitemap;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\CmsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicesSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed([RolesAndPermissionsSeeder::class, SettingsSeeder::class, ServicesSeeder::class, CitiesSeeder::class, CmsSeeder::class]);
});

it('groups site search results across modules', function (): void {
    // 'housing' legitimately hits BOTH groups per the indexed-fields
    // contract (08-search §1: services name/short_desc/intro; cities
    // name/state/description — Pune's description carries "corporate
    // housing"). 'relocation' matches only services — the spec's own
    // example query ("relocation pune") reaches cities via "pune".
    $results = app(SearchService::class)->search('housing');

    expect($results['total'])->toBeGreaterThan(0)
        ->and($results['groups']['services']['count'])->toBeGreaterThan(0)
        ->and($results['groups']['cities']['count'])->toBeGreaterThan(0);
});

it('logs searches anonymously and flags zero results', function (): void {
    $results = app(SearchService::class)->search('zzz-no-match-xyz');

    expect($results['total'])->toBe(0);

    $row = SearchQuery::query()->where('term', 'zzz-no-match-xyz')->first();
    expect($row)->not->toBeNull()
        ->and($row->zero_results)->toBeTrue()
        ->and($row->count)->toBe(1);

    // Repeated searches increment the counter (editorial ticket weight).
    app(SearchService::class)->search('zzz-no-match-xyz');
    expect(SearchQuery::query()->where('term', 'zzz-no-match-xyz')->first()->count)->toBe(2);
});

it('renders the search page noindex with grouped tabs', function (): void {
    $this->get('/search')
        ->assertOk()
        ->assertSee('What are you looking for?', false);
});

it('generates the sitemap index with contracted children', function (): void {
    $files = app(SitemapGenerator::class)->generate();

    expect(array_keys($files))->toEqualCanonicalizing([
        'sitemap-pages.xml', 'sitemap-services.xml', 'sitemap-cities.xml',
        'sitemap-housing.xml', 'sitemap-posts.xml', 'sitemap-categories.xml',
        'sitemap_index.xml',
    ]);

    // Services present, noindex excluded.
    expect($files['sitemap-services.xml'])->toContain('/services/employee-mobility/relocation');

    // Index links every child.
    expect($files['sitemap_index.xml'])->toContain('sitemap-services.xml')
        ->toContain('sitemap-cities.xml');
});

it('excludes noindex and draft entries from the sitemap', function (): void {
    $page = Page::query()->where('slug', 'about')->first();
    $page->noindex = true;
    $page->noindex_reason = 'Duplicate during migration.';
    $page->noindex_confirmed_at = now();
    $page->save();

    $city = City::query()->where('slug', 'pune')->first();
    $city->status = PageStatus::Draft->value;
    $city->save();

    $files = app(SitemapGenerator::class)->generate();

    expect($files['sitemap-pages.xml'])->not->toContain('/about')
        ->and($files['sitemap-cities.xml'])->not->toContain('/cities/pune')
        ->and($files['sitemap-cities.xml'])->toContain('/cities/gurugram');
});

it('enqueues sitemap regeneration on publish events', function (): void {
    // assertListening lives on the Event FAKE — binding it swaps in the
    // fake while assertListening reflects the real registered listeners.
    Event::fake();

    Event::assertListening(
        ServicePublished::class,
        RegenerateSitemap::class,
    );

    Event::assertListening(
        PagePublished::class,
        RegenerateSitemap::class,
    );

    Event::assertListening(
        CityPublished::class,
        RegenerateSitemap::class,
    );
});

it('writes robots.txt per the seo contract', function (): void {
    $robots = file_get_contents(public_path('robots.txt'));

    expect($robots)->toContain('Disallow: /search*')
        ->toContain('Disallow: /admin*')
        ->toContain('Disallow: /app*')
        ->toContain('Sitemap: https://sewahospitality.com/sitemap_index.xml')
        ->toContain('GPTBot'); // AEO: AI crawlers allowed
});
