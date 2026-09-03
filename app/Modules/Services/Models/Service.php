<?php

namespace App\Modules\Services\Models;

use App\Modules\Cities\Models\City;
use App\Modules\Cities\Models\CityService;
use App\Modules\Services\Enums\ServiceFamily;
use App\Modules\Services\Enums\ServiceStatus;
use App\Modules\Services\Observers\ServiceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * Service (03-service-catalog.md source of truth + schema §3):
 * self-referencing tree — two family hubs, immigration sub-tree,
 * leaves. URL slugs are locked to the catalog doc; a slug change uses
 * the same 301 flow as CMS pages. Lead forms on the page carry
 * `lead_tag` end-to-end into Leads (M3).
 */
#[ObservedBy([ServiceObserver::class])]
class Service extends Model
{
    use HasUlids;
    use Searchable;

    protected $fillable = [
        'slug', 'family', 'parent_id', 'name', 'short_desc', 'hero_media_id',
        'intro', 'content_blocks', 'faq', 'icon_svg_key', 'status', 'sort',
        'lead_tag', 'meta_title', 'meta_description', 'noindex',
        'noindex_reason', 'noindex_confirmed_at', 'noindex_confirmed_by',
        'locale', 'locale_source_id', 'cta_label_override', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'family' => ServiceFamily::class,
            'status' => ServiceStatus::class,
            'content_blocks' => 'array',
            'faq' => 'array',
            'noindex' => 'boolean',
            'noindex_confirmed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Service, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'parent_id');
    }

    /** @return HasMany<Service, $this> */
    public function children()
    {
        return $this->hasMany(Service::class, 'parent_id')->orderBy('sort');
    }

    /** @return BelongsToMany<Service, $this> */
    public function related(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_related', 'service_id', 'related_id')
            ->withPivot('sort')
            ->orderBy('service_related.sort');
    }

    /** @return BelongsToMany<City, $this> */
    public function cities()
    {
        return $this->belongsToMany(City::class, 'city_services')
            ->using(CityService::class)
            ->withPivot('note');
    }

    public function scopePublished($query)
    {
        return $query->where('status', ServiceStatus::Published->value);
    }

    /**
     * Public path per the catalog doc (03-service-catalog §URLs):
     * immigration children live at /services/immigration/{slug}, all
     * other leaves under /services/{family}/{slug}.
     */
    public function publicPath(): string
    {
        if ($this->parent_id === null) {
            // Family hub or the immigration hub itself.
            return $this->family === ServiceFamily::Standalone
                ? '/services/'.$this->slug
                : $this->family->path();
        }

        $parent = $this->parent;

        return '/services/'.($parent?->slug ?? $this->family->value).'/'.$this->slug;
    }

    /** SEO title template (06-content-seo/02-seo-technical §1.2). */
    public function displayTitle(): string
    {
        return trim((string) $this->meta_title) !== '' ? (string) $this->meta_title : $this->name;
    }

    /** Scout searchable index payload (08-search §1). */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'short_desc' => (string) $this->short_desc,
            'intro' => strip_tags((string) $this->intro),
            'path' => $this->publicPath(),
        ];
    }
}
