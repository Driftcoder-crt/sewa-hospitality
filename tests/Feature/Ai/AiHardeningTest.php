<?php

use App\Models\User;
use App\Modules\Ai\Jobs\LeadEnrich;
use App\Modules\Ai\Jobs\TranslateContent;
use App\Modules\Cities\Models\City;
use App\Modules\Cms\Models\Page;
use App\Modules\Csr\Livewire\CsrManager;
use App\Modules\Csr\Models\CsrStory;
use App\Modules\I18n\Models\Locale;
use App\Modules\Leads\Models\Lead;
use App\Modules\Services\Models\Service;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\CmsSeeder;
use Database\Seeders\LocalesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicesSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/*
 * Review-r4 hardening contract for the AI + translation pipeline:
 *   1. The PromptLibrary system contract (register + glossary + JSON
 *      shape) must actually REACH the provider — a system prompt that
 *      is defined but never wired is dead weight and the model drifts
 *      (11-multilingual §4 register rules, 06-content-seo/04 §3
 *      glossary).
 *   2. Variant creation must re-hydrate JSON-cast columns — a raw
 *      attribute copy double-encodes them (media_ids, empty blocks).
 *   3. Variants share the source slug — the schema enforces per
 *      (slug, locale), so every content family can hold variants.
 *   4. Publishing a CSR story fans out translation jobs like every
 *      other publish path.
 */

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

function fakeTranslateOkCsr(): void
{
    Http::fake([
        'api.tokenrouter.com/*' => Http::response([
            'choices' => [['message' => ['content' => '{"title":"第三回研修コホート"}']]],
            'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 40],
        ]),
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => '{"title":"第三回研修コホート"}']]],
            'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 40],
        ]),
    ]);
}

it('sends the system contract — register, glossary, JSON shape — on translate calls', function () {
    Http::fake([
        '*' => Http::response([
            'choices' => [['message' => ['content' => '{"title":"SEWAについて"}']]],
            'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 30],
        ]),
    ]);

    $about = Page::query()->where('slug', 'about')->first();
    TranslateContent::dispatch(Page::class, (string) $about->getKey(), 'ja');

    Http::assertSent(function ($request): bool {
        $messages = $request['messages'] ?? [];

        return ($messages[0]['role'] ?? '') === 'system'
            && str_contains((string) ($messages[0]['content'] ?? ''), 'translation desk')
            && str_contains((string) ($messages[0]['content'] ?? ''), 'formal polite Japanese')
            && str_contains((string) ($messages[0]['content'] ?? ''), 'Sewa Verified')
            && ($messages[1]['role'] ?? '') === 'user'
            && str_contains((string) ($messages[1]['content'] ?? ''), 'Content JSON:');
    });
});

it('sends the anonymous-metadata system contract on enrichment calls', function () {
    Http::fake([
        '*' => Http::response([
            'choices' => [['message' => ['content' => '{"segment":"corporate","language":"en","summary":"s","priority_hint":"low"}']]],
            'usage' => ['prompt_tokens' => 50, 'completion_tokens' => 20],
        ]),
    ]);

    $lead = Lead::factory()->create(['message' => 'Corporate move in Q3.']);

    LeadEnrich::dispatch($lead->getKey());

    Http::assertSent(function ($request): bool {
        $messages = $request['messages'] ?? [];

        return ($messages[0]['role'] ?? '') === 'system'
            && str_contains((string) ($messages[0]['content'] ?? ''), 'Never invent PII')
            && ($messages[1]['role'] ?? '') === 'user';
    });
});

it('re-hydrates JSON-cast columns on the variant — no double-encoding', function () {
    fakeTranslateOkCsr();

    $story = CsrStory::query()->create([
        'slug' => 'training-cohort-3',
        'title' => 'Third training cohort graduates',
        'body' => '<p>Twelve women completed the hospitality skills program.</p>',
        'status' => 'published',
        'published_at' => now(),
        'media_ids' => [5, 7],
    ]);

    TranslateContent::dispatch(CsrStory::class, (string) $story->getKey(), 'ja');

    $variant = CsrStory::query()
        ->where('locale_source_id', $story->getKey())
        ->where('locale', 'ja')
        ->first();

    expect($variant)->not()->toBeNull()
        // A raw-attribute copy would land "[5,7]" as a JSON STRING here.
        ->and($variant->media_ids)->toBeArray()->toBe([5, 7])
        // Human gate: machine output is a draft, never self-published.
        ->and($variant->status)->toBe('draft');
});

it('copies empty-array JSON columns onto the variant without corrupting them', function () {
    Http::fake([
        '*' => Http::response([
            'choices' => [['message' => ['content' => '{"title":"SEWAについて"}']]],
            'usage' => ['prompt_tokens' => 80, 'completion_tokens' => 20],
        ]),
    ]);

    $about = Page::query()->where('slug', 'about')->first();
    $about->forceFill(['blocks' => []])->save();

    TranslateContent::dispatch(Page::class, (string) $about->getKey(), 'ja');

    $variant = Page::query()->where('locale_source_id', $about->getKey())->where('locale', 'ja')->first();

    // '[]' double-encoded would read back as the STRING '[]', not an array.
    expect($variant)->not()->toBeNull()
        ->and($variant->blocks)->toBeArray()->toBe([]);
});

it('creates variants sharing the source slug for service and city entities', function () {
    $this->seed([ServicesSeeder::class, CitiesSeeder::class]);
    fakeTranslateOkCsr();

    $service = Service::query()->where('slug', 'immigration')->firstOrFail();
    TranslateContent::dispatch(Service::class, (string) $service->getKey(), 'ja');

    $city = City::query()->firstOrFail();
    TranslateContent::dispatch(City::class, (string) $city->getKey(), 'ja');

    $serviceVariant = Service::query()->where('locale_source_id', $service->getKey())->where('locale', 'ja')->first();
    $cityVariant = City::query()->where('locale_source_id', $city->getKey())->where('locale', 'ja')->first();

    // Global slug uniques would have thrown before the per-(slug, locale)
    // constraint — the variant must exist WITH the source slug intact.
    expect($serviceVariant)->not()->toBeNull()
        ->and($serviceVariant->slug)->toBe('immigration')
        ->and($cityVariant)->not()->toBeNull()
        ->and($cityVariant->slug)->toBe($city->slug);
});

it('fans out translation jobs when an editor publishes a CSR story', function () {
    fakeTranslateOkCsr();

    $editor = User::factory()->create();
    $editor->syncRoles(['editor']);
    test()->actingAs($editor, 'web');

    $story = CsrStory::query()->create([
        'slug' => 'training-cohort-4',
        'title' => 'Fourth training cohort graduates',
        'body' => '<p>Twenty women completed the program.</p>',
    ]);

    Livewire::test(CsrManager::class)
        ->call('toggleStoryPublish', (string) $story->getKey())
        ->assertHasNoErrors();

    // The story publishes, and the fan-out lands one draft variant per
    // enabled auto-translate locale (hi, ja, ko, tr, ar).
    expect($story->fresh()->status)->toBe('published')
        ->and(CsrStory::query()->where('locale_source_id', $story->getKey())->count())->toBe(5);
});
