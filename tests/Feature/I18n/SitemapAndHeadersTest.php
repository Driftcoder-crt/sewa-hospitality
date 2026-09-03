<?php

use App\Modules\Cms\Services\SitemapGenerator;
use App\Modules\I18n\Models\Locale;
use Database\Seeders\CmsSeeder;
use Database\Seeders\LocalesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, SettingsSeeder::class, LocalesSeeder::class, CmsSeeder::class]);
    Locale::flushRegistry();
});

it('emits one sitemap with xhtml hreflang alternates per URL', function () {
    $files = app(SitemapGenerator::class)->generate();

    $pages = $files['sitemap-pages.xml'];

    expect($pages)->toContain('xmlns:xhtml="http://www.w3.org/1999/xhtml"')
        ->toContain('<loc>'.rtrim(config('app.url'), '/').'/about</loc>')
        ->toContain('hreflang="x-default"')
        ->toContain('hreflang="ja"')
        ->toContain('/ja/about');
});

it('keeps the sitemap truthful when no variants exist for a locale', function () {
    // Disable every non-EN locale: the hreflang set shrinks to en + x-default.
    DB::table('locales')->where('code', '!=', 'en')->update(['enabled' => false]);
    Locale::flushRegistry();

    $pages = app(SitemapGenerator::class)->generate()['sitemap-pages.xml'];

    expect($pages)->toContain('hreflang="x-default"')
        ->not()->toContain('hreflang="ja"');
});

it('locks the recorded CSP contract: nonce\'d scripts, inline styles allowed by decision', function () {
    $response = $this->get('/about')->assertOk();

    $csp = (string) $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain("default-src 'self'")
        ->toContain('script-src')
        ->toContain('nonce-')
        ->toContain("style-src 'self' 'unsafe-inline'")
        ->toContain('upgrade-insecure-requests')
        // Header hardening (05-security-reliability §1.3) still intact.
        ->and((string) $response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and((string) $response->headers->get('X-Frame-Options'))->toBe('DENY')
        ->and((string) $response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
});

it('carries the og:locale matching the resolved locale', function () {
    $this->get('/ja/about')
        ->assertOk()
        ->assertSee('og:locale', false)
        ->assertSee('content="ja"', false);
});
