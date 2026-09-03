<?php

namespace App\Modules\Organizations\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Portal membership pivot (03-technical-specs/03-database-schema.md §1):
 * organization_id × user_id (unique) with role_in_org
 * (manager|employee|billing), optional invited_by and joined_at.
 *
 * Deliberately a full Model (not a Pivot subclass): the contract's
 * User::organizations() uses a plain belongsToMany, so membership rows
 * are always created through this model — it generates the ULID
 * primary key and timestamps that a raw attach() would leave NULL.
 */
class OrganizationUser extends Model
{
    use HasUlids;

    protected $table = 'organization_users';

    protected $fillable = [
        'organization_id',
        'user_id',
        'role_in_org',
        'invited_by',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
