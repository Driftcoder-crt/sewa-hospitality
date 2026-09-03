<?php

use App\Modules\Cms\Enums\PageStatus;
use App\Modules\Cms\Models\Page;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

function scheduledPage(?string $at): Page
{
    return Page::query()->create([
        'title' => 'Scheduled page',
        'slug' => 'scheduled-'.mb_strtolower(Str::random(4)),
        'status' => $at ? PageStatus::Scheduled->value : PageStatus::Draft->value,
        'scheduled_at' => $at,
        'meta_title' => 'Scheduled',
        'meta_description' => 'Publishes on time, never broken.',
        'blocks' => [['type' => 'hero', 'data' => ['headline' => 'Scheduled', 'ctas' => []]]],
    ]);
}

it('publishes due pages through the cron-driven command', function (): void {
    $due = scheduledPage(now()->subMinutes(5)->toIso8601String());
    $future = scheduledPage(now()->addHours(2)->toIso8601String());

    $this->artisan('cms:publish-scheduled')->assertSuccessful();

    expect($due->fresh()->status)->toBe(PageStatus::Published)
        ->and($due->fresh()->published_at)->not->toBeNull()
        ->and($future->fresh()->status)->toBe(PageStatus::Scheduled);
});

it('holds back due pages that fail the publish gate', function (): void {
    // Missing meta description → gate blocks, page stays scheduled and audited.
    $blocked = Page::query()->create([
        'title' => 'Broken scheduled',
        'slug' => 'broken-scheduled',
        'status' => PageStatus::Scheduled->value,
        'scheduled_at' => now()->subMinutes(5),
        'meta_title' => 'No description',
        'blocks' => [['type' => 'hero', 'data' => ['headline' => 'x', 'ctas' => []]]],
    ]);

    $this->artisan('cms:publish-scheduled')->assertSuccessful();

    expect($blocked->fresh()->status)->toBe(PageStatus::Scheduled);
});

it('reports a dry run without publishing', function (): void {
    $page = scheduledPage(now()->subMinute()->toIso8601String());

    $this->artisan('cms:publish-scheduled', ['--dry-run' => true])->assertSuccessful();

    expect($page->fresh()->status)->toBe(PageStatus::Scheduled);
});
