<?php

use App\Http\Controllers\StatusController;
use App\Modules\Blog\Http\Controllers\BlogController;
use App\Modules\Careers\Http\Controllers\CareersController;
use App\Modules\Cities\Http\Controllers\CityController;
use App\Modules\Cms\Http\Controllers\PageController;
use App\Modules\Cms\Http\Controllers\RedirectController;
use App\Modules\Csr\Http\Controllers\CsrController;
use App\Modules\I18n\Http\Controllers\LocaleController;
use App\Modules\I18n\Models\Locale;
use App\Modules\Leads\Http\Controllers\NewsletterController;
use App\Modules\Leads\Http\Controllers\ThankYouController;
use App\Modules\Search\Livewire\SiteSearch;
use App\Modules\Services\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/*
| Request-time locale binding (04-modules/11-multilingual.md §3): a
| {locale} segment is valid ONLY when the locales registry currently
| enables it. Evaluated per request — never frozen at boot. Absent/
| empty segments (the optional unprefixed-EN case) pass through so the
| default-locale path keeps working.
*/
Route::bind('locale', function ($value) {
    if ($value === null || $value === '') {
        return $value;
    }

    if (! Locale::isEnabled((string) $value)) {
        throw new NotFoundHttpException("Locale [{$value}] is not enabled.");
    }

    return $value;
});

/*
|--------------------------------------------------------------------------
| Public site — sewahospitality.com (area: site)
|--------------------------------------------------------------------------
| The CMS-composed page tree + the full public content surface, served
| under the locale algebra (04-modules/11-multilingual.md §3/§5):
| unprefixed paths are canonically EN (x-default); every enabled locale
| prefixes (/ja /ko /tr /ar /hi) the SAME route set. Controllers resolve
| entities via ContentVariants — missing localized variant → the EN
| source renders (fallback doctrine), hreflang stays truthful.
|
| Utility/noindex surfaces (search, feed, thank-you, newsletter,
| status, dev) stay unprefixed by contract.
*/

// Explicit-locale chooser (banner CTA + switcher): the only cookie-
// setting click. Registered BEFORE the {locale?} group so /locale/x
// is never read as a content path.
Route::get('/locale/dismiss', [LocaleController::class, 'dismiss'])
    ->name('locale.dismiss');
Route::get('/locale/{code}', [LocaleController::class, 'choose'])
    ->where('code', '[a-z]{2,3}')
    ->name('locale.choose');

/*
|--------------------------------------------------------------------------
| Public site route map (04-modules/01-cms.md §3 + 11-multilingual §5)
|--------------------------------------------------------------------------
| ONE map, TWO registrations:
|
|   1. CANONICAL EN (x-default) — unprefixed, NAMELESS mirrors. Laravel's
|      optional-mid-URI regex ({locale?}/about) can never match /about,
|      so unprefixed content paths need their own definitions.
|
|   2. LOCALIZED — {locale?} prefix group carrying ALL route names. The
|      {locale?} parameter lets URL::defaults (LocaleResolver) inject the
|      active prefix into every route() call, so /ja pages generate /ja
|      links while unprefixed requests generate canonical EN links.
|      Names live ONLY here: duplicate route names resolve last-registered
|      — naming the mirrors too would strip localized prefixes from links.
|
| The locale segment is validated per-request by Route::bind('locale').
| LocaleResolver forgets the 'locale' bag entry before controllers run:
| Laravel spreads route parameters POSITIONALLY into scalar action
| arguments, so a leading locale parameter would shift every {slug} arg.
*/

