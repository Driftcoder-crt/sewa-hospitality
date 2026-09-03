<?php

use App\Modules\Cities\Models\City;
use App\Modules\Cities\Models\HousingUnit;
use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Services\BlockRegistry;
use App\Modules\Cms\Services\PageRenderer;
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

it('registers the complete enumerated catalog after wave 4 (49 blocks)', function (): void {
    expect(BlockRegistry::all())->toHaveCount(49)
        ->and(array_keys(BlockRegistry::all()))->toContain(
            'tabs', 'timeline', 'faq', 'comparison_table', 'story_pillars',
            'gallery_grid', 'carousel', 'full_bleed_media', 'video_feature',
            'logo_cloud', 'testimonial_grid', 'review_highlights', 'stats_band',
        );
});

it('renders the wave-2 blocks with their contracted behaviors', function (): void {
    $samples = [
        'tabs' => ['items' => [['label' => 'First', 'content_html' => '<p>Alpha content</p>'], ['label' => 'Second', 'content_html' => '<p>Beta content</p>']]],
        'timeline' => ['items' => [['date' => 'Day 1', 'title' => 'Kickoff', 'text' => 'Scoping call']]],
        'faq' => ['heading' => 'Common questions', 'items' => [['q' => 'Wave-2 question?', 'a' => 'Wave-2 answer.']]],
        'comparison_table' => [
            'heading' => 'A vs B',
            'highlight' => '2',
            'columns' => [['label' => 'A'], ['label' => 'B']],
            'rows' => [['label' => 'Cost', 'values' => 'Higher, Lower']],
        ],
        'story_pillars' => ['items' => [['title' => 'Pillar', 'hook' => 'A two line hook here.']]],
        'gallery_grid' => ['columns' => '3', 'items' => [['media_id' => null, 'caption' => 'Gallery slot']]],
        'carousel' => ['items' => [['media_id' => null, 'caption' => 'Slide one']]],
        'full_bleed_media' => ['media_id' => null, 'quote' => 'Cinematic quote'],
        'video_feature' => ['youtube_id' => 'abc123', 'title' => 'Video title'],
        'logo_cloud' => ['source' => 'memberships'],
        'testimonial_grid' => ['source' => 'home', 'limit' => '4'],
        'review_highlights' => ['link_reviews' => true],
        'stats_band' => ['as_of' => 'As of Aug 2026', 'items' => [['value' => '1000', 'suffix' => '+', 'label' => 'Sample']]],
    ];

    $page = Page::query()->create([
        'title' => 'Wave 2 render',
        'slug' => 'wave-2-render',
        'status' => 'draft',
        'meta_title' => 'Wave 2',
        'meta_description' => 'Wave-2 block matrix.',
        'blocks' => collect($samples)->map(fn ($data, $type): array => ['type' => $type, 'data' => $data])->values()->all(),
    ]);

    $html = app(PageRenderer::class)->render($page)->render();

    expect($html)->toContain('Alpha content')                       // B5
        ->toContain('Kickoff')                                       // B6
        ->toContain('Wave-2 question?')                              // B7
        ->toContain('application/ld+json')                           // B7 FAQPage schema
        ->toContain('recommended')                                   // B8 highlight chip
        ->toContain('A two line hook here.')                         // B9
        ->toContain('Gallery slot')                                  // C1
        ->toContain('Slide one')                                     // C2
        ->toContain('Cinematic quote')                               // C3
        ->toContain('Video title')                                   // C4 facade
        ->toContain('only once formally held')                       // C5 honest zero-state
        ->toContain('being curated')                                 // D1 zero-state
        ->toContain('as of')                                         // D2
        ->toContain('As of Aug 2026');                               // D3 as-of line
});

it('keeps exactly one H1 with wave-2 blocks present', function (): void {
    $page = Page::query()->create([
        'title' => 'H1 guard',
        'slug' => 'h1-guard-wave2',
        'status' => 'draft',
        'meta_title' => 'H1 guard',
        'meta_description' => 'Single H1 across the wave-2 matrix.',
        'blocks' => [
            ['type' => 'hero', 'data' => ['headline' => 'Lead hero', 'ctas' => []]],
            ['type' => 'faq', 'data' => ['heading' => 'FAQ H2 only', 'items' => [['q' => 'Q', 'a' => 'A']]]],
            ['type' => 'story_pillars', 'data' => ['items' => [['title' => 'T', 'hook' => 'H']]]],
            ['type' => 'tabs', 'data' => ['items' => [['label' => 'Tab', 'content_html' => '<p>C</p>']]]],
        ],
    ]);

    $html = app(PageRenderer::class)->render($page)->render();

    expect(preg_match_all('/<h1[\s>]/', $html))->toBe(1);
});

it('feeds module blocks from real data once modules publish', function (): void {
    $unit = HousingUnit::factory()->published()->verified()->create([
        'city_id' => City::query()->where('slug', 'gurugram')->value('id'),
        'name' => 'The Cypress House',
    ]);

    // The city page's housing section renders real inventory (module-fed
    // block behavior through the city template).
    $html = $this->get('/cities/gurugram')->getContent();

    expect($html)->toContain('The Cypress House')
        ->toContain('Sewa Verified');
});
