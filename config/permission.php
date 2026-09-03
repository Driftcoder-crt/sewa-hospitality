<?php

use App\Models\Permission;
use App\Models\Role;

/*
|--------------------------------------------------------------------------
| Spatie laravel-permission configuration (v6)
|--------------------------------------------------------------------------
| Role/permission matrix owner: 03-technical-specs/03-database-schema.md §1
| (10 seeded roles) + 04-modules/00-module-system.md permission matrix.
|
| NOTE: our own migration extends the `roles` table with `slug` and
| `display_name` columns (admin panel ergonomics). The package never
| touches those columns — they are read by our models/policies only.
*/

return [

    'tables' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        'role_pivot_key' => null, // default 'role_id'
        'permission_pivot_key' => null, // default 'permission_id'
        // ulidMorphs('model') in our migration creates model_id + model_type —
        // the morph key is the FULL COLUMN NAME ('model_id'), not the base.
        'model_morph_key' => 'model_id',
        'team_foreign_key' => 'team_id',
    ],

    /*
     | When set to true the package registers its own permission check method
     | on the Gate. We rely on it plus Gate::before (super-admin bypass) in
     | AppServiceProvider.
     */
    'register_permission_check_method' => true,

    'register_octane_reset_listener' => false,

    // ULID-keyed role/permission models (03-database-schema §1) — our
    // migrations own the schema with ulid() PKs, and the vendor classes
    // never generate ids on their own.
    'models' => [
        'permission' => Permission::class,
        'role' => Role::class,
    ],

    // Teams/Organizations permission scoping stays off: portal tenancy is
    // enforced by organization_users + policies, not by permission teams.
    'teams' => false,

    'display_role_in_exception' => false,

    'enable_wildcard_permission' => false,

    'cache' => [
        'expiration_time' => DateInterval::createFromDateString('24 hours'),
        'key' => 'spatie.permission.cache',
        // Explicit database store — the platform has no other cache backend
        // (locked decision, see config/database.php).
        'store' => 'database',
    ],

];
