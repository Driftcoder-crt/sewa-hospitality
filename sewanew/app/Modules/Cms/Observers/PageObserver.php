<?php

declare(strict_types=1);

namespace Modules\Cms\Observers;

use Modules\Cms\Models\Page;
use Modules\Cms\Models\Revision;
use Modules\Cms\Events\PagePublished;
use Illuminate\Support\Facades\Auth;

/**
 * Page Observer
 */
class PageObserver
{
    /**
     * Handle the Page "creating" event.
     */
    public function creating(Page $page): void
    {
        if (empty($page->slug)) {
            $page->slug = Page::generateUniqueSlug($page->title);
        }

        if (!$page->created_by && Auth::check()) {
            $page->created_by = Auth::id();
        }
    }

    /**
     * Handle the Page "updating" event.
     */
    public function updating(Page $page): void
    {
        if (!$page->updated_by && Auth::check()) {
            $page->updated_by = Auth::id();
        }
    }

    /**
     * Handle the Page "updated" event.
     */
    public function updated(Page $page): void
    {
        if ($page->wasRecentlyUpdated()) {
            $this->createRevision($page, 'updated');
        }
    }

    /**
     * Handle the Page "published" event.
     */
    public function published(Page $page): void
    {
        $this->createRevision($page, 'published');
        
        // Dispatch event for listeners
        PagePublished::dispatch($page);
    }

    /**
     * Handle the Page "deleted" event.
     */
    public function deleted(Page $page): void
    {
        $this->createRevision($page, 'deleted', $page->toArray());
    }

    /**
     * Handle the Page "restored" event.
     */
    public function restored(Page $page): void
    {
        $this->createRevision($page, 'restored');
    }

    /**
     * Create a revision record.
     */
    protected function createRevision(Page $page, string $action, ?array $data = null): void
    {
        Revision::create([
            'revisionable_type' => Page::class,
            'revisionable_id' => $page->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'old_values' => $page->getOriginal(),
            'new_values' => $data ?? $page->toArray(),
            'reason' => null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
