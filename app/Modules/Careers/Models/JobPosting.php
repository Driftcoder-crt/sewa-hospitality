<?php

namespace App\Modules\Careers\Models;

use App\Modules\Careers\Enums\Department;
use App\Modules\Careers\Enums\EmploymentType;
use App\Modules\Careers\Enums\JobStatus;
use App\Modules\Cities\Models\City;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A job posting (04-modules/06-hr-employee-module.md §3). The per-job
 * page exists for open AND paused AND closed postings — the reference's
 * 404 job details are structurally impossible: closed renders a "see
 * similar" state on the same URL.
 */
class JobPosting extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'slug', 'title', 'department', 'location_city_id', 'location_text',
        'employment_type', 'experience_min', 'experience_max',
        'description_html', 'responsibilities_html', 'skills_html',
        'status', 'closes_at', 'posted_by_user_id', 'published_at',
        'applies_to_email', 'locale', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'department' => Department::class,
            'employment_type' => EmploymentType::class,
            'status' => JobStatus::class,
            'closes_at' => 'date',
            'published_at' => 'datetime',
            'experience_min' => 'integer',
            'experience_max' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /* ── Relations ─────────────────────────────────────────────────── */

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'location_city_id');
    }

    /* ── Scopes ────────────────────────────────────────────────────── */

    /** Public listing scope: open and not past the closing date. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', JobStatus::Open)
            ->where(fn (Builder $q) => $q->whereNull('closes_at')->orWhere('closes_at', '>=', now()->toDateString()));
    }

    /** Public per-job URL. */
    public function publicPath(): string
    {
        return '/careers/'.$this->slug;
    }

    /** Experience range label, honest ("2–5 yrs" / "Any"). */
    public function experienceLabel(): string
    {
        if ($this->experience_min === null && $this->experience_max === null) {
            return 'Any experience';
        }

        $min = $this->experience_min ?? 0;
        $max = $this->experience_max;

        return $max !== null && $max !== $min ? "{$min}–{$max} yrs" : "{$min}+ yrs";
    }

    /** Closing line for the page ("Apply by 30 Sep 2026" / none). */
    public function closesLabel(): ?string
    {
        return $this->closes_at?->isoFormat('D MMM YYYY');
    }
}
