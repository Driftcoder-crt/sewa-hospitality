<?php

namespace App\Modules\Cms\Services;

use App\Modules\Cms\Enums\MenuLocation;
use App\Modules\Cms\Models\Menu;
use App\Modules\Cms\Models\MenuItem;
use App\Modules\I18n\Models\Locale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Menu read/tree service. Trees are cached per location+locale and
 * flushed on any menu-item write (MenuObserver) or page delete
 * (PageObserver flags items → flush). The public surface must render
 * even during a database outage, so treeSafe() degrades to an empty
 * navigation instead of a 500 (05-security-reliability §6).
 */
class MenuService
{
    public const CACHE_KEY = 'cms.menu.tree.%s.%s';

    /** @return Collection<int, MenuItem> flat, sorted, flagged excluded */
    public function tree(MenuLocation|string $location, string $locale = 'en'): Collection
    {
        $location = $location instanceof MenuLocation ? $location->value : $location;

        return Cache::rememberForever(
            sprintf(self::CACHE_KEY, $location, $locale),
            fn () => $this->query($location, $locale)->values(),
        );
    }

    /** Same as tree() but never throws (public layout resilience). */
    public function treeSafe(MenuLocation|string $location, string $locale = 'en'): Collection
    {
        try {
            return $this->tree($location, $locale);
        } catch (Throwable) {
            return collect();
        }
    }

    /** @return Collection<int, MenuItem> */
    private function query(string $location, string $locale): Collection
    {
        $menu = Menu::query()
            ->where('location', $location)
            ->where('locale', $locale)
            ->first();

        // EN fallback (11-multilingual §4): a missing localized menu
        // tree NEVER blanks the site chrome — the EN navigation serves
        // under that locale until a translated menu is published.
        if (! $menu && $locale !== 'en') {
            $menu = Menu::query()
                ->where('location', $location)
                ->where('locale', 'en')
                ->first();
        }

        if (! $menu) {
            return collect();
        }

        return MenuItem::query()
            ->where('menu_id', $menu->getKey())
            // Boolean column with default(false): a NULL check never
            // matches — rows are created as false and the flag rule is
            // "not flagged", i.e. flagged = false.
            ->where('flagged', false)
            ->orderBy('sort')
            ->orderBy('created_at')
            ->get();
    }

    public static function flush(string $locale = 'en'): void
    {
        // A write in ANY locale invalidates every locale's tree: the EN
        // fallback makes per-locale caches interdependent — a ja-keyed
        // tree may hold fallback EN data that an EN edit must clear too.
        // rememberForever means a missed key stays stale forever.
        try {
            $locales = array_unique(['en', $locale, ...array_keys(Locale::enabledSwitcher())]);
        } catch (Throwable) {
            // Registry unavailable (pre-seed provisioning) — flush what we know.
            $locales = array_unique(['en', $locale]);
        }

        foreach (MenuLocation::cases() as $location) {
            foreach ($locales as $flushLocale) {
                Cache::forget(sprintf(self::CACHE_KEY, $location->value, $flushLocale));
            }
        }
    }
}
