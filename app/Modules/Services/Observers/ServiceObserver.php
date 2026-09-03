<?php

namespace App\Modules\Services\Observers;

use App\Modules\Services\Models\Service;

/**
 * Service lifecycle (04-modules/02-services-module.md §7): publish/
 * update → sitemap + cache purge + search upsert (M2-d wires the
 * sitemap enqueue; search upsert is Scout's queue on `syncs`).
 * Tree integrity (§8): saving a cycle is structurally impossible —
 * an ancestor walk to self nulls the parent (orphan parents cannot
 * exist either; parents are nullOnDelete).
 */
class ServiceObserver
{
    public function saving(Service $service): void
    {
        $id = $service->getKey();

        // An unsaved model has no key yet — it cannot be part of a cycle
        // (and getKey() === null would false-match the chain terminator).
        // The saved() pass on the next update re-checks integrity.
        if ($service->parent_id === null || $id === null) {
            return;
        }

        if ($service->parent_id === $id) {
            $service->parent_id = null;

            return;
        }

        $ancestor = $service->parent_id;
        $depth = 0;
        while ($ancestor !== null && $depth < 25) {
            $ancestor = Service::query()->whereKey($ancestor)->value('parent_id');
            if ($ancestor !== null && $ancestor === $id) {
                $service->parent_id = null;

                return;
            }
            $depth++;
        }
    }

    public function saved(Service $service): void
    {
        cache()->forget('services.tree');
        cache()->forget('services.hub');
    }

    public function deleted(Service $service): void
    {
        cache()->forget('services.tree');
        cache()->forget('services.hub');
    }
}
