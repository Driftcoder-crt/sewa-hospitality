<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CMS Menu Item Model
 * 
 * Represents an individual item in a navigation menu.
 */
class MenuItem extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected $table = 'cms_menu_items';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'menu_id',
        'title',
        'url',
        'page_id',
        'parent_id',
        'order',
        'icon',
        'is_external',
        'is_visible',
        'attributes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_external' => 'boolean',
            'is_visible' => 'boolean',
            'order' => 'integer',
            'attributes' => 'array',
        ];
    }

    /**
     * Get the parent menu.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Get the parent menu item (for nested menus).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * Get child menu items.
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('order');
    }

    /**
     * Get the linked page.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Get the full URL for this item.
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->attributes['url']) {
            return $this->attributes['url'];
        }

        if ($this->page_id && $this->page) {
            return route('pages.show', $this->page->slug);
        }

        return '#';
    }

    /**
     * Check if item has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Convert to tree array structure.
     */
    public function toTreeArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'icon' => $this->icon,
            'is_external' => $this->is_external,
            'attributes' => $this->attributes ?? [],
            'children' => $this->children->map(fn($child) => $child->toTreeArray())->toArray(),
        ];
    }

    /**
     * Scope to get visible items.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope to get top-level items (no parent).
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }
}
