<?php

use App\Modules\Ai\Jobs\LeadEnrich;
use App\Modules\Ai\Jobs\TranslateContent;
use App\Modules\Ai\Services\TranslationDispatcher;
use App\Modules\Cms\Enums\PageStatus;
use App\Modules\Cms\Models\Page;
use App\Modules\I18n\Models\Locale;
use App\Modules\Leads\Models\Lead;
use Database\Seeders\CmsSeeder;
use Database\Seeders\LocalesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, SettingsSeeder::class, LocalesSeeder::class, CmsSeeder::class]);
    Locale::flushRegistry();
    config([
        'ai.enabled' => true,
        'ai.providers.tokenrouter.key' => 'test-tokenrouter-key',
        'ai.providers.openrouter.key' => 'test-openrouter-key',
    ]);
});

function fakeTranslateOk(): void
{
    Http::fake([
        'api.tokenrouter.com/*' => Http::response([
            'choices' => [['message' => ['content' => '{"title":"SEWAについて","meta_title":"SEWAについて — Sewa Hospitality"}']]],
            'usage' => ['prompt_tokens' => 200, 'completion_tokens' => 60],
        ]),
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => '{"title":"SEWAについて","meta_title":"SEWAについて — Sewa Hospitality"}']]],
            'usage' => ['prompt_tokens' => 200, 'completion_tokens' => 60],
        ]),
    ]);
}

it('creates a DRAFT ja variant with translated fields and preserved structure', function () {
    fakeTranslateOk();

    $about = Page::query()->where('slug', 'about')->first();

    // Original structure: the translated JSON lacks every other column —
    // MergeTranslated keeps them from the source payload.
    TranslateContent::dispatch(Page::class, (string) $about->getKey(), 'ja');

    $variant = Page::query()
        ->where('locale_source_id', $about->getKey())
        ->where('locale', 'ja')
        ->first();

    expect($variant)->not()->toBeNull()
        ->and($variant->status)->toBe(PageStatus::Draft) // machine output can never self-publish
        ->and($variant->title)->toBe('SEWAについて')
        ->and($variant->slug)->toBe('about') // copied from source
        ->and($variant->blocks)->toEqual($about->blocks); // untouched by this payload
});

it('is idempotent — one variant per entity-locale, re-dispatch is a no-op', function () {
    fakeTranslateOk();

    $about = Page::query()->where('slug', 'about')->first();

    TranslateContent::dispatch(Page::class, (string) $about->getKey(), 'ja');
    TranslateContent::dispatch(Page::class, (string) $about->getKey(), 'ja');

    expect(Page::query()->where('locale', 'ja')->count())->toBe(1);
});

it('fans out a machine-draft job per enabled auto-translate locale on publish', function () {
    fakeTranslateOk();

    $about = Page::query()->where('slug', 'about')->first();
    $about->forceFill(['status' => 'published'])->save();

    TranslationDispatcher::forEntity($about->fresh());

    // hi, ja, ko, tr, ar (en is the source; all five auto-translate).
    expect(Page::query()->whereNotNull('locale_source_id')->count())->toBe(5);

    $locales = Page::query()->whereNotNull('locale_source_id')->pluck('locale')->all();

    expect($locales)->toEqualCanonicalizing(['hi', 'ja', 'ko', 'tr', 'ar']);
});

it('dispatches nothing when the kill switch is on — nothing blocks', function () {
    config(['ai.enabled' => false]);

    $about = Page::query()->where('slug', 'about')->first();

    expect(TranslationDispatcher::forEntity($about))->toBe(0)
        ->and(Page::query()->whereNotNull('locale_source_id')->count())->toBe(0);
});

it('parks on malformed model output without creating a broken variant', function () {
    Http::fake([
        '*' => Http::response([
            'choices' => [['message' => ['content' => 'not json at all']]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
        ]),
    ]);

    $about = Page::query()->where('slug', 'about')->first();

    TranslateContent::dispatch(Page::class, (string) $about->getKey(), 'ja');

    expect(Page::query()->where('locale', 'ja')->count())->toBe(0)
        // EN keeps serving untouched.
        ->and(Page::query()->where('slug', 'about')->where('locale', 'en')->exists())->toBeTrue();
});

it('never serves a machine-draft variant publicly under its locale prefix', function () {
    fakeTranslateOk();

    $about = Page::query()->where('slug', 'about')->first();

    TranslateContent::dispatch(Page::class, (string) $about->getKey(), 'ja');

    // The draft exists but /ja/about still renders the EN source.
    $this->get('/ja/about')
        ->assertStatus(200)
        ->assertDontSee('SEWAについて');
});

it('enriches a lead from anonymous metadata and stores an advisory suggestion', function () {
    Http::fake([
        '*' => Http::response([
            'choices' => [['message' => ['content' => '{"segment":"corporate","language":"ja","summary":"Relocation support for Q3.","priority_hint":"high"}']]],
            'usage' => ['prompt_tokens' => 90, 'completion_tokens' => 40],
        ]),
    ]);

    $lead = Lead::factory()->quote()->create([
        'locale' => 'ja',
        'message' => 'Corporate move in Q3 — we need serviced apartments.',
    ]);

    LeadEnrich::dispatch($lead->getKey());

    $lead->refresh();
    $ai = $lead->enrichment['ai'] ?? null;

    expect($ai)->not()->toBeNull()
        ->and($ai['segment'])->toBe('corporate')
        ->and($ai['language'])->toBe('ja')
        ->and($ai['priority_hint'])->toBe('high')
        // Advisory only: the status machine is untouched.
        ->and($lead->status->value)->toBe('new')
        ->and($lead->assigned_user_id)->toBeNull();
});

it('never sends direct PII to the provider', function () {
    Http::fake([
        '*' => Http::response([
            'choices' => [['message' => ['content' => '{"segment":"family","language":"en","summary":"s","priority_hint":"low"}']]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 10],
        ]),
    ]);

    $lead = Lead::factory()->create([
        'name' => 'Aiko Tanaka',
        'email' => 'aiko.tanaka@corp.example',
        'phone' => '+91 9811111111',
        'message' => 'Need help — email aiko.tanaka@corp.example or call +91 9811111111.',
    ]);

    LeadEnrich::dispatch($lead->getKey());

    Http::assertSent(function ($request) use ($lead): bool {
        $body = $request->body();

        return ! str_contains($body, $lead->name)
            && ! str_contains($body, $lead->email)
            && ! str_contains($body, $lead->phone);
    });
});

it('leaves the lead untouched when enrichment degrades', function () {
    Http::fake(['*' => Http::response('down', 500)]);

    $lead = Lead::factory()->create(['message' => 'Hello from a Korean family']);

    LeadEnrich::dispatch($lead->getKey());

    $lead->refresh();

    expect($lead->enrichment['ai'] ?? null)->toBeNull()
        ->and($lead->status->value)->toBe('new');
});
