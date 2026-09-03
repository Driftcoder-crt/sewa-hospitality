<?php

declare(strict_types=1);

namespace Modules\Cms\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Cms\Models\Page;
use Modules\Cms\Services\PageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public CMS Page Controller
 */
class PageController extends Controller
{
    public function __construct(
        protected PageService $pageService
    ) {}

    /**
     * Display a published page by slug.
     */
    public function show(string $slug): View
    {
        $page = $this->pageService->findBySlug($slug);

        abort_unless($page, 404, 'Page not found');
        abort_unless($page->isPublished(), 404);

        // Track page view
        $this->pageService->trackView($page);

        return view('cms.pages.show', [
            'page' => $page,
            'seo' => $page->getSeoDataArray(),
            'blocks' => $page->publishedBlocks,
        ]);
    }

    /**
     * Display homepage.
     */
    public function home(): View
    {
        $page = $this->pageService->getHomePage();

        if (!$page || !$page->isPublished()) {
            return view('welcome');
        }

        $this->pageService->trackView($page);

        return view('cms.pages.home', [
            'page' => $page,
            'seo' => $page->getSeoDataArray(),
            'blocks' => $page->publishedBlocks,
        ]);
    }
}
