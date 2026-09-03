<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationUser;
use Illuminate\Database\Seeder;

/**
 * STAGING/LOCAL ONLY — fixtures for UAT only (03-technical-specs/
 * 13-testing-qa.md §6 "seed:demo (staging)"). Hard-blocked in
 * production; the production path is `composer seed:prod-minimal`.
 *
 * Seeds the prod-minimal baseline first, then one demo client
 * organization plus one staff user per seeded role and the two portal
 * roles as Meridian client contacts. Names are UAT fixtures, clearly
 * .test addresses — never shipped to production.
 */
final class DemoSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'Sewa-Demo-2026!';

    /**
     * role 'ops' is the optional 11th role (04-modules/05-admin-panel.md
     * §5; worklog conflict #6): it is assigned only when
     * RolesAndPermissionsSeeder actually seeded it — never invented here.
     */
    private const FIXTURE_USERS = [
        ['email' => 'superadmin@sewahospitality.test', 'name' => 'Meera Iyer', 'role' => 'super-admin'],
        ['email' => 'admin@sewahospitality.test', 'name' => 'Rohit Malhotra', 'role' => 'admin'],
        ['email' => 'editor@sewahospitality.test', 'name' => 'Sana Qureshi', 'role' => 'editor'],
        ['email' => 'author@sewahospitality.test', 'name' => 'Devika Nair', 'role' => 'author'],
        ['email' => 'hr@sewahospitality.test', 'name' => 'Priya Sharma', 'role' => 'hr-manager'],
        ['email' => 'recruiter@sewahospitality.test', 'name' => 'Arjun Mehta', 'role' => 'recruiter'],
        ['email' => 'finance@sewahospitality.test', 'name' => 'Kavya Reddy', 'role' => 'finance'],
        ['email' => 'consultant@sewahospitality.test', 'name' => 'Vikram Singh', 'role' => 'consultant'],
        ['email' => 'ops@sewahospitality.test', 'name' => 'Nikhil Verma', 'role' => 'ops'],
        ['email' => 'manager@sewahospitality.test', 'name' => 'Sarah Thompson', 'role' => 'client-manager'],
        ['email' => 'employee@sewahospitality.test', 'name' => 'Daniel Kim', 'role' => 'client-employee'],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('DemoSeeder is forbidden in production — run seed:prod-minimal.');
        }

        $this->call(ProdMinimalSeeder::class);

        $organization = Organization::factory()->create([
            'name' => 'Meridian Technologies',
            'slug' => 'meridian-technologies-demo',
            'industry' => 'Information Technology',
            'status' => 'active',
        ]);

        $users = [];

        foreach (self::FIXTURE_USERS as $fixture) {
            $user = User::factory()->create([
                'name' => $fixture['name'],
                'email' => $fixture['email'],
                'password' => self::DEMO_PASSWORD,
            ]);

            if ($fixture['role'] === 'ops') {
                if (Role::query()->where('name', 'ops')->where('guard_name', 'web')->exists()) {
                    $user->assignRole('ops');
                }
            } else {
                $user->assignRole($fixture['role']);
            }

            $users[$fixture['email']] = $user;
        }

        // Portal membership rows go through the OrganizationUser model so
        // the pivot ULID + timestamps are generated (see model docblock).
        OrganizationUser::create([
            'organization_id' => $organization->id,
            'user_id' => $users['manager@sewahospitality.test']->id,
            'role_in_org' => 'manager',
            'invited_by' => null,
            'joined_at' => now(),
        ]);

        OrganizationUser::create([
            'organization_id' => $organization->id,
            'user_id' => $users['employee@sewahospitality.test']->id,
            'role_in_org' => 'employee',
            'invited_by' => null,
            'joined_at' => now(),
        ]);
    }
}
