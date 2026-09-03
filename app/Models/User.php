<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Modules\Careers\Models\AuthorProfile;
use App\Modules\Careers\Models\Employee;
use App\Modules\Organizations\Models\Organization;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Platform user (03-technical-specs/03-database-schema.md §1).
 * Spatie roles via model_has_roles; portal organization membership
 * via organization_users (role_in_org: manager|employee|billing).
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use HasUlids;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'locale',
        'timezone',
        'status',
        'avatar_media_id',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => UserStatus::class,
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /** Client companies this user belongs to (portal, tenant scope root). */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(
            Organization::class,
            'organization_users',
        )->withPivot(['role_in_org', 'invited_by', 'joined_at']);
    }

    public function avatarMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'avatar_media_id');
    }

    /** Byline profile (04-modules/07-blog-news.md §2) — one per user. */
    public function authorProfile(): HasOne
    {
        return $this->hasOne(AuthorProfile::class, 'user_id');
    }

    /** Staff record behind a public /team/{code} page (06-hr §3). */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(config('sewa.staff_roles', []));
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }
}
