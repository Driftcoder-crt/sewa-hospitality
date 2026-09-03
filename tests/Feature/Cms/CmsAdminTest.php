<?php

use App\Livewire\Admin\CommandPalette;
use App\Models\User;
use App\Modules\Cms\Livewire\MenusEditor;
use App\Modules\Cms\Livewire\PageEditor;
use App\Modules\Cms\Livewire\PagesTable;
use App\Modules\Cms\Livewire\RedirectsManager;
use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Models\Redirect;
use App\Modules\Cms\Services\RevisionManager;
use Database\Seeders\CmsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed([RolesAndPermissionsSeeder::class, SettingsSeeder::class, CmsSeeder::class]);
});

function staffWith2fa(array $roles): void
{
    actingAsStaff($roles);
    auth()->user()->forceFill(['two_factor_confirmed_at' => now()])->save();
}

it('lets a confirmed editor open the pages table', function (): void {
    staffWith2fa(['editor']);

    Livewire::test(PagesTable::class)
        ->assertOk()
        ->assertSee('Corporate relocation', false)
        ->assertSee('About Sewa Hospitality', false);
});

it('denies the pages table to non-editor staff', function (): void {
    staffWith2fa(['recruiter']);

    Livewire::test(PagesTable::class)->assertForbidden();
});

it('lets an editor open the page editor canvas', function (): void {
    staffWith2fa(['editor']);
    $page = Page::query()->where('slug', 'home')->first();

    Livewire::test(PageEditor::class, ['page' => $page])
        ->assertOk()
        ->assertSet('slug', 'home')
        ->assertSee('Blocks', false)
        ->assertSee('SEO', false);
});

it('autosaves editor drafts and records revisions', function (): void {
    staffWith2fa(['editor']);
    $page = Page::query()->where('slug', 'about')->first();

    Livewire::test(PageEditor::class, ['page' => $page])
        ->set('meta_title', 'About Sewa Hospitality — honest mobility')
        ->call('autosave', app(RevisionManager::class))
        ->assertSet('autosaveState', 'saved');

    expect($page->fresh()->meta_title)->toBe('About Sewa Hospitality — honest mobility')
        ->and($page->revisions()->count())->toBeGreaterThanOrEqual(1);
});

it('offers and creates the 301 when a published slug changes', function (): void {
    staffWith2fa(['editor']);
    $page = Page::query()->where('slug', 'contact')->first();

    Livewire::test(PageEditor::class, ['page' => $page])
        ->set('slug', 'contact-us')
        ->assertSet('slugChanged', true)
        ->assertSet('addRedirect', true)
        ->call('save', app(RevisionManager::class));

    expect(Redirect::query()->where('from', '/contact')->where('to', '/contact-us')->exists())->toBeTrue();
});

it('denies the redirects screen to editors but admits admins', function (): void {
    staffWith2fa(['editor']);
    Livewire::test(RedirectsManager::class)->assertForbidden();

    staffWith2fa(['admin']);
    Livewire::test(RedirectsManager::class)->assertOk();
});

it('admits editors to the menus screen and persists item edits', function (): void {
    staffWith2fa(['editor']);

    Livewire::test(MenusEditor::class)
        ->set('location', 'header')
        ->assertOk()
        ->assertSee('Label', false);
});

it('scopes the command palette results to the operator role', function (): void {
    staffWith2fa(['editor']);
    $component = Livewire::test(CommandPalette::class);

    $results = $component->instance()->results();
    // Editor holds cms/blog/testimonials/csr permissions + the
    // i18n.manage gate + 2FA access — and NOTHING else: no Intake,
    // People, Portal ops, Billing or AI surfaces in the palette.
    expect(collect($results)->pluck('group')->unique()->values()->all())
        ->toEqualCanonicalizing(['Overview', 'Content', 'Editorial', 'Trust', 'I18n', 'System', 'Pages']);

    // Portal role never sees page rows in the palette.
    $portal = User::factory()->create();
    $portal->syncRoles(['client-employee']);
    test()->actingAs($portal, 'web');

    $scoped = Livewire::test(CommandPalette::class)->instance()->results();
    expect(collect($scoped)->pluck('group')->contains('Pages'))->toBeFalse();
});
