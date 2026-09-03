<?php

use App\Modules\Cms\Enums\MenuItemType;
use App\Modules\Cms\Enums\PageType;
use App\Modules\Cms\Models\Menu;
use App\Modules\Cms\Models\MenuItem;
use App\Modules\Cms\Models\Page;
use Database\Seeders\CmsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed([RolesAndPermissionsSeeder::class, SettingsSeeder::class, CmsSeeder::class]);
});

it('flags menu items when their target page is deleted (menu integrity)', function (): void {
    $page = Page::query()->where('slug', 'about')->first();
    $item = MenuItem::query()
        ->where('type', MenuItemType::Page->value)
        ->where('ref_id', $page->getKey())
        ->first();

    expect($item->flagged)->toBeFalse();

    $page->delete();

    expect($item->fresh()->flagged)->toBeTrue();
});

it('drops flagged items from the public render (never dead links)', function (): void {
    $page = Page::query()->where('slug', 'about')->first();
    $page->delete();

    // treeSafe cache was flushed by the observer; / renders without /about.
    $html = $this->get('/')->getContent();

    expect($html)->not->toContain('href="/about"')
        ->toContain('href="/"');
});

it('resolves page menu items through the page, not a frozen URL', function (): void {
    $page = Page::query()->where('slug', 'about')->first();
    $item = MenuItem::query()->where('ref_id', $page->getKey())->where('type', 'page')->first();

    $page->update(['type' => PageType::Landing, 'status' => $page->status]);
    $page->refresh();

    expect($item->href())->toBe('/p/about');
});

it('seeds exactly one menu per location', function (): void {
    expect(Menu::query()->count())->toBe(2)
        ->and(Menu::query()->where('location', 'header')->count())->toBe(1)
        ->and(Menu::query()->where('location', 'footer')->count())->toBe(1);
});
