<?php

namespace App\Modules\Cms\Services;

use App\Modules\Cms\Models\Page;

/**
 * Page render cache (04-modules/01-cms.md §3 + §6): full-page cache
 * keyed by page+locale+version. Shared-hosting discipline — no cache
 * tags (file/database drivers don't support them), so invalidation
 * bumps a per-page version integer and stale HTML self-heals within
 * the 10-minute TTL ("stale page self-heals on TTL, max 10-min
 * staleness").
 */
final class PageCache
{
    public const TTL_SECONDS = 600;

    public static function version(Page $page): int
    {
        return (int) cache()->get(self::versionKey($page), 1);
    }

    public static function key(Page $page, string $locale): string
    {
        return sprintf('cms.page.html.%s.%s.%d', $page->getKey(), $locale, self::version($page));
    }

    /** Invalidate all cached renders of the page (any locale). */
    public static function flushFor(Page $page): void
    {
        cache()->forever(self::versionKey($page), self::version($page) + 1);
    }

    /** @return string HTML|null */
    public static function get(Page $page, string $locale): ?string
    {
        return cache()->get(self::key($page, $locale));
    }

    public static function put(Page $page, string $locale, string $html): void
    {
        cache()->put(self::key($page, $locale), $html, self::TTL_SECONDS);
    }

    private static function versionKey(Page $page): string
    {
        return 'cms.page.ver.'.$page->getKey();
    }
}
