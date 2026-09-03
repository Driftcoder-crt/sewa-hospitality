<?php

namespace App\Modules\Cms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cms\Enums\PageStatus;
use App\Modules\Cms\Enums\PageType;
use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Services\PageRenderer;
use App\Modules\I18n\Services\ContentVariants;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public CMS surface (04-modules/01-cms.md §3):
 *   /            → pages(slug=home)
 *   /{standard}  → /about, /contact …
 *   /legal/{p}   → legal pages
 *   /p/{slug}    → landing pages for campaigns
 * Anonymous full-page cache keyed path+locale (PageRenderer::cachedHtml).
 * Locale prefixes resolve through ContentVariants — a missing localized
 * variant renders the EN source (11-multilingual §4 fallback doctrine).
 */
class PageController extends Controller
{
    public function __construct(private readonly PageRenderer $renderer) {}

    public function home(): Response
    {
        return $this->serve(
            ContentVariants::firstInLocale(
                Page::query()->published()->where('slug', 'home'),
            ),
        );
    }

    /**
     * Standard pages live behind DEDICATED methods (about/contact) rather
     * than Route::defaults('slug', ...): with a leading {locale?} param,
     * Laravel hands controller scalars the route parameters positionally —
     * $slug received the LOCALE segment ('en') and every unprefixed path
     * 404'd. Explicit methods carry no positional ambiguity.
     */
    public function about(): Response
    {
        return $this->standardPage('about');
    }

    public function contact(): Response
    {
        return $this->standardPage('contact');
    }

    private function standardPage(string $slug): Response
    {
        return $this->serve(
            ContentVariants::firstInLocale(
                Page::query()
                    ->published()
                    ->where('slug', mb_strtolower($slug))
                    ->whereIn('type', [PageType::Standard->value, PageType::About->value]),
            ),
        );
    }

    public function legal(string $slug): Response
    {
        return $this->serve(
            ContentVariants::firstInLocale(
                Page::query()
                    ->published()
                    ->where('slug', mb_strtolower($slug))
                    ->where('type', PageType::Legal->value),
            ),
        );
    }

    public function landing(string $slug): Response
    {
        return $this->serve(
            ContentVariants::firstInLocale(
                Page::query()
                    ->published()
                    ->where('slug', mb_strtolower($slug))
                    ->where('type', PageType::Landing->value),
            ),
        );
    }

    /** Render + cache, or 404 (never a dead end — errors pages carry menus). */
    private function serve(?Page $page): Response
    {
        if (! $page || $page->status !== PageStatus::Published) {
            throw new NotFoundHttpException('Page not found.');
        }

        $html = $this->renderer->cachedHtml($page, app()->getLocale());

        return response($html)->header('X-Content-Type-Options', 'nosniff');
    }
}
