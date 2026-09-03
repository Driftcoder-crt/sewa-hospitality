<?php

namespace App\Modules\Cities\Models;

use App\Modules\Cities\Enums\CityStatus;
use App\Modules\Cities\Observers\CityObserver;
use App\Modules\Services\Models\Service;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * City (03-technical-specs/03-database-schema.md §3 + 04-modules/
 * 10-cities-content.md): the money template of the SEO program. Hub
 * cities get deeper content first (city content program W1). Coverage
 * truth lives in city_services — a service shows on the page only with
 * a real row (§5).
 */
#[ObservedBy([CityObserver::class])]
class City extends Model
{
    use HasFactory;
    use HasUlids;
    use Searchable;

    protected $fillable = [
        'slug', 'name', 'state', 'country', 'lat', 'lng', 'is_hub',
        'description', 'content_blocks', 'hero_media_id', 'population',
        'cost_index', 'status', 'locale', 'locale_source_id',
        'meta_title', 'meta_description', 'noindex', 'noindex_reason',
        'noindex_confirmed_at', 'noindex_confirmed_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => CityStatus::class,
            'is_hub' => 'boolean',
            'content_blocks' => 'array',
            'lat' => 'float',
            'lng' => 'float',
            'noindex' => 'boolean',
            'noindex_confirmed_at' => 'datetime',
        ];
    }

    /** @return BelongsToMany<Service, $this> */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'city_services')
            ->withPivot('note');
    }

    /** @return HasMany<HousingUnit, $this> */
    public function housingUnits()
    {
        return $this->hasMany(HousingUnit::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', CityStatus::Published->value);
    }

    public function publicPath(): string
    {
        return '/cities/'.$this->slug;
    }

    /** Scout payload (08-search §1: name, state, description). */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'state' => $this->state,
            'description' => strip_tags((string) $this->description),
            'path' => $this->publicPath(),
        ];
    }
}
