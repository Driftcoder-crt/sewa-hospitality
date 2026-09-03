<?php

namespace App\Modules\Cms\Events;

use App\Modules\Cms\Models\Page;

/**
 * Fired when a page becomes public (draft→published or scheduled→
 * published via cms:publish-scheduled). Listeners flush caches
 * (PageCache bump already happened on save) and — from M2 — enqueue
 * sitemap regeneration + search upsert (event catalog,
 * 04-modules/00-module-system.md).
 */
class PagePublished
{
    public function __construct(public readonly Page $page) {}
}
