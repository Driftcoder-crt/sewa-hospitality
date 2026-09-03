<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * ULID variant of Spatie's Role model (03-database-schema §1:
 * every Sewa row is ULID-keyed so roles/permissions are URL-exposable
 * and time-sortable). The ULID PK columns are OUR migration's schema;
 * HasUlids generates the id the vendor class would never supply.
 * Wired via config/permission.php → models.role.
 */
class Role extends SpatieRole
{
    use HasUlids;
}
