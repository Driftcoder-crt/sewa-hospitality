<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * CMS Page Model
 * 
 * Represents a content-managed page with full SEO support,
 * block-based content building, and revision tracking.
 */
class Page extends BaseModel
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'cms_pages';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'slug',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'content',
        'template',
        'is_published',
        'published_at',
        'author_id',
        'seo_data',
        'schema_markup',
        'canonical_url',
        'no_index',
        'no_follow',
        'og_image',
        'og_title',
        'og_description',
        'twitter_card',
        'status',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'seo_data' => 'array',
            'schema_markup' => 'array',
            'no_index' => 'boolean',
            'no_follow' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Page $page) {
            if (empty($page->slug)) {
                $page->slug = static::generateUniqueSlug($page->title);
            }
        });

        static::updating(function (Page $page) {
            // Track revisions on update
            if ($page->isDirty()) {
                // Revision tracking handled by observer
            }
        });
    }

    /**
     * Generate a unique slug from title.
     */
    public static function generateUniqueSlug(string $title, ?string $id = null): string
    {
        $slug = \Illuminate\Support\Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)
            ->when($id, fn($q) => $q->where('id', '!=', $id))
            ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Get the author of the page.
     */
    public function author(): HasOne
    {
        return $this->hasOne(\App\Models\User::class, 'id', 'author_id');
    }

    /**
     * Get the creator of the page.
     */
    public function creator(): HasOne
    {
        return $this->hasOne(\App\Models\User::class, 'id', 'created_by');
    }

    /**
     * Get the updater of the page.
     */
    public function updater(): HasOne
    {
        return $this->hasOne(\App\Models\User::class, 'id', 'updated_by');
    }

    /**
     * Get the blocks for this page.
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class)->orderBy('order');
    }

    /**
     * Get published blocks only.
     */
    public function publishedBlocks(): HasMany
    {
        return $this->hasMany(Block::class)
            ->where('is_active', true)
            ->orderBy('order');
    }

    /**
     * Get all revisions for this page.
     */
    public function revisions(): MorphMany
    {
        return $this->morphMany(Revision::class, 'revisionable');
    }

    /**
     * Get the latest revision.
     */
    public function latestRevision(): MorphOne
    {
        return $this->morphOne(Revision::class, 'revisionable')
            ->latest();
    }

    /**
     * Get analytics events for this page.
     */
    public function analytics(): MorphMany
    {
        return $this->morphMany(Analytics::class, 'trackable');
    }

    /**
     * Get page views count.
     */
    public function getViewCountAttribute(): int
    {
        return $this->analytics()
            ->where('event_type', 'view')
            ->count();
    }

    /**
     * Check if page is published.
     */
    public function isPublished(): bool
    {
        return $this->is_published && $this->published_at?->isPast();
    }

    /**
     * Check if page is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if page is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * Publish the page.
     */
    public function publish(?string $reason = null): bool
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
            'status' => 'published',
        ]);

        return true;
    }

    /**
     * Unpublish the page.
     */
    public function unpublish(?string $reason = null): bool
    {
        $this->update([
            'is_published' => false,
            'status' => 'draft',
        ]);

        return true;
    }

    /**
     * Archive the page.
     */
    public function archive(?string $reason = null): bool
    {
        $this->update([
            'status' => 'archived',
        ]);

        return true;
    }

    /**
     * Get the canonical URL.
     */
    public function getCanonicalUrlAttribute(): ?string
    {
        return $this->canonical_url ?? route('pages.show', $this->slug);
    }

    /**
     * Get the OG image URL.
     */
    public function getOgImageUrlAttribute(): ?string
    {
        if ($this->og_image) {
            return Storage::disk('public')->url($this->og_image);
        }

        return null;
    }

    /**
     * Get full SEO data array.
     */
    public function getSeoDataArray(): array
    {
        return array_merge([
            'title' => $this->meta_title ?? $this->title,
            'description' => $this->meta_description,
            'keywords' => $this->meta_keywords,
            'canonical' => $this->canonical_url,
            'no_index' => $this->no_index,
            'no_follow' => $this->no_follow,
            'og_title' => $this->og_title ?? $this->title,
            'og_description' => $this->og_description ?? $this->meta_description,
            'og_image' => $this->og_image_url,
            'twitter_card' => $this->twitter_card,
            'schema_markup' => $this->schema_markup,
        ], $this->seo_data ?? []);
    }

    /**
     * Scope to get only published pages.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where('status', 'published')
            ->whereNull('published_at')
            ->orWhere('published_at', '<=', now());
    }

    /**
     * Scope to get pages by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to search pages.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")
                ->orWhere('meta_description', 'like', "%{$search}%");
        });
    }
}
