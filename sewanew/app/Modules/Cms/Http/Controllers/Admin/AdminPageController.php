<?php

declare(strict_types=1);

namespace Modules\Cms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Cms\Models\Page;
use Modules\Cms\Services\PageService;
use Modules\Cms\Http\Requests\StorePageRequest;
use Modules\Cms\Http\Requests\UpdatePageRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Admin CMS Page Controller
 */
class AdminPageController extends Controller
{
    public function __construct(
        protected PageService $pageService
    ) {}

    /**
     * Display listing of pages.
     */
    public function index(Request $request): View
    {
        $pages = $this->pageService->getPaginated(
            perPage: $request->get('per_page', 15),
            search: $request->get('search'),
            status: $request->get('status'),
            sortBy: $request->get('sort_by', 'created_at'),
            sortDir: $request->get('sort_dir', 'desc')
        );

        return view('cms.admin.pages.index', [
            'pages' => $pages,
            'statuses' => Page::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
        ]);
    }

    /**
     * Show form for creating a new page.
     */
    public function create(): View
    {
        return view('cms.admin.pages.create', [
            'page' => null,
            'blockTypes' => ['hero', 'features', 'content'],
            'templates' => ['default', 'landing', 'minimal'],
        ]);
    }

    /**
     * Store a newly created page.
     */
    public function store(StorePageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['author_id'] = Auth::id();
        $data['created_by'] = Auth::id();

        $page = $this->pageService->create($data);

        return redirect()
            ->route('admin.cms.pages.edit', $page)
            ->with('success', 'Page created successfully.');
    }

    /**
     * Display the specified page for editing.
     */
    public function edit(Page $page): View
    {
        return view('cms.admin.pages.edit', [
            'page' => $page->load(['blocks', 'revisions' => function ($q) {
                $q->latest()->limit(10);
            }]),
            'blockTypes' => ['hero', 'features', 'content', 'cta', 'testimonials'],
            'templates' => ['default', 'landing', 'minimal'],
        ]);
    }

    /**
     * Update the specified page.
     */
    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        $page = $this->pageService->update($page, $data);

        return redirect()
            ->route('admin.cms.pages.edit', $page)
            ->with('success', 'Page updated successfully.');
    }

    /**
     * Publish the specified page.
     */
    public function publish(Request $request, Page $page): RedirectResponse
    {
        $reason = $request->input('reason');
        
        $this->pageService->publish($page, $reason);

        return back()->with('success', 'Page published successfully.');
    }

    /**
     * Unpublish the specified page.
     */
    public function unpublish(Request $request, Page $page): RedirectResponse
    {
        $reason = $request->input('reason');
        
        $this->pageService->unpublish($page, $reason);

        return back()->with('success', 'Page unpublished successfully.');
    }

    /**
     * Archive the specified page.
     */
    public function archive(Request $request, Page $page): RedirectResponse
    {
        $reason = $request->input('reason');
        
        $this->pageService->archive($page, $reason);

        return back()->with('success', 'Page archived successfully.');
    }

    /**
     * Duplicate the specified page.
     */
    public function duplicate(Page $page): RedirectResponse
    {
        $newPage = $this->pageService->duplicate($page);

        return redirect()
            ->route('admin.cms.pages.edit', $newPage)
            ->with('success', 'Page duplicated successfully.');
    }

    /**
     * Remove the specified page.
     */
    public function destroy(Page $page): RedirectResponse
    {
        $this->pageService->delete($page);

        return redirect()
            ->route('admin.cms.pages.index')
            ->with('success', 'Page deleted successfully.');
    }

    /**
     * Preview the page.
     */
    public function preview(Page $page): View
    {
        return view('cms.pages.show', [
            'page' => $page,
            'seo' => $page->getSeoDataArray(),
            'blocks' => $page->blocks,
        ]);
    }

    /**
     * Get page revisions.
     */
    public function revisions(Page $page): View
    {
        return view('cms.admin.pages.revisions', [
            'page' => $page,
            'revisions' => $page->revisions()->latest()->paginate(20),
        ]);
    }

    /**
     * Rollback to a specific revision.
     */
    public function rollbackRevision(Request $request, Page $page, string $revisionId): RedirectResponse
    {
        $revision = $page->revisions()->findOrFail($revisionId);
        $reason = $request->input('reason', "Rollback to revision #{$revisionId}");

        $revision->rollback($reason);

        return back()->with('success', 'Page rolled back successfully.');
    }
}
