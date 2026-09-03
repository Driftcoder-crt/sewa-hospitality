<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('redirects guests from the admin dashboard to the login screen', function (): void {
    // M1 made '/' the public CMS home — the admin shell lives at /admin.
    $this->get('/admin')->assertRedirect(route('login'));
});

it('admits a staff editor with a confirmed device', function (): void {
    actingAsStaff(['editor']);
    auth()->user()->forceFill(['two_factor_confirmed_at' => now()])->save();

    $this->get('/admin')->assertOk();
});

it('parks a super-admin without confirmed 2fa on the security screen', function (): void {
    // 2FA is mandatory for super-admin/admin (05-security-reliability §1.1):
    // admin.2fa redirects them to the bootstrap page until enrolled.
    // (actingAsStaff acts as an ENROLLED admin — the unenrolled actor
    // under test is built by hand here.)
    $user = \App\Models\User::factory()->create();
    $user->syncRoles(['super-admin']);
    test()->actingAs($user, 'web');

    $this->get('/admin')->assertRedirect(route('admin.security'));
});

it('admits a super-admin once 2fa is confirmed', function (): void {
    actingAsStaff(['super-admin']);
    auth()->user()->forceFill(['two_factor_confirmed_at' => now()])->save();

    $this->get('/admin')->assertOk();
});

it('denies portal staff the admin surface', function (): void {
    // Gate 'access-admin' only admits config('sewa.staff_roles');
    // client-manager is a portal role and must never see the admin.
    actingAsStaff(['client-manager']);
    auth()->user()->forceFill(['two_factor_confirmed_at' => now()])->save();

    $this->get('/admin')->assertForbidden();
});

it('renders the 2fa bootstrap page for an unenrolled super-admin', function (): void {
    actingAsStaff(['super-admin']);

    $this->get(route('admin.security'))
        ->assertOk()
        ->assertSee('Two-factor authentication')
        ->assertSee('user:enable-2fa');
});
