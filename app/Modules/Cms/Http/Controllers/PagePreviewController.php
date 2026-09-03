<?php

namespace App\Modules\Cms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Services\PageRenderer;
use Illuminate\Http\Response;

/**
 * Draft preview (04-modules/01-cms.md §4.2 "live preview… identical to
 * public"): admin-only, uncached render of the page as it stands —
 * same layout, same block pipeline, meta headers.
 */
class PagePreviewController extends Controller
{
    public function __construct(private readonly PageRenderer $renderer) {}

    public function __invoke(Page $page): Response
    {
        $this->authorize('view', $page);

        $html = $this->renderer->render($page, $page->locale)->render();

        return response($html)
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'private, no-store');
    }
}
