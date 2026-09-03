<?php

use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds the eleven roles of the permission matrix', function (): void {
    // 10 canonical roles (03-technical-specs/03-database-schema.md §1) plus
    // the optional `ops` role from 04-modules/05-admin-panel.md §5.
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::query()->count())->toBe(11)
        ->and(Role::query()->where('name', 'ops')->exists())->toBeTrue();
});

it('seeds every role under the web guard', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::query()->where('guard_name', 'web')->count())->toBe(11);
});

it('gives editors the content toolbox but never lead PII', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $editor = Role::findByName('editor', 'web');

    expect($editor->hasPermissionTo('cms.publish'))->toBeTrue()
        ->and($editor->hasPermissionTo('leads.pii.view'))->toBeFalse();
});

it('keeps client employees scoped to the portal', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $employee = Role::findByName('client-employee', 'web');

    expect($employee->hasPermissionTo('portal.view'))->toBeTrue()
        ->and($employee->hasPermissionTo('billing.view'))->toBeFalse();
});

it('leaves super-admin without matrix rows on purpose', function (): void {
    // Gate::before in AppServiceProvider covers super-admin; the role must
    // stay empty so the bypass surface remains a single audited closure.
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::findByName('super-admin', 'web')->permissions()->count())->toBe(0);
});
