<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CMS Collection Item Model
 * 
 * Pivot model for media collections.
 */
class CollectionItem extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected $table = 'cms_collection_items';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'collection_id',
        'media_id',
        'order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    /**
     * Get the collection.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(MediaCollection::class);
    }

    /**
     * Get the media.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
