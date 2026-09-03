<?php

namespace App\Modules\Cities\Models;

use App\Modules\Cities\Enums\HousingTier;
use App\Modules\Cities\Enums\HousingType;
use App\Modules\Cities\Enums\RateUnit;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

/**
 * Housing unit (03-technical-specs/03-database-schema.md §3 +
 * 04-modules/10-cities-content.md): honest from-rates only, Sewa
 * Verified via verified_at, and a 90-day rate-staleness flag computed
 * on read (§6 "confirm current rate" badge).
 */
class HousingUnit extends Model
{
    /** Public = admin-published (housing.boolean). */
    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    use HasFactory;
    use HasUlids;
    use Searchable;

    public const RATE_STALE_DAYS = 90;

    protected $fillable = [
        'city_id', 'type', 'name', 'area', 'locality', 'bedrooms', 'tier',
        'status', 'from_rate', 'rate_unit', 'area_sqft', 'amenities',
        'media_ids', 'verified_at', 'verified_by_user_id', 'managed_by',
        'published', 'notes', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => HousingType::class,
            'tier' => HousingTier::class,
            'rate_unit' => RateUnit::class,
            'amenities' => 'array',
            'media_ids' => 'array',
            'verified_at' => 'datetime',
            'published' => 'boolean',
        ];
    }

    /** @return BelongsTo<City, $this> */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /** Sewa Verified badge is live only while published. */
    public function isVerified(): bool
    {
        return $this->published && $this->verified_at !== null;
    }

    /** 90-day staleness (cities doc §6): badge "confirm current rate". */
    public function isRateStale(): bool
    {
        return $this->published
            && $this->from_rate !== null
            && $this->updated_at !== null
            && $this->updated_at->diffInDays(now()) > self::RATE_STALE_DAYS;
    }

    /** Honest from-rate string — never fake precision (§5). */
    public function rateLabel(): ?string
    {
        if ($this->from_rate === null) {
            return null;
        }

        return 'from ₹'.number_format($this->from_rate).'/'.$this->rate_unit->label();
    }

    public function publicPath(): string
    {
        return '/housing/'.$this->getKey();
    }

    /** Scout payload (08-search §1: name, locality, area). */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'locality' => (string) $this->locality,
            'area' => (string) $this->area,
            'city' => $this->city?->name,
            'tier' => $this->tier->value,
            'type' => $this->type->value,
            'bedrooms' => $this->bedrooms,
            'rate_label' => $this->rateLabel(),
            'path' => $this->publicPath(),
        ];
    }
}
