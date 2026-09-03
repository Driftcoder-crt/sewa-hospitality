<?php

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationUser;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('attaches users with their portal roles preserved on the pivot', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->create();
    $employee = User::factory()->create();

    // Membership rows go through the pivot model so the ULID id and
    // timestamps are generated (raw attach() would leave the PK NULL).
    OrganizationUser::create([
        'organization_id' => $organization->id,
        'user_id' => $manager->id,
        'role_in_org' => 'manager',
        'joined_at' => now(),
    ]);

    OrganizationUser::create([
        'organization_id' => $organization->id,
        'user_id' => $employee->id,
        'role_in_org' => 'employee',
        'joined_at' => now(),
    ]);

    expect($organization->users()->count())->toBe(2);

    $freshManager = $organization->users()
        ->where('users.email', $manager->email)
        ->first();
    $freshEmployee = $organization->users()
        ->where('users.email', $employee->email)
        ->first();

    expect($freshManager->pivot->role_in_org)->toBe('manager')
        ->and($freshManager->pivot->joined_at)->not->toBeNull()
        ->and($freshEmployee->pivot->role_in_org)->toBe('employee')
        // The contract relation on the user side sees the same membership.
        ->and($manager->organizations()->count())->toBe(1);
});

it('rejects a duplicate organization membership', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    OrganizationUser::create([
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'role_in_org' => 'manager',
        'joined_at' => now(),
    ]);

    // unique(organization_id, user_id) must hold at the DB level.
    expect(fn () => DB::table('organization_users')->insert([
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'role_in_org' => 'employee',
        'joined_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('finds organizations by slug and resolves the crm owner', function () {
    $owner = User::factory()->create();
    $organization = Organization::factory()->create([
        'crm_owner_user_id' => $owner->id,
    ]);

    expect(Organization::query()->whereSlug($organization->slug)->first()?->is($organization))->toBeTrue()
        ->and($organization->owner()->first()?->is($owner))->toBeTrue();
});
