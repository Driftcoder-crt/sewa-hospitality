<?php

use App\Modules\Cms\Enums\PageStatus;
use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Models\PageRevision;
use App\Modules\Cms\Services\RevisionManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

function revisionPage(): Page
{
    return Page::query()->create([
        'title' => 'Revision page',
        'slug' => 'revision-page',
        'status' => PageStatus::Draft->value,
        'meta_title' => 'Revision',
        'meta_description' => 'Revision trail test page.',
        'blocks' => [['type' => 'hero', 'data' => ['headline' => 'V1', 'ctas' => []]]],
    ]);
}

it('records one revision per content change and skips no-ops', function (): void {
    $manager = app(RevisionManager::class);
    $page = revisionPage();

    $manager->record($page);
    expect($page->revisions()->count())->toBe(1);

    // Same content → no revision noise.
    $manager->record($page->refresh());
    expect($page->revisions()->count())->toBe(1);

    $page->update(['title' => 'Revision page v2']);
    $manager->record($page->refresh());
    expect($page->revisions()->count())->toBe(2);
});

it('restores a revision by writing a NEW one — never destructive', function (): void {
    $manager = app(RevisionManager::class);
    $page = revisionPage();

    $manager->record($page);
    $old = $page->revisions()->first();

    $page->update(['title' => 'Changed forever', 'blocks' => [['type' => 'hero', 'data' => ['headline' => 'V2', 'ctas' => []]]]]);
    $manager->record($page->refresh());

    $restored = $manager->restore($old, null);

    expect($restored->title)->toBe('Revision page')
        ->and($page->revisions()->count())->toBe(3) // v1, v2 + the restore itself
        ->and(PageRevision::query()->where('page_id', $page->getKey())->latest('created_at')->first()->snapshot['title'])
        ->toBe('Revision page');
});

it('keeps the last 20 revisions per page', function (): void {
    $manager = app(RevisionManager::class);
    $page = revisionPage();

    for ($i = 0; $i < 30; $i++) {
        $page->update(['title' => "Version {$i}"]);
        $manager->record($page->refresh());
    }

    expect($page->revisions()->count())->toBe(RevisionManager::CAP)
        ->and($page->revisions()->first()->snapshot['title'])->toBe('Version 29');
});

it('diffs snapshots structurally and at word level', function (): void {
    $manager = app(RevisionManager::class);
    $page = revisionPage();

    $manager->record($page);
    $before = $page->revisions()->first()->snapshot;

    $page->update([
        'blocks' => [
            ['type' => 'hero', 'data' => ['headline' => 'V1 updated headline', 'ctas' => []]],
            ['type' => 'cta_band', 'data' => ['headline' => 'New band', 'ctas' => []]],
        ],
    ]);
    $after = [
        'title' => $page->title,
        'slug' => $page->slug,
        'meta_title' => $page->meta_title,
        'meta_description' => $page->meta_description,
        'noindex' => $page->noindex,
        'blocks' => $page->blocks,
    ];

    $diff = $manager->diff($before, $after);

    expect($diff['added'])->not->toBe([])      // the new cta_band
        ->and($diff['changes'])->not->toBe([]); // hero headline words
});
