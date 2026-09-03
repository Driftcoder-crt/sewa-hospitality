<?php

namespace App\Modules\Cms\Observers;

use App\Modules\Cms\Enums\MenuItemType;
use App\Modules\Cms\Models\MenuItem;
use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Services\MenuService;
use App\Modules\Cms\Services\PageCache;

/**
 * Page lifecycle rules (04-modules/01-cms.md §5):
 * - any save flushes the page's render cache (publish, edits, archive);
 * - deleting a page auto-flags menu items that point at it for review
 *   (never a silently dead link).
 */
class PageObserver
{
    public function saved(Page $page): void
    {
        PageCache::flushFor($page);
    }

    public function deleted(Page $page): void
    {
        PageCache::flushFor($page);

        MenuItem::query()
            ->where('type', MenuItemType::Page->value)
            ->where('ref_id', $page->getKey())
            ->update(['flagged' => true]);

        // The flag was a bulk update (no model events) — flush by hand.
        MenuService::flush();
    }
}
