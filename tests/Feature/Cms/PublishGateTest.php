<?php

use App\Modules\Cms\Enums\PageStatus;
use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Services\PublishGate;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

function draftPage(array $overrides = []): Page
{
    return Page::query()->create([
        'title' => 'Gate test page',
        'slug' => 'gate-test-'.mb_strtolower(Str::random(4)),
        'type' => 'standard',
        'status' => PageStatus::Draft->value,
        'meta_title' => 'Gate test',
        'meta_description' => 'A page built to exercise the publish gate.',
        'blocks' => [
            ['type' => 'hero', 'data' => ['headline' => 'Gate hero', 'sub' => 's', 'ctas' => []]],
            ['type' => 'cta_band', 'data' => ['headline' => 'Go', 'ctas' => [['label' => 'C', 'url' => '/']]]],
        ],
        ...$overrides,
    ]);
}

it('blocks publish when meta title or description are missing', function (): void {
    $page = draftPage(['meta_title' => '', 'meta_description' => '']);

    $inspection = app(PublishGate::class)->inspect($page);

    expect($inspection['errors'])->toHaveKey('meta_title')
        ->and($inspection['errors'])->toHaveKey('meta_description');
});

it('warns but does not block on guidance overruns', function (): void {
    $page = draftPage([
        'meta_title' => str_repeat('T', 75),
        'meta_description' => str_repeat('D', 200),
    ]);

    $inspection = app(PublishGate::class)->inspect($page);

    expect($inspection['errors'])->toBe([])
        ->and($inspection['warnings'])->toHaveKey('meta_title')
        ->and($inspection['warnings'])->toHaveKey('meta_description');
});

it('blocks publish while blocks are empty', function (): void {
    $page = draftPage(['blocks' => []]);

    expect(app(PublishGate::class)->inspect($page)['errors'])->toHaveKey('blocks');
});

it('requires typed confirmation plus a reason for noindex', function (): void {
    $unconfirmed = draftPage(['noindex' => true]);
    $inspection = app(PublishGate::class)->inspect($unconfirmed);
    expect($inspection['errors'])->toHaveKey('noindex')
        ->and($inspection['errors'])->toHaveKey('noindex_confirm');

    $reasoned = draftPage(['noindex' => true, 'noindex_reason' => 'Duplicate of /about during migration.']);
    expect(app(PublishGate::class)->inspect($reasoned)['errors'])->toHaveKey('noindex_confirm');

    // The editor confirms through noindex_confirmed_at (typed confirm step).
    $reasoned->noindex_confirmed_at = now();
    $reasoned->noindex_confirmed_by = null;

    expect(app(PublishGate::class)->passes($reasoned))->toBeTrue();
});

it('blocks publish when a block cannot render (render probe)', function (): void {
    $page = draftPage();
    $page->blocks = [
        ['type' => 'hero', 'data' => ['headline' => 'ok', 'ctas' => []]],
        ['type' => 'not_a_real_block', 'data' => []],
    ];

    $inspection = app(PublishGate::class)->inspect($page);

    expect($inspection['errors'])->not->toBe([]);
});

it('passes a complete honest page', function (): void {
    expect(app(PublishGate::class)->passes(draftPage()))->toBeTrue();
});
