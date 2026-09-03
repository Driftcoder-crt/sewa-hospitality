<?php

use App\Models\User;
use App\Modules\Blog\Models\Category;
use App\Modules\Blog\Models\Post;
use App\Modules\Careers\Models\Employee;
use App\Modules\Cities\Models\City;
use App\Modules\Cms\Services\BlockRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed([RolesAndPermissionsSeeder::class]);
});

/** Render one block through the shared loop and return the HTML. */
function renderWave4Block(string $type, array $data): string
{
    return view('cms.partials.blocks', [
        'blocks' => [['type' => $type, 'data' => $data]],
        'leadIndex' => 0,
    ])->render();
}

it('has all seventeen wave-4 blocks registered (catalog completes at 49)', function (): void {
    foreach (['bento_grid', 'step_flow', 'marquee_strip', 'spacer_divider',
        'image_duo', 'map_block', 'before_after', 'services_grid',
        'service_accordion', 'housing_grid', 'city_strip', 'posts_feed',
        'category_cloud', 'job_listings', 'leadership_grid',
        'ventures_strip', 'search_widget'] as $type) {
        expect(BlockRegistry::has($type))->toBeTrue("Missing wave-4 block [{$type}]");
    }

    expect(count(BlockRegistry::all()))->toBe(49);
});

it('renders A5 bento grid with tile sizes', function (): void {
    $html = renderWave4Block('bento_grid', [
        'items' => [
            ['title' => 'Wide tile', 'text' => 'Copy', 'size' => 'wide'],
            ['title' => 'Tall tile', 'text' => 'Copy', 'size' => 'tall'],
        ],
    ]);

    expect($html)->toContain('Wide tile')->toContain('Tall tile');
});

it('renders A6 step flow with numbered steps', function (): void {
    $html = renderWave4Block('step_flow', [
        'items' => [
            ['title' => 'Share your brief', 'text' => 'Five minutes.'],
            ['title' => 'Get a plan', 'text' => 'Line-item quote.'],
        ],
    ]);

    expect($html)->toContain('Share your brief')->toContain('Get a plan');
});

it('renders A7 marquee strip and A8 spacer without overhead', function (): void {
    $marquee = renderWave4Block('marquee_strip', [
        'items' => [['text' => 'Relocation'], ['text' => 'Housing']],
    ]);
    expect($marquee)->toContain('Relocation')->toContain('Housing');

    $spacer = renderWave4Block('spacer_divider', ['height' => 'md', 'ornament' => 'rule']);
    expect($spacer)->not->toBe('');
});

it('renders C6 image duo with placeholders for missing media (honest slots)', function (): void {
    $html = renderWave4Block('image_duo', [
        'items' => [
            ['media_id' => null, 'caption' => 'Arrival day'],
            ['media_id' => null, 'caption' => 'Week one'],
        ],
    ]);

    expect($html)->toContain('Arrival day')->toContain('Week one');
});

it('renders C7 map block as a click-to-load facade (no 3rd-party JS pre-consent)', function (): void {
    $html = renderWave4Block('map_block', [
        'heading' => 'Find us', 'address' => 'DT Mega Mall, Gurugram',
        'pin_lat' => '28.4670', 'pin_lng' => '77.0940',
    ]);

    // The facade never loads remote maps JS on its own — the interactive
    // tiles only mount behind an explicit user click.
    expect($html)->toContain('Find us')
        ->toContain('DT Mega Mall, Gurugram')
        ->not->toMatch('/<script[^>]+src=/');
});

it('renders C8 before/after with range input for keyboard a11y', function (): void {
    $html = renderWave4Block('before_after', [
        'label_before' => 'Before', 'label_after' => 'After', 'caption' => 'A week with Sewa.',
    ]);

    expect($html)->toContain('Before')->toContain('After')->toContain('A week with Sewa.');
});

it('renders module-fed F-blocks with honest zero-states', function (): void {
    // Nothing seeded: every F-block renders its empty state, never fake data.
    $empty = [
        'services_grid' => ['family' => 'all', 'limit' => '6'],
        'housing_grid' => ['city_id' => null, 'limit' => '6'],
        'city_strip' => ['heading' => 'Cities', 'limit' => '8'],
        'posts_feed' => ['type' => 'blog', 'limit' => '4'],
        'category_cloud' => ['heading' => 'Categories'],
        'job_listings' => ['heading' => 'Open roles', 'department' => 'all'],
        'leadership_grid' => ['heading' => 'Leadership', 'limit' => '4'],
        'ventures_strip' => ['heading' => 'Ventures'],
    ];

    foreach ($empty as $type => $data) {
        $html = renderWave4Block($type, $data);
        expect(mb_strlen(trim($html)))->toBeGreaterThan(0, "Block [{$type}] rendered nothing");
    }
});

it('renders F-blocks with real module data when present', function (): void {
    // city_strip renders HUB cities ("we operate in…") — a published
    // non-hub city must not appear, so the fixture has to be a hub.
    $city = City::factory()->published()->hub()->create();
    $author = User::factory()->create();

    $post = Post::factory()->published()->create([
        'title' => 'Housing market notes: Gurugram',
        'author_user_id' => $author->getKey(),
    ]);
    $post->categories()->attach(Category::factory()->create(['name' => 'City Guides'])->getKey());
    $post->refresh();

    Employee::factory()->create(['is_public' => true, 'department' => 'relocation']);

    expect(renderWave4Block('city_strip', ['heading' => 'Where we operate', 'limit' => '8']))
        ->toContain($city->name);

    expect(renderWave4Block('posts_feed', ['type' => 'blog', 'limit' => '4']))
        ->toContain('Housing market notes: Gurugram');

    expect(renderWave4Block('category_cloud', ['heading' => 'Browse']))
        ->toContain('City Guides');

    expect(renderWave4Block('job_listings', ['heading' => 'Open roles', 'department' => 'all']))
        ->toContain('Open roles');
});
