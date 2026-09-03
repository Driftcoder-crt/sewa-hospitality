<?php

use App\Models\User;
use App\Modules\Ai\Models\AiInvocation;
use App\Modules\Cms\Services\SitemapGenerator;
use App\Modules\I18n\Models\Locale;
use App\Modules\Leads\Models\Lead;
use App\Support\Analytics\Consent;
use App\Support\Analytics\Jobs\ReportConversion;
use App\Support\Analytics\MeasurementProtocol;
use App\Support\Locks\CircuitBreaker;
use Database\Seeders\CmsSeeder;
use Database\Seeders\LocalesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, SettingsSeeder::class, LocalesSeeder::class, CmsSeeder::class]);
    Locale::flushRegistry();
});

afterEach(function () {
    // Remove the artifacts the full-pass test materializes in public/.
    foreach ([
        'sitemap_index.xml', 'sitemap-pages.xml', 'sitemap-services.xml', 'sitemap-cities.xml',
        'sitemap-housing.xml', 'sitemap-posts.xml', 'sitemap-categories.xml', 'llms.txt',
    ] as $artifact) {
        File::delete(public_path($artifact));
    }

    File::delete(public_path('storage/.gitkeep'));
    @rmdir(public_path('storage'));
});

/* ── Consent-first analytics (02-analytics-plan §1.1) ─────────────── */

it('never renders an analytics tag before an explicit consent choice', function () {
    config(['sewa.analytics.ga4_id' => 'G-TEST123', 'sewa.analytics.gtm_id' => 'GTM-TEST1']);

    $response = $this->get('/about')->assertOk();

    $response->assertDontSee('googletagmanager', false)
        ->assertDontSee('gtag(', false);

    // The banner invites the explicit choice instead.
    $response->assertSee('sewa_consent', false);
});

it('loads GA4 only after an explicit analytics choice', function () {
    config(['sewa.analytics.ga4_id' => 'G-TEST123', 'sewa.analytics.gtm_id' => 'GTM-TEST1']);

    $this->withUnencryptedCookie(Consent::COOKIE, 'all')
        ->get('/about')
        ->assertOk()
        ->assertSee('googletagmanager.com/gtag/js?id=G-TEST123', false)
        // Consent Mode update rides the loader; ads signals stay denied.
        ->assertSee("'ad_personalization': 'denied'", false);

    // Banner rests once the choice exists.
    $this->withUnencryptedCookie(Consent::COOKIE, 'all')
        ->get('/about')
        ->assertOk()
        ->assertDontSee('Essential only', false);
});

it('never loads analytics for the essential-only choice', function () {
    config(['sewa.analytics.ga4_id' => 'G-TEST123']);

    $this->withUnencryptedCookie(Consent::COOKIE, 'essential')
        ->get('/about')
        ->assertOk()
        ->assertDontSee('googletagmanager', false);
});

it('widens the CSP for analytics endpoints only under consent + configuration', function () {
    config(['sewa.analytics.ga4_id' => 'G-TEST123']);

    // No consent: strict CSP.
    $strict = (string) $this->get('/about')->assertOk()->headers->get('Content-Security-Policy');

    expect($strict)->not()->toContain('googletagmanager');

    // Consent: the GA4 endpoints enter script-src/connect-src.
    $granted = (string) $this->withUnencryptedCookie(Consent::COOKIE, 'analytics')
        ->get('/about')->assertOk()->headers->get('Content-Security-Policy');

    expect($granted)->toContain('https://www.googletagmanager.com')
        ->toContain('https://www.google-analytics.com');
});

/* ── Server-confirmed conversions (§1.2 + §4) ─────────────────────── */

it('sends PII-free generate_lead events for consenting leads', function () {
    config([
        'sewa.analytics.ga4_id' => 'G-TEST123',
        'sewa.analytics.ga4_api_secret' => 'test-secret',
    ]);

    Http::fake(['www.google-analytics.com/*' => Http::response('', 204)]);

    $lead = Lead::factory()->create([
        'name' => 'Aiko Tanaka',
        'email' => 'aiko@corp.example',
        'consent_at' => now(),
    ]);

    $job = ReportConversion::forLead($lead);

    expect($job)->not()->toBeNull();

    $job->handle();

    Http::assertSent(function ($request) use ($lead): bool {
        $body = (string) $request->body();

        return str_contains($request->url(), 'measurement_id=G-TEST123')
            && str_contains($body, 'generate_lead')
            && str_contains($body, 'transaction_id')
            // PII boundary (§1.1): ids and context only.
            && ! str_contains($body, $lead->email)
            && ! str_contains($body, $lead->name);
    });
});

