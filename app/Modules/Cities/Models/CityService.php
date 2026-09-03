<?php

namespace App\Modules\Cities\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * city_services junction (03-technical-specs/03-database-schema.md §3):
 * a service listed on a city page MUST have a row here — no optimistic
 * coverage. Pivot model because sync()/attach() never generate the
 * migration's ULID primary key; HasUlids does (same pattern as
 * Organizations\OrganizationUser).
 */
class CityService extends Pivot
{
    use HasUlids;

    protected $table = 'city_services';

    protected $fillable = [
        'city_id',
        'service_id',
        'note',
    ];
}