$siteRouteMap = [
    ['/', [PageController::class, 'home'], 'home'],

    // Explicit standard pages (about/contact are dedicated methods — a
    // ->defaults('slug') scalar would receive the LOCALE positionally).
    ['/about', [PageController::class, 'about'], 'about'],
    ['/contact', [PageController::class, 'contact'], 'contact'],

    // Legal + landing page families.
    ['/legal/{slug}', [PageController::class, 'legal'], 'legal.page'],
    ['/p/{slug}', [PageController::class, 'landing'], 'landing.page'],

    // Services catalog (04-modules/02-services-module.md §3): hub → family
    // → leaf; immigration children resolve via their hub parent slug.
    ['/services', [ServiceController::class, 'hub'], 'services.hub'],
    ['/services/{slug}', [ServiceController::class, 'family'], 'services.family'],
    ['/services/{parent}/{slug}', [ServiceController::class, 'leaf'], 'services.leaf'],

    // Cities + housing (04-modules/10-cities-content.md §3). Housing unit
    // paths use ULID refs (inventory churns; slugs would 301 forever).
    ['/cities', [CityController::class, 'citiesHub'], 'cities.hub'],
    ['/cities/{slug}', [CityController::class, 'city'], 'cities.city'],
    ['/housing/verified', [CityController::class, 'verified'], 'housing.verified'],
    ['/housing', [CityController::class, 'housing'], 'housing.index'],
    ['/housing/{unit}', [CityController::class, 'unit'], 'housing.unit'],

    // Site search (03-technical-specs/08-search.md §3): noindex, follow.
    ['/search', SiteSearch::class, 'search'],

    // Careers + team (04-modules/06-hr-employee-module.md §3): per-job
    // pages never 404 for open/paused/closed postings; drafts are 404.
    ['/careers', [CareersController::class, 'index'], 'careers.index'],
    ['/careers/{slug}', [CareersController::class, 'show'], 'careers.show'],
    ['/team/{employee}', [CareersController::class, 'person'], 'careers.person'],

    // Editorial (04-modules/07-blog-news.md §3): dated blog permalinks +
    // /news + archives; dated-URL mismatches 301 to the canonical path.
    ['/blog', [BlogController::class, 'index'], 'blog.index'],
    ['/blog/category/{slug}', [BlogController::class, 'category'], 'blog.category'],
    ['/blog/tag/{slug}', [BlogController::class, 'tag'], 'blog.tag'],
    ['/blog/{year}/{month}/{slug}', [BlogController::class, 'show'], 'blog.show'],
    ['/news/{slug}', [BlogController::class, 'newsShow'], 'news.show'],

    // RSS journal feed (07-blog-news §6, AEO surface): real dates, real
    // authors, validator-friendly conditional GET.
    ['/feed', [BlogController::class, 'feed'], 'blog.feed'],

    // CSR (04-modules/09-csr-module.md §3).
    ['/csr', [CsrController::class, 'index'], 'csr.index'],
    ['/csr/{slug}', [CsrController::class, 'story'], 'csr.story'],
];

// 1. Canonical EN surfaces — unprefixed, nameless.
foreach ($siteRouteMap as [$path, $action]) {
    Route::get($path, $action);
}

// 2. Localized mirrors — every enabled locale prefixes the SAME route set.
//    Permissive regex here + request-time Route::bind validation: freezing
//    the enabled-locale list into a boot-time regex breaks any boot that
//    runs before the locales table is seeded (tests, first-run provisioning).
Route::prefix('{locale?}')
    ->where(['locale' => '[a-z]{2,3}'])
    ->group(function () use ($siteRouteMap): void {
        foreach ($siteRouteMap as [$path, $action, $name]) {
            Route::get($path, $action)->name($name);
        }
    });

// Money-path confirmations (04-modules/03-leads-crm.md §3): honest
// next-step pages, never a silent redirect. Noindex utility surfaces
// (unprefixed by contract — the lead's locale already rode the form).
Route::get('/thank-you', ThankYouController::class)
    ->middleware('throttle:public-writes')->name('thank-you');
Route::get('/newsletter/confirm/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// /dev/components — visual-state gallery, local/dev only, always
// noindex (05-design-system/02-ui-components.md §3).
if (app()->isLocal() || config('sewa.dev_routes', false)) {
    Route::view('/dev/components', 'dev.components')->name('dev.components');
}

// Public status page (06-hosting-deployment §9) — honest transparency,
// watched by UptimeRobot. Cached 30s inside the controller.
Route::get('/status', StatusController::class)
    ->name('status');

// Fallback: redirects map → 404 (never a dead end). Prefixed misses
// (/ja/no-such-page) land here too — LocaleResolver already set the
// locale from the path, so the 404 view renders localized chrome.
Route::fallback(RedirectController::class);
