<?php

namespace App\Modules\Services\Events;

use App\Modules\Services\Models\Service;

/**
 * ServicePublished (event catalog, 04-modules/00-module-system.md):
 * from M2-d the listener enqueues sitemap regeneration; cache purge
 * happens in the observer.
 */
class ServicePublished
{
    public function __construct(public readonly Service $service) {}
}
