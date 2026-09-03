<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use InvalidArgumentException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles & permissions seed (03-technical-specs/03-database-schema.md §13,
 * 04-modules/00-module-system.md §5, 04-modules/05-admin-panel.md §5).
 *
 * Permission naming: '{module}.{action}'. The 11 seeded roles are the 10
 * canonical ones plus the optional `ops` role from the admin-panel doc.
 *
 * super-admin deliberately holds ZERO permissions: AppServiceProvider's
 * Gate::before returns true for super-admin ahead of any other check, so
 * the role passes every gate without matrix rows. Least privilege by
 * construction — new permissions never need to be re-granted to it, and
 * the bypass surface is a single audited closure in the provider.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * The full permission matrix, module by module ('{module}.{action}').
     *
     * @var array<string, list<string>>
     */
    protected array $modules = [
        'cms' => ['view', 'create', 'update', 'delete', 'publish'],
        'services' => ['view', 'create', 'update', 'delete', 'publish'],
        'cities' => ['view', 'create', 'update', 'delete', 'publish'],
        'housing' => ['view', 'create', 'update', 'delete', 'publish', 'verify'],
        'blog' => ['view', 'create', 'update', 'delete', 'publish'],
        'testimonials' => ['view', 'create', 'update', 'delete', 'publish'],
        'csr' => ['view', 'create', 'update', 'delete', 'publish'],
        'media' => ['view', 'upload', 'delete'],
        'leads' => ['view', 'pii.view', 'update', 'assign', 'export'],
        'careers' => ['view', 'pii.view', 'update', 'create', 'delete', 'export'],
        'hr' => ['view', 'update', 'create', 'delete'],
        'portal' => ['view', 'manage'],
        'billing' => ['view', 'manage'],
        'i18n' => ['view', 'manage'],
        'ai' => ['view', 'manage'],
        'system' => ['view', 'manage'],
        'ops' => ['view'],
    ];

    /**
     * Role map: slug + display_name + permission spec. `Module.*` entries
     * expand to every action of the module; `except` grants everything
     * minus the listed names. Created with updateOrCreate by name so the
     * seeder is idempotent across environments.
     *
     * @var array<string, array{slug: string, display_name: string, permissions?: list<string>, except?: list<string>}>
     */
    protected array $roleMap = [
        'super-admin' => [
            'slug' => 'super-admin',
            'display_name' => 'Super Admin',
            // No permissions seeded — the Gate::before bypass in
            // AppServiceProvider covers every ability for this role.
            'permissions' => [],
        ],
        'admin' => [
            'slug' => 'admin',
            'display_name' => 'Administrator',
            // Everything except the guarded manage domains: destructive
            // system settings, AI provider management and locale lifecycle.
            'except' => ['system.manage', 'ai.manage', 'i18n.manage'],
        ],
        'editor' => [
            'slug' => 'editor',
            'display_name' => 'Editor',
            'permissions' => [
                'cms.*', 'services.*', 'cities.*', 'housing.*',
                'blog.*', 'testimonials.*', 'csr.*', 'media.*',
            ],
        ],
        'author' => [
            'slug' => 'author',
            'display_name' => 'Author',
            'permissions' => ['blog.view', 'blog.create', 'blog.update', 'media.view', 'media.upload'],
        ],
        'hr-manager' => [
            'slug' => 'hr-manager',
            'display_name' => 'HR Manager',
            'permissions' => ['hr.*', 'careers.*', 'cms.view', 'media.view', 'media.upload', 'portal.view'],
        ],
        'recruiter' => [
            'slug' => 'recruiter',
            'display_name' => 'Recruiter',
            // Pipeline rights without posting lifecycle (create/delete are
            // hr-manager's — 06-hr doc §4 permissions).
            'permissions' => ['careers.view', 'careers.pii.view', 'careers.update', 'careers.export', 'media.view'],
        ],
        'finance' => [
            'slug' => 'finance',
            'display_name' => 'Finance',
            // M5: billing.* now covers quotes/invoices/payments/orgs;
            // portal.view gives read coordination on moves.
            'permissions' => ['billing.*', 'leads.view', 'portal.view'],
        ],
        'consultant' => [
            'slug' => 'consultant',
            'display_name' => 'Consultant',
            'permissions' => ['leads.view', 'leads.update', 'portal.view'],
        ],
        'client-manager' => [
            'slug' => 'client-manager',
            'display_name' => 'Client Manager',
            'permissions' => ['portal.view', 'billing.view'],
        ],
        'client-employee' => [
            'slug' => 'client-employee',
            'display_name' => 'Client Employee',
            'permissions' => ['portal.view'],
        ],
        'ops' => [
            'slug' => 'ops',
            'display_name' => 'Operations',
            'permissions' => ['ops.view', 'system.view'],
        ],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $all = $this->allPermissionNames();

        foreach ($all as $name) {
            Permission::updateOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach ($this->roleMap as $name => $definition) {
            $role = Role::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['slug' => $definition['slug'], 'display_name' => $definition['display_name']],
            );

            $role->syncPermissions($this->resolveRolePermissions($definition, $all));
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Every matrix permission name, in seed order.
     *
     * @return list<string>
     */
    protected function allPermissionNames(): array
    {
        $names = [];

        foreach ($this->modules as $module => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        return $names;
    }

    /**
     * Expand one role's spec into concrete permission names. Unknown
     * modules or names throw immediately (error-locks doctrine: a typo
     * in the matrix must never seed silently).
     *
     * @param  array{permissions?: list<string>, except?: list<string>}  $definition
     * @param  list<string>  $all
     * @return list<string>
     */
    protected function resolveRolePermissions(array $definition, array $all): array
    {
        if (array_key_exists('except', $definition)) {
            $unknown = array_diff($definition['except'], $all);

            if ($unknown !== []) {
                throw new InvalidArgumentException('Unknown permission(s) in except list: '.implode(', ', $unknown));
            }

            return array_values(array_diff($all, $definition['except']));
        }

        $granted = [];

        foreach ($definition['permissions'] ?? [] as $entry) {
            if (str_ends_with($entry, '.*')) {
                $module = substr($entry, 0, -2);

                if (! isset($this->modules[$module])) {
                    throw new InvalidArgumentException("Unknown permission module [{$module}] in role map.");
                }

                foreach ($this->modules[$module] as $action) {
                    $granted[] = "{$module}.{$action}";
                }

                continue;
            }

            if (! in_array($entry, $all, true)) {
                throw new InvalidArgumentException("Unknown permission [{$entry}] in role map.");
            }

            $granted[] = $entry;
        }

        return array_values(array_unique($granted));
    }
}
