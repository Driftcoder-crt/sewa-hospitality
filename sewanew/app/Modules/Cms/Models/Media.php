<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

/**
 * CMS Media Model
 * 
 * Represents an uploaded media file (image, video, document, etc.).
 */
class Media extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected $table = 'cms_media';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'filename',
        'original_filename',
        'mime_type',
        'disk',
        'path',
        'size',
        'width',
        'height',
        'alt_text',
        'caption',
        'description',
        'metadata',
        'uploaded_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (Media $media) {
            // Delete physical file when model is deleted
            if (Storage::disk($media->disk)->exists($media->path)) {
                Storage::disk($media->disk)->delete($media->path);
            }
        });
    }

    /**
     * Get the uploader of the media.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    /**
     * Get collections this media belongs to.
     */
    public function collections(): MorphMany
    {
        return $this->morphedByMany(MediaCollection::class, 'mediaable', 'cms_collection_items');
    }

    /**
     * Get the full URL for the media.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Get thumbnail URL if available.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->isImage() && isset($this->metadata['thumbnails']['small'])) {
            return Storage::disk($this->disk)->url($this->metadata['thumbnails']['small']);
        }

        return null;
    }

    /**
     * Check if media is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if media is a video.
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Check if media is a document.
     */
    public function isDocument(): bool
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * Get human-readable file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Scope to get only images.
     */
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    /**
     * Scope to get only videos.
     */
    public function scopeVideos($query)
    {
        return $query->where('mime_type', 'like', 'video/%');
    }

    /**
     * Scope to get by disk.
     */
    public function scopeDisk($query, string $disk)
    {
        return $query->where('disk', $disk);
    }

    /**
     * Scope to search media.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('original_filename', 'like', "%{$search}%")
                ->orWhere('alt_text', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
