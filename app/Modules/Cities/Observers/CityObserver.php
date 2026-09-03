<?php

namespace App\Modules\Cities\Observers;

use App\Modules\Cities\Models\City;

/**
 * City lifecycle (04-modules/10-cities-content.md §7): city saves
 * flush the services-tree caches (coverage strips render per service);
 * publish events (CityPublished) are fired by the editor, not here —
 * saves only invalidate.
 */
class CityObserver
{
    public function saved(City $city): void
    {
        cache()->forget('services.tree');
    }

    public function deleted(City $city): void
    {
        cache()->forget('services.tree');
    }
}
