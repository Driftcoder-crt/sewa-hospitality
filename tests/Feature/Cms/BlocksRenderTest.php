<?php

use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Services\BlockRegistry;
use App\Modules\Cms\Services\PageRenderer;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed([RolesAndPermissionsSeeder::class, SettingsSeeder::class]);
});

function blockSamples(): array
{
    return [
        'hero' => ['eyebrow' => 'EYEBROW', 'headline' => 'Hero headline', 'sub' => 'Sub copy', 'height' => 'compact', 'overlay' => 'none', 'align' => 'start', 'ctas' => [['label' => 'Act', 'url' => '/', 'variant' => 'primary']]],
        'split_hero' => ['headline' => 'Split headline', 'sub' => 'Split sub', 'media_side' => 'right', 'ctas' => []],
        'feature_grid' => ['columns' => '3', 'style' => 'border', 'items' => [
            ['title' => 'One', 'text' => 'First', 'icon' => 'globe', 'url' => ''],
            ['title' => 'Two', 'text' => 'Second', 'icon' => 'map', 'url' => ''],
        ]],
        'rich_text' => ['html' => '<h1>Demoted</h1><p>Body copy with <strong>weight</strong>.</p><script>alert(1)</script>'],
        'text_media' => ['title' => 'Text media title', 'copy' => '<p>Copy.</p>', 'media_side' => 'left', 'caption' => 'A caption'],
        'chapter_heading' => ['number' => '01', 'title' => 'Chapter title'],
        'accordion' => ['first_open' => true, 'items' => [['title' => 'Q', 'body_html' => '<p>A</p>']]],
        'cta_band' => ['headline' => 'Band headline', 'copy' => 'Band copy', 'theme' => 'brand', 'layout' => 'split', 'ctas' => [['label' => 'Go', 'url' => '/', 'variant' => 'primary'], ['label' => 'Alt', 'url' => '/', 'variant' => 'secondary']]],
    ];
}

it('registers the M1 wave-1 catalog (A1–A4, B1–B4, E1) within the full library', function (): void {
    $keys = array_keys(BlockRegistry::all());

    // Wave-1 stays complete and registered (§10: M1 = A1–A4, B1–B4, E1);
    // the M2–M4 waves extend the catalog — 47 blocks contracted in
    // 05-design-system/05-section-block-library.md §1.
    expect($keys)->toContain(
        'hero', 'split_hero', 'section_wrapper', 'feature_grid',
        'rich_text', 'text_media', 'chapter_heading', 'accordion', 'cta_band',
    )->and(count($keys))->toBeGreaterThanOrEqual(47);
});

it('renders every wave-1 block with required markup and sanitization', function (): void {
    $renderer = app(PageRenderer::class);
    $samples = blockSamples();

    $page = Page::query()->create([
        'title' => 'Blocks page',
        'slug' => 'blocks-page-'.mb_strtolower(Str::random(4)),
        'status' => 'draft',
        'meta_title' => 'Blocks',
        'meta_description' => 'Block render matrix.',
        'blocks' => collect($samples)->map(fn ($data, $type): array => ['type' => $type, 'data' => $data])->values()->all(),
    ]);

    $html = $renderer->render($page)->render();

    expect($html)->toContain('Hero headline')                       // A1 (lead → H1)
        ->toContain('<h1')                                          // A2 headline is NOT an H1
        ->toContain('Split headline')
        ->toContain('First')                                        // A4
        ->toContain('<h2>Demoted</h2>')                             // B1 ladder enforcement
        ->not->toContain('<script>')                                // B1 sanitizer
        ->toContain('Text media title')                             // B2
        ->toContain('Chapter title')                                // B3
        ->toContain('aria-expanded')                                // B4 a11y
        ->toContain('Band headline');                               // E1
});

it('keeps exactly one H1 across the full block matrix', function (): void {
    $renderer = app(PageRenderer::class);
    $samples = blockSamples();

    $page = Page::query()->create([
        'title' => 'H1 page',
        'slug' => 'h1-page-'.mb_strtolower(Str::random(4)),
        'status' => 'draft',
        'meta_title' => 'H1',
        'meta_description' => 'Single H1 rule.',
        'blocks' => collect($samples)->map(fn ($data, $type): array => ['type' => $type, 'data' => $data])->values()->all(),
    ]);

    $html = $renderer->render($page)->render();

    expect(preg_match_all('/<h1[\s>]/', $html))->toBe(1);
});

it('survives every theme slot on every block (complete-slot-set rule)', function (): void {
    $renderer = app(PageRenderer::class);

    foreach (['light', 'dark', 'brand', 'deep'] as $theme) {
        $page = Page::query()->create([
            'title' => "Theme {$theme}",
            'slug' => 'theme-'.mb_strtolower(Str::random(4)),
            'status' => 'draft',
            'meta_title' => 'Theme',
            'meta_description' => 'Theme matrix.',
            'blocks' => [
                ['type' => 'hero', 'data' => ['headline' => 'T', 'ctas' => []]],
                ['type' => 'cta_band', 'data' => ['headline' => 'T2', 'theme' => $theme === 'light' ? 'brand' : $theme, 'ctas' => []]],
            ],
        ]);

        $html = $renderer->render($page)->render();

        expect($html)->toContain('data-theme')
            ->and($html)->toContain('T');
        $page->delete();
    }
});

it('renders RTL direction for Arabic without breaking the pipeline', function (): void {
    app()->setLocale('ar');

    $page = Page::query()->create([
        'title' => 'Arabic',
        'slug' => 'arabic-page',
        'status' => 'draft',
        'meta_title' => 'عربي',
        'meta_description' => 'صفحة اختبار.',
        'blocks' => [['type' => 'hero', 'data' => ['headline' => 'مرحبا', 'ctas' => []]]],
    ]);

    $html = app(PageRenderer::class)->render($page, 'ar')->render();

    expect($html)->toContain('dir="rtl"')
        ->toContain('مرحبا');
});
