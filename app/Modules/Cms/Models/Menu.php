<?php

namespace App\Modules\Cms\Models;

use App\Modules\Cms\Enums\MenuLocation;
use App\Modules\Cms\Services\MenuService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Navigation menu (03-technical-specs/03-database-schema.md §2
 * "menus"): one per location+locale. The tree lives in menu_items.
 */
class Menu extends Model
{
    use HasUlids;

    protected $fillable = ['name', 'location', 'locale'];

    protected function casts(): array
    {
        return [
            'location' => MenuLocation::class,
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => MenuService::flush());
        static::deleted(fn () => MenuService::flush());
    }

    /** @return HasMany<MenuItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->orderBy('sort')
            ->orderBy('created_at');
    }
}
