<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

/**
 * Platform media record (03-technical-specs/03-database-schema.md §2 +
 * 09-media-pipeline.md). Extends the Spatie model so the media table is
 * owned by OUR migration (we never publish the package migrations).
 *
 * NOTE (accepted deviation, least-deviation rule): the media table keeps
 * Spatie's integer PK — the package contract depends on it. Every Sewa
 * content table uses ULIDs; media is referenced by UUID for URLs.
 *
 * Alt text discipline (§4): `alt_text` is required at upload by the
 * admin validation layer. Decorative images carry alt_text='' with
 * is_decorative=true — intentional, never accidental.
 */
class Media extends BaseMedia
{
    use HasFactory;

    protected $fillable = [
        'alt_text',
        'is_decorative',
        'credit',
        'focal_point',
        'namespace',
        'person_consent',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_decorative' => 'boolean',
            'person_consent' => 'boolean',
        ]);
    }

    /** Effective alt string for rendering: decorative images render alt="". */
    public function effectiveAltText(): Attribute
    {
        return Attribute::get(fn (): string => $this->is_decorative ? '' : (string) $this->alt_text);
    }

    /** Alt text is present and publish-safe (empty only when decorative). */
    public function hasUsableAltText(): bool
    {
        return $this->is_decorative || trim((string) $this->alt_text) !== '';
    }

    /** Convertible image? (EXIF stripping + raster conversions apply to these.) */
    public function isRasterImage(): bool
    {
        return in_array($this->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true);
    }

    public function scopeInNamespace(Builder $query, string $namespace): Builder
    {
        return $query->where('namespace', $namespace);
    }
}
