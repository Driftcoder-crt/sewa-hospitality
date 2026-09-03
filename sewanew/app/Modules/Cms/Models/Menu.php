<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CMS Menu Model
 * 
 * Represents a navigation menu with configurable items.
 */
class Menu extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected $table = 'cms_menus';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'location',
        'is_active',
        'items',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'items' => 'array',
        ];
    }

    /**
     * Get menu items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('order');
    }

    /**
     * Get visible menu items only.
     */
    public function visibleItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->where('is_visible', true)
            ->orderBy('order');
    }

    /**
     * Get the creator of the menu.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the updater of the menu.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Scope to get active menus.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get menu by location.
     */
    public function scopeLocation($query, string $location)
    {
        return $query->where('location', $location);
    }

    /**
     * Get or create menu by location.
     */
    public static function getByLocation(string $location): ?self
    {
        return static::where('location', $location)
            ->where('is_active', true)
            ->with(['visibleItems' => function ($q) {
                $q->with('parent')->orderBy('order');
            }])
            ->first();
    }

    /**
     * Build hierarchical menu structure.
     */
    public function buildTree(): array
    {
        $items = $this->visibleItems()
            ->with('children')
            ->whereNull('parent_id')
            ->get();

        return $items->map(fn($item) => $item->toTreeArray())->toArray();
    }
}
