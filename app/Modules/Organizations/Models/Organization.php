<?php

namespace App\Modules\Organizations\Models;

use App\Models\User;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Client company (03-technical-specs/03-database-schema.md §1).
 * Portal tenancy root: client-manager / client-employee users attach
 * through organization_users with role_in_org (manager|employee|billing).
 *
 * NOTE: pivot rows must be created through OrganizationUser::create()
 * (generates the pivot ULID + timestamps) — the raw query-builder
 * attach() cannot populate the pivot table's ULID primary key.
 */
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    use HasUlids;

    protected $fillable = [
        'name',
        'slug',
        'industry',
        'gstin',
        'pan',
        'billing_address',
        'status',
        'notes',
        'crm_owner_user_id',
    ];

    protected static function newFactory(): Factory
    {
        return OrganizationFactory::new();
    }

    protected function casts(): array
    {
        return [
            'billing_address' => 'array',
        ];
    }

    /** Portal members of this organization (04-modules/04-client-portal.md). */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_users')
            ->withPivot('role_in_org', 'invited_by', 'joined_at');
    }

    /** Account manager (CRM owner) — a staff user. */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'crm_owner_user_id');
    }

    public function scopeWhereSlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }
}
