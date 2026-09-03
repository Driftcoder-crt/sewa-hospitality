<?php

declare(strict_types=1);

namespace Modules\Cms\Services;

use Modules\Cms\Models\Page;
use Modules\Cms\Models\Revision;
use Modules\Cms\Models\Analytics;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CMS Page Service
 */
class PageService
{
    /**
     * Get paginated list of pages.
     */
    public function getPaginated(
        int $perPage = 15,
        ?string $search = null,
        ?string $status = null,
        string $sortBy = 'created_at',
        string $sortDir = 'desc'
    ): LengthAwarePaginator {
        $query = Page::query();

        if ($search) {
            $query->search($search);
        }

        if ($status) {
            $query->status($status);
        }

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    /**
     * Find page by slug.
     */
    public function findBySlug(string $slug): ?Page
    {
        return Page::where('slug', $slug)->first();
    }

    /**
     * Get home page.
     */
    public function getHomePage(): ?Page
    {
        return Page::where('slug', '/')
            ->orWhere('slug', 'home')
            ->first();
    }

    /**
     * Create a new page.
     */
    public function create(array $data): Page
    {
        return DB::transaction(function () use ($data) {
            $page = Page::create($data);

            // Create initial revision
            $this->createRevision($page, 'created', $data);

            return $page;
        });
    }

    /**
     * Update an existing page.
     */
    public function update(Page $page, array $data): Page
    {
        return DB::transaction(function () use ($page, $data) {
            $oldValues = $page->toArray();
            
            $page->update($data);

            // Create revision if there are changes
            if ($page->wasRecentlyUpdated()) {
                $this->createRevision($page, 'updated', $data, $oldValues);
            }

            return $page->fresh();
        });
    }

    /**
     * Publish a page.
     */
    public function publish(Page $page, ?string $reason = null): Page
    {
        return DB::transaction(function () use ($page, $reason) {
            $page->publish($reason);

            $this->createRevision($page, 'published', [
                'is_published' => true,
                'published_at' => now(),
                'status' => 'published',
            ]);

            return $page;
        });
    }

    /**
     * Unpublish a page.
     */
    public function unpublish(Page $page, ?string $reason = null): Page
    {
        return DB::transaction(function () use ($page, $reason) {
            $page->unpublish($reason);

            $this->createRevision($page, 'unpublished', [
                'is_published' => false,
                'status' => 'draft',
            ]);

            return $page;
        });
    }

    /**
     * Archive a page.
     */
    public function archive(Page $page, ?string $reason = null): Page
    {
        return DB::transaction(function () use ($page, $reason) {
            $page->archive($reason);

            $this->createRevision($page, 'archived', [
                'status' => 'archived',
            ]);

            return $page;
        });
    }

    /**
     * Duplicate a page.
     */
    public function duplicate(Page $page): Page
    {
        return DB::transaction(function () use ($page) {
            $newPage = $page->replicate();
            $newPage->title = $page->title . ' (Copy)';
            $newPage->slug = Page::generateUniqueSlug($page->title . ' (Copy)');
            $newPage->status = 'draft';
            $newPage->is_published = false;
            $newPage->published_at = null;
            $newPage->created_by = Auth::id();
            $newPage->save();

            // Duplicate blocks
            foreach ($page->blocks as $block) {
                $newBlock = $block->replicate();
                $newBlock->page_id = $newPage->id;
                $newBlock->created_by = Auth::id();
                $newBlock->save();
            }

            $this->createRevision($newPage, 'copied', [
                'copied_from' => $page->id,
            ]);

            return $newPage;
        });
    }

    /**
     * Delete a page.
     */
    public function delete(Page $page): bool
    {
        return DB::transaction(function () use ($page) {
            $this->createRevision($page, 'deleted', $page->toArray());
            
            return $page->delete();
        });
    }

    /**
     * Track page view.
     */
    public function trackView(Page $page): Analytics
    {
        return Analytics::trackView(
            $page,
            session()->getId(),
            Auth::id(),
            request()->headers->get('referer')
        );
    }

    /**
     * Create a revision record.
     */
    protected function createRevision(
        Page $page,
        string $action,
        array $newValues,
        array $oldValues = []
    ): Revision {
        return Revision::create([
            'revisionable_type' => Page::class,
            'revisionable_id' => $page->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues,
            'reason' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get pages by status.
     */
    public function getByStatus(string $status): array
    {
        return Page::status($status)->get()->toArray();
    }

    /**
     * Get published pages.
     */
    public function getPublished(): array
    {
        return Page::published()->get()->toArray();
    }

    /**
     * Search pages.
     */
    public function search(string $query, int $limit = 10): array
    {
        return Page::search($query)
            ->published()
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
