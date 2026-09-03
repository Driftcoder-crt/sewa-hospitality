<?php

use App\Modules\Cities\Models\City;
use App\Modules\Cms\Models\Page;
use App\Modules\I18n\Models\Locale;
use App\Modules\I18n\Services\ContentVariants;
use Database\Seeders\CmsSeeder;
use Database\Seeders\LocalesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, SettingsSeeder::class, LocalesSeeder::class, CmsSeeder::class]);
    Locale::flushRegistry();
});

function localizeVariant(Model $source, string $locale, array $overrides = []): Model
{
    $variant = $source->replicate();
    $variant->locale = $locale;
    $variant->locale_source_id = $source->getKey();
    $variant->status = 'published';
    $variant->slug = $source->slug;

    foreach ($overrides as $key => $value) {
        $variant->{$key} = $value;
    }

    $variant->save();

    return $variant;
}

it('falls back to the EN source when a localized variant is missing', function () {
    $city = City::factory()->create(['status' => 'published']);

    $found = ContentVariants::firstInLocale(
        City::query()->published()->where('slug', $city->slug),
        'ja',
    );

    expect($found?->getKey())->toBe($city->getKey())
        ->and($found->locale)->toBe('en');
});

it('prefers the localized variant when one exists', function () {
    $city = City::factory()->create(['status' => 'published']);
    $variant = localizeVariant($city, 'ja', ['name' => 'グルガーオン']);

    $found = ContentVariants::firstInLocale(
        City::query()->published()->where('slug', $city->slug),
        'ja',
    );

    expect($found?->getKey())->toBe($variant->getKey())
        ->and($found->name)->toBe('グルガーオン');
});

it('never hides EN content from a localized listing', function () {
    $withVariant = City::factory()->create(['status' => 'published', 'name' => 'Gurugram']);
    $enOnly = City::factory()->create(['status' => 'published', 'name' => 'Kochi']);
    localizeVariant($withVariant, 'ja', ['name' => 'グルガーオン']);

    $jaRows = ContentVariants::localizedList(City::query()->published(), 'ja')->get();

    // The ja variant + the EN-only city both serve; the EN twin of the
    // ja variant is excluded (it is represented by its variant).
    expect($jaRows->pluck('name')->all())->toEqualCanonicalizing(['グルガーオン', 'Kochi'])
        ->and($jaRows)->toHaveCount(2);

    // EN listing shows the two EN sources, never a variant.
    $enRows = ContentVariants::localizedList(City::query()->published(), 'en')->get();

    expect($enRows->pluck('name')->all())->toEqualCanonicalizing(['Gurugram', 'Kochi']);
});

it('lists hreflang alternates only for PUBLISHED variants plus x-default', function () {
    $about = Page::query()->where('slug', 'about')->first();

    $ja = localizeVariant($about, 'ja');
    localizeVariant($about, 'ko', ['status' => 'draft']); // machine draft — omitted

    $alternates = ContentVariants::alternatesFor($about);

    expect($alternates)->toHaveKey('en')
        ->toHaveKey('ja')
        ->toHaveKey('x-default')
        ->not()->toHaveKey('ko')
        ->and($alternates['x-default'])->toBe($alternates['en'])
        ->and($alternates['ja'])->toBe('/about')
        ->and($ja->getKey())->not()->toBe($about->getKey());
});

it('renders EN content under a localized URL and omits that hreflang until a variant publishes', function () {
    // No ja variant: /ja/about renders the EN source (never a 404) and
    // hreflang stays truthful — no ja claim in the <head> alternates.
    // (The footer switcher carries hreflang per-locale by design, so
    // the assertions pin the <link rel="alternate"> tag itself.)
    $this->get('/ja/about')
        ->assertStatus(200)
        ->assertSee('<link rel="alternate" hreflang="x-default"', false)
        ->assertDontSee('<link rel="alternate" hreflang="ja"', false);

    localizeVariant(Page::query()->where('slug', 'about')->first(), 'ja', ['title' => 'SEWAについて']);

    $this->get('/ja/about')
        ->assertStatus(200)
        ->assertSee('<link rel="alternate" hreflang="ja"', false);
});

it('serves RTL direction for the ar locale', function () {
    localizeVariant(Page::query()->where('slug', 'about')->first(), 'ar', ['title' => 'عن سيفا']);

    $this->get('/ar/about')
        ->assertStatus(200)
        ->assertSee('dir="rtl"', false);
});

it('keeps the hreflang set off noindex pages', function () {
    Page::query()->where('slug', 'about')->update(['noindex' => true]);

    // Pins the <head> alternate set (the footer switcher carries
    // per-locale hreflang attributes on <a> by design).
    $this->get('/about')
        ->assertStatus(200)
        ->assertSee('noindex', false)
        ->assertDontSee('<link rel="alternate" hreflang=', false);
});
