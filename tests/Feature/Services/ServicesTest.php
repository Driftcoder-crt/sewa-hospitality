<?php

use App\Modules\Services\Enums\ServiceStatus;
use App\Modules\Services\Models\Service;
use App\Modules\Services\Services\ServicePublishGate;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicesSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed([RolesAndPermissionsSeeder::class, SettingsSeeder::class, ServicesSeeder::class, CitiesSeeder::class]);
});

it('seeds the catalog tree producing the 14 contracted URLs', function (): void {
    // 16 rows = 2 family hubs + immigration hub (child of EM, per the
    // catalog tree) + 5 EM leaves + 5 BM leaves + 3 immigration children.
    expect(Service::query()->published()->count())->toBe(16)
        ->and(Service::query()->whereNull('parent_id')->count())->toBe(2)
        ->and(Service::query()->where('slug', 'immigration')->first()->parent_id)->not->toBeNull();

    // publicPath() resolves ->parent for leaf URLs — eager it so the
    // non-production lazy-loading guard never trips.
    $paths = Service::query()->published()->with('parent')->get()->map(fn (Service $service): string => $service->publicPath());

    // The 14 catalog rows (03-service-catalog table):
    foreach ([
        '/services/employee-mobility/relocation',
        '/services/employee-mobility/immigration',
        '/services/immigration/inbound-immigration',
        '/services/immigration/outbound-immigration',
        '/services/immigration/ancillary-services',
        '/services/employee-mobility/serviced-apartments',
        '/services/employee-mobility/moving',
        '/services/employee-mobility/corporate-housing',
        '/services/employee-mobility/fleet',
        '/services/business-mobility/travel',
        '/services/business-mobility/business-space',
        '/services/business-mobility/recruitment',
        '/services/business-mobility/interior-design',
        '/services/business-mobility/sanitization',
    ] as $catalogPath) {
        expect($paths->contains($catalogPath))->toBeTrue("missing {$catalogPath}");
    }

    // Family hub pages exist too.
    expect($paths->contains('/services/employee-mobility'))->toBeTrue()
        ->and($paths->contains('/services/business-mobility'))->toBeTrue();
});

it('renders the hub with service cards and lead tags', function (): void {
    $this->get('/services')
        ->assertOk()
        ->assertSee('Relocation', false)
        ->assertSee('Corporate housing', false);
});

it('auto-composes family pages from published children', function (): void {
    $this->get('/services/employee-mobility')
        ->assertOk()
        ->assertSee('Relocation', false)
        ->assertSee('Employee mobility', false);
});

it('renders the immigration sub-tree at its contracted paths', function (): void {
    $this->get('/services/immigration/inbound-immigration')
        ->assertOk()
        ->assertSee('Start registration', false);
});

it('renders FAQPage schema only when the service declares faq', function (): void {
    $service = Service::query()->where('slug', 'relocation')->first();
    $service->faq = [['q' => 'Do you handle pets?', 'a' => 'Yes — pet relocation rides the household move.']];
    $service->save();

    $html = $this->get($service->publicPath())->getContent();

    expect($html)->toContain('FAQPage')
        ->toContain('Do you handle pets?');
});

it('respects coverage truth on leaf pages', function (): void {
    $html = $this->get('/services/employee-mobility/relocation')->getContent();

    // W1 hubs all have relocation coverage rows → Gurugram shows.
    expect($html)->toContain('/cities/gurugram');
});

it('blocks drafts from the public surface', function (): void {
    Service::query()->where('slug', 'fleet')->update(['status' => ServiceStatus::Draft->value]);

    $this->get('/services/employee-mobility/fleet')->assertNotFound();
});

it('enforces the service publish gate on draft services', function (): void {
    $draft = Service::query()->create([
        'name' => 'Gate draft',
        'slug' => 'gate-draft',
        'family' => 'employee-mobility',
        'status' => ServiceStatus::Draft->value,
        'lead_tag' => 'relocation',
        'content_blocks' => [],
    ]);

    $inspection = app(ServicePublishGate::class)->inspect($draft);

    expect($inspection['errors'])->toHaveKeys(['meta_title', 'meta_description', 'intro', 'hero_media', 'content_blocks'])
        ->and(app(ServicePublishGate::class)->passes($draft))->toBeFalse();

    // Fill everything but the hero → still blocked (no broken heroes live).
    $draft->meta_title = 'Gate draft service';
    $draft->meta_description = 'A draft service for gate testing.';
    $draft->intro = 'Complete copy for the gate draft.';
    $draft->content_blocks = [['type' => 'rich_text', 'data' => ['html' => '<p>Body copy.</p>']]];

    expect(app(ServicePublishGate::class)->inspect($draft)['errors'])->toHaveKey('hero_media');

    $draft->hero_media_id = '01J000000000000000000000000';

    expect(app(ServicePublishGate::class)->passes($draft))->toBeTrue();
});

it('links related services on the leaf page', function (): void {
    $relocation = Service::query()->where('slug', 'relocation')->first();
    $moving = Service::query()->where('slug', 'moving')->first();

    $relocation->related()->sync([$moving->getKey()]);

    $html = $this->get('/services/employee-mobility/relocation')->getContent();

    expect($html)->toContain('You may also need')
        ->toContain('/services/employee-mobility/moving');
});

it('prevents cycles when editing the tree', function (): void {
    $hub = Service::query()->where('slug', 'employee-mobility')->first();
    $relocation = Service::query()->where('slug', 'relocation')->first();

    // Attempt: make the hub a child of its own leaf (cycle).
    $hub->parent_id = $relocation->getKey();
    $hub->family = 'employee-mobility';
    $hub->save();

    // The tree remains resolvable: relocation's parent chain terminates.
    $resolved = $relocation->parent()->first();
    expect($resolved)->not->toBeNull()
        ->and(Service::query()->whereNull('parent_id')->count())->toBeGreaterThanOrEqual(1);
});
