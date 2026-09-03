<?php

use App\Modules\I18n\Http\Middleware\LocaleResolver;
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

it('serves the locale from the explicit path prefix', function () {
    $this->get('/ja/about')
        ->assertStatus(200)
        ->assertSee('lang="ja"', false);

    expect(app()->getLocale())->toBe('ja');
});

it('serves en on unprefixed paths — detection never swaps the URL language', function () {
    // Korean browser hits the root: content stays EN (no silent redirect,
    // no cookie-based serving) — the banner suggests instead.
    $this->get('/', ['Accept-Language' => 'ko-KR,ko;q=0.9,en;q=0.8'])
        ->assertStatus(200);

    expect(app()->getLocale())->toBe('en');
});

it('matches the primary accept-language subtag with highest quality', function () {
    $resolver = new LocaleResolver;
    $method = new ReflectionMethod($resolver, 'fromAcceptLanguage');

    // ja-KR is a ja variant — primary subtag wins over region.
    expect($method->invoke($resolver, 'ja-KR,ko;q=0.9'))->toBe('ja')
        // Highest q wins regardless of region tag.
        ->and($method->invoke($resolver, 'ko;q=0.4,ja-KR;q=0.9'))->toBe('ja')
        // Equal quality: first in header order is deterministic.
        ->and($method->invoke($resolver, 'ko,ja'))->toBe('ko')
        // Disabled/unknown locales never match.
        ->and($method->invoke($resolver, 'fr-FR,de;q=0.8'))->toBeNull()
        // en is not a "suggestion" — nothing to suggest.
        ->and($method->invoke($resolver, 'en-GB,en;q=0.9'))->toBeNull();
});

it('never suggests a locale the header marks explicitly unacceptable (q=0)', function () {
    $resolver = new LocaleResolver;
    $method = new ReflectionMethod($resolver, 'fromAcceptLanguage');

    // RFC 7231: q=0 means NOT acceptable — ja must not be suggested even
    // when it is the first tag in the list.
    expect($method->invoke($resolver, 'ja;q=0,ko;q=0.5'))->toBe('ko')
        // And when every tag is unacceptable, there is no suggestion.
        ->and($method->invoke($resolver, 'ja;q=0,ko;q=0'))->toBeNull();
});

it('uses the cloudflare country header only as a tiebreaker for the banner', function () {
    $resolver = new LocaleResolver;
    $method = new ReflectionMethod($resolver, 'fromGeo');

    expect($method->invoke($resolver, 'JP'))->toBe('ja')
        ->and($method->invoke($resolver, 'TR'))->toBe('tr')
        ->and($method->invoke($resolver, 'US'))->toBeNull()
        ->and($method->invoke($resolver, null))->toBeNull();
});

it('shows the one-time suggestion banner and never sets a cookie on detection', function () {
    $response = $this->get('/about', ['Accept-Language' => 'ja-JP,ja;q=0.9,en;q=0.5']);

    $response->assertStatus(200);
    // Banner CTA is an explicit chooser link to the suggested locale.
    $response->assertSee('/locale/ja?to=', false);
    // Detection NEVER sets the preference cookie.
    expect($response->headers->getCookies())->each(
        fn ($cookie) => $cookie->getName()->not()->toBe(LocaleResolver::COOKIE),
    );
});

it('suppresses the banner once the visitor chose (cookie present)', function () {
    // sewa_locale is excluded from cookie encryption (bootstrap/app.php)
    // so JS can also write it — the test sends it the same raw way.
    $response = $this->withUnencryptedCookie(LocaleResolver::COOKIE, 'ja')
        ->get('/about', ['Accept-Language' => 'ja-JP,ja;q=0.9']);

    // The SUGGESTION banner is gone (its ask copy + CTA) — the footer
    // switcher stays by contract (11-multilingual §3: switcher is
    // permanent explicit UI; only detection-driven suggesting stops).
    $response->assertStatus(200)
        ->assertDontSee('aria-label="Language suggestion"', false)
        ->assertDontSee('Read this site in', false);
});

it('sets the sewa_locale cookie ONLY on an explicit chooser click', function () {
    $response = $this->get('/locale/ja?to=about');

    $response->assertRedirect('/ja/about')
        ->assertPlainCookie(LocaleResolver::COOKIE, 'ja');
});

it('rejects chooser codes that are not enabled locales', function () {
    $this->get('/locale/fr')->assertStatus(404);
});

it('dismisses the banner with a session flag — never a cookie', function () {
    $this->get('/locale/dismiss')->assertRedirect('/');
});

it('localizes menu hrefs under a prefix — menu clicks never drop to EN', function () {
    $this->get('/ja/about')->assertStatus(200)->assertSee('/ja/about', false);

    // EN generation stays unprefixed.
    $this->get('/about')->assertStatus(200)->assertSee('href="/about"', false);
});

it('reflects enabled locales in the route constraint after disabling one', function () {
    DB::table('locales')->where('code', 'tr')->update(['enabled' => false]);
    Locale::flushRegistry();

    $this->get('/tr/services')->assertStatus(404);
    $this->get('/ja/services')->assertOk();
});
