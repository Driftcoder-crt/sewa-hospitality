<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * CMS Media Collection Model
 * 
 * Represents a collection/album of media files.
 */
class MediaCollection extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected $table = 'cms_media_collections';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'cover_image_id',
        'is_public',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (MediaCollection $collection) {
            if (empty($collection->slug)) {
                $collection->slug = \Illuminate\Support\Str::slug($collection->name);
            }
        });
    }

    /**
     * Get the creator of the collection.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the cover image.
     */
    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_image_id');
    }

    /**
     * Get all media items in the collection.
     */
    public function mediaItems(): HasMany
    {
        return $this->hasMany(CollectionItem::class)->orderBy('order');
    }

    /**
     * Get all media through pivot.
     */
    public function media(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'cms_collection_items')
            ->withPivot('order')
            ->orderByPivot('order');
    }

    /**
     * Add media to collection.
     */
    public function addMedia(Media $media, int $order = null): void
    {
        $maxOrder = $this->mediaItems()->max('order') ?? -1;
        
        CollectionItem::create([
            'collection_id' => $this->id,
            'media_id' => $media->id,
            'order' => $order ?? ($maxOrder + 1),
        ]);
    }

    /**
     * Remove media from collection.
     */
    public function removeMedia(Media $media): void
    {
        CollectionItem::where('collection_id', $this->id)
            ->where('media_id', $media->id)
            ->delete();
    }

    /**
     * Get collection URL.
     */
    public function getUrlAttribute(): string
    {
        return route('media.collections.show', $this->slug);
    }

    /**
     * Get cover image URL.
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        if ($this->coverImage) {
            return $this->coverImage->url;
        }

        $firstMedia = $this->mediaItems()->first();
        return $firstMedia?->media?->url;
    }

    /**
     * Get media count.
     */
    public function getMediaCountAttribute(): int
    {
        return $this->mediaItems()->count();
    }

    /**
     * Scope to get public collections.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to search collections.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
