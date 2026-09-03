<?php

namespace App\Modules\Cms\Models;

use App\Modules\Cms\Enums\MenuItemType;
use App\Modules\Cms\Services\MenuService;
use App\Modules\I18n\Services\LocaleUrls;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Menu item (03-technical-specs/03-database-schema.md §2). Types:
 * route|page|service|custom. `ref_id` points at the linked entity;
 * `flagged` marks an item whose target disappeared — the reviewer
 * fixes or removes it, the public render drops it meanwhile
 * (never a dead link, 04-modules/01-cms.md §5).
 */
class MenuItem extends Model
{
    use HasUlids;

    protected $fillable = [
        'menu_id', 'parent_id', 'label', 'url', 'target', 'type',
        'ref_id', 'sort', 'flagged',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => MenuService::flush());
        static::deleted(fn () => MenuService::flush());
    }

    protected function casts(): array
    {
        return [
            'type' => MenuItemType::class,
            'flagged' => 'boolean',
        ];
    }

    /** @return BelongsTo<Menu, $this> */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /** @return HasMany<MenuItem, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')
            ->orderBy('sort')
            ->orderBy('created_at');
    }

    /**
     * Resolve the href this item points at. Page refs resolve through
     * the page model (their publicPath may change with type/slug);
     * unresolved or flagged targets render as '#' and are skipped by
     * the public renderer. Locale-aware (11-multilingual §5): under a
     * path prefix the href keeps it — menu clicks never drop the
     * visitor back to EN.
     */
    public function href(): ?string
    {
        $href = match ($this->type) {
            MenuItemType::Page => Page::query()->find($this->ref_id)?->publicPath(),
            MenuItemType::Service => $this->url, // resolved by Services module (M2)
            MenuItemType::Route, MenuItemType::Custom => $this->url,
        };

        if ($href === null || str_starts_with((string) $href, 'http')) {
            return $href;
        }

        return LocaleUrls::localized(app()->getLocale(), (string) $href);
    }
}