it('never calls the measurement protocol for subjects without consent', function () {
    config([
        'sewa.analytics.ga4_id' => 'G-TEST123',
        'sewa.analytics.ga4_api_secret' => 'test-secret',
    ]);

    Http::fake(['*' => Http::response('', 204)]);

    // The schema contract makes consent REQUIRED at lead creation
    // (schema §leads: consent_at non-null) — a consent-less lead cannot
    // exist in the DB, so the guard is tested on an unpersisted model
    // exactly like the dispatcher would receive a malformed subject.
    $lead = Lead::factory()->make(['consent_at' => null]);

    expect(ReportConversion::forLead($lead))->toBeNull();

    Http::assertNothingSent();
});

it('degrades silently when the GA4 endpoint is down', function () {
    config([
        'sewa.analytics.ga4_id' => 'G-TEST123',
        'sewa.analytics.ga4_api_secret' => 'test-secret',
    ]);

    Http::fake(['*' => Http::response('', 500)]);

    $job = ReportConversion::forLead(Lead::factory()->create(['consent_at' => now()]));
    $job->handle();

    expect(MeasurementProtocol::track('generate_lead', '01JTEST', []))->toBeFalse()
        ->and(AiInvocation::count())->toBe(0); // the AI ledger stays untouched

    // The breaker opens after repeated failures — subsequent calls skip.
    for ($i = 0; $i < CircuitBreaker::FAILURE_THRESHOLD; $i++) {
        MeasurementProtocol::track('generate_lead', '01JTEST'.$i, []);
    }

    expect(CircuitBreaker::isOpen(MeasurementProtocol::SERVICE))->toBeTrue();
});

/* ── /llms.txt (05-aeo §2) ────────────────────────────────────────── */

it('generates the curated llms.txt index from published content', function () {
    $md = app(SitemapGenerator::class)->generateLlms();

    expect($md)->toContain('# Sewa Hospitality')
        ->toContain('## Services')
        ->toContain('/about)')
        ->toContain('/housing/verified')
        ->toContain('DT Mega Mall')
        ->toContain('Sewa Verified');
});

/* ── sewa:launch-verify (13-testing-qa §2 gate 9) ────────────────── */

it('runs the launch gate: green on seeded checks, honest about missing artifacts', function () {
    User::factory()->create()->syncRoles(['admin']);

    // In the test container the SEO artifacts + storage symlink don't
    // exist yet, so the gate must FAIL — but the seed/database checks
    // must be visibly green.
    $this->artisan('sewa:launch-verify')
        ->expectsOutputToContain('[PASS] database reachable')
        ->expectsOutputToContain('[PASS] locales seeded')
        ->expectsOutputToContain('[FAIL] sitemap index')
        ->assertExitCode(1);
});

it('passes the launch gate when every artifact is in place', function () {
    User::factory()->create()->syncRoles(['admin']);

    // Materialize exactly what the deploy runbook produces before the gate.
    app(SitemapGenerator::class)->write();
    File::ensureDirectoryExists(public_path('storage'));
    File::put(public_path('storage/.gitkeep'), '');
    File::ensureDirectoryExists(config('sewa.backups_path'));

    $this->artisan('sewa:launch-verify')->assertExitCode(0);
});

it('warns when AI is neither configured nor killed', function () {
    User::factory()->create()->syncRoles(['admin']);
    // The "neither" posture must be explicit: AI_ENABLED=false in the
    // local .env feeds the config fallback in globallyEnabled(), and
    // the settings row (via the console toggle test) can say "killed"
    // from cache — a stale either would print the kill-switch info
    // line instead of the WARN this test pins.
    config(['ai.enabled' => true, 'ai.providers.tokenrouter.key' => null, 'ai.providers.openrouter.key' => null]);
    Cache::forget('sewa.settings.all');

    app(SitemapGenerator::class)->write();
    File::ensureDirectoryExists(public_path('storage'));
    File::put(public_path('storage/.gitkeep'), '');
    File::ensureDirectoryExists(config('sewa.backups_path'));

    $this->artisan('sewa:launch-verify')
        ->expectsOutputToContain('[WARN] AI neither configured nor killed')
        ->assertExitCode(0); // warn ≠ fail
});

it('links the consent banner to the real privacy-policy page (no 404 href)', function () {
    $this->get('/about')
        ->assertOk()
        ->assertSee('href="/legal/privacy-policy"', false)
        ->assertDontSee('href="/legal/privacy"', false);
});
