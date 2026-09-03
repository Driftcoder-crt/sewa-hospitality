<?php

namespace App\Modules\Testimonials\Models;

use App\Modules\Cities\Models\City;
use App\Modules\Services\Models\Service;
use App\Modules\Testimonials\Enums\TestimonialSource;
use App\Modules\Testimonials\Enums\TestimonialStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A curated testimonial (08 doc §2/§5): verified ONLY when linked to a
 * synced Google review or marked verified via a completed move; names
 * render only with consent_named (default first name + city).
 */
class Testimonial extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'client_name', 'client_role', 'company', 'city_id', 'service_id',
        'body', 'rating', 'source', 'source_url', 'google_review_id',
        'consent_named', 'verified_at', 'published_at', 'status', 'locale',
        'locale_source_id',
    ];

    protected function casts(): array
    {
        return [
            'source' => TestimonialSource::class,
            'status' => TestimonialStatus::class,
            'verified_at' => 'datetime',
            'published_at' => 'datetime',
            'consent_named' => 'boolean',
            'rating' => 'integer',
        ];
    }

    /* ── Relations ─────────────────────────────────────────────────── */

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function googleReview(): BelongsTo
    {
        return $this->belongsTo(GoogleReview::class);
    }

    /* ── Scopes ────────────────────────────────────────────────────── */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', TestimonialStatus::Published);
    }

    /** The honesty rule: verified rows only get the "verified" badge. */
    public function isVerified(): bool
    {
        return $this->verified_at !== null || $this->google_review_id !== null;
    }

    /** Consent-gated display name (08 doc §5: default first name + city). */
    public function displayName(): string
    {
        if ($this->consent_named) {
            return $this->company
                ? "{$this->client_name}, {$this->company}"
                : (string) $this->client_name;
        }

        $first = explode(' ', trim((string) $this->client_name))[0];

        return $this->city?->name
            ? "{$first} · {$this->city->name}"
            : $first;
    }
}
