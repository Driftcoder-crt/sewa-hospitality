<?php

/*
|--------------------------------------------------------------------------
| Pest configuration
|--------------------------------------------------------------------------
| Every test binds to the Laravel TestCase (boots the full application,
| including the rate limiters, gates and Fortify views from
| AppServiceProvider). Shared cross-suite helpers live in this file as
| function_exists-guarded plain functions.
*/

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Tests\TestCase;

uses(TestCase::class)->in('Unit', 'Feature');

// No Node build exists in CI's backend job or any bare container — the
// 13-testing-qa §3 gate-1 contract demands tests run with ZERO build
// artifacts. Render views with a stubbed Vite response everywhere.

if (! function_exists('actingAsStaff')) {
    /**
     * Create a staff user, assign the given Spatie roles, and authenticate
     * as them on the web guard. Returns the TestCase so calls stay chainable:
     *
     *     actingAsStaff(['editor'])->get('/admin/pages');
     *
     * Portal actors use actingAsStaff(['client-manager']) — role strings
     * come from the seed matrix in 03-technical-specs/03-database-schema.md §1.
     *
     * @param  array<int, string>  $roles
     */
    function actingAsStaff(array $roles = ['admin'], string $guard = 'web'): TestCase
    {
        // Idempotent (updateOrCreate-based) — safe even when the consuming
        // test already seeded, and self-sufficient when it didn't.
        test()->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();

        $user->syncRoles($roles);

        // admin/super-admin are parked on the 2FA bootstrap screen until
        // enrolled (EnsureTwoFactorConfirmed) — staff helpers act as an
        // already-enrolled admin unless a test explicitly covers 2FA.
        if ($user->hasAnyRole(config('sewa.admin.two_factor_roles', ['super-admin', 'admin']))) {
            User::query()->whereKey($user->getKey())->update(['two_factor_confirmed_at' => now()]);
            $user->refresh();
        }

        return test()->actingAs($user, $guard);
    }
}
