<?php

namespace App\Modules\Cms\Events;

use App\Modules\Cms\Models\Page;

/**
 * Fired when a page stops being public (unpublish/archive). From M2
 * this also enqueues sitemap regeneration.
 */
class PageUnpublished
{
    public function __construct(public readonly Page $page) {}
}
