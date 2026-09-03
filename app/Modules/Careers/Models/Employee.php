<?php

namespace App\Modules\Careers\Models;

use App\Models\Media;
use App\Modules\Careers\Enums\Department;
use App\Modules\Careers\Enums\EmployeeStatus;
use App\Modules\Cities\Models\City;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An employee — the people registry (06-hr doc §1/§4.3). is_public is
 * the marketing visibility switch: leadership grids (D6), About-page
 * team section and /team/{person} profiles read ONLY public rows.
 */
class Employee extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'user_id', 'employee_code', 'full_name', 'designation', 'department',
        'joined_at', 'employment_type', 'office_city_id', 'is_public', 'bio',
        'credentials', 'photo_media_id', 'manager_employee_id', 'status', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'department' => Department::class,
            'status' => EmployeeStatus::class,
            'joined_at' => 'date',
            'is_public' => 'boolean',
            'credentials' => 'array',
            'sort' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'employee_code';
    }

    /* ── Relations ─────────────────────────────────────────────────── */

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_media_id');
    }

    public function officeCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'office_city_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_employee_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(self::class, 'manager_employee_id');
    }

    /* ── Public profile helpers ────────────────────────────────────── */

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /** Languages spoken, from credentials.languages (public card chip). */
    public function languages(): array
    {
        $languages = $this->credentials['languages'] ?? [];

        return is_array($languages) ? array_values(array_filter($languages)) : [];
    }

    /** Public profile URL (tap-to-bio page — 06-hr §3). */
    public function publicPath(): string
    {
        return '/team/'.$this->employee_code;
    }
}
