<?php

namespace App\Modules\I18n\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\I18n\Http\Middleware\LocaleResolver;
use App\Modules\I18n\Models\Locale;
use App\Modules\I18n\Services\LocaleUrls;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The explicit-locale chooser (11-multilingual §3): the ONLY place the
 * sewa_locale cookie is ever set. A visitor click — banner CTA or
 * footer switcher link — is the explicit action; detection itself
 * never writes preferences. GET-only + idempotent: safe to share,
 * safe to retry, nothing to replay.
 */
final class LocaleController extends Controller
{
    /** One-time banner dismissal: session flag only — no cookie (the tests lock "cookie set only on explicit choice"). */
    public function dismiss(Request $request): RedirectResponse
    {
        $request->session()->put('i18n.banner_dismissed', true);

        // Back to the page the banner was on (dismiss is NOT a locale
        // action — it must never re-prefix or drop the visitor's path).
        // Direct hits with no referer fall back to home, never to this
        // utility route itself (self-redirect loop).
        return redirect()->back(fallback: '/');
    }

    /** Choose a locale: set the cookie (1y) and land on the localized path. */
    public function choose(Request $request, string $code): RedirectResponse
    {
        abort_unless(Locale::isEnabled($code), 404);

        $target = LocaleUrls::localized($code, (string) $request->query('to', '/'));

        // Path-only redirect target: query('to') is coerced through the
        // path algebra so an absolute URL can never leak into Location.
        // RAW value, matching the reader: sewa_locale is excluded from
        // cookie encryption (bootstrap/app.php) so JS can write it too —
        // a signed value here would DecryptException on the next request.
        $cookie = cookie(
            LocaleResolver::COOKIE,
            $code,
            60 * 24 * 365,
            '/',
            null,
            $request->isSecure(),
            true, // httpOnly — the server reads it; JS never needs it
            false,
            'lax',
        );

        return redirect()->to($target, 302)->withCookie($cookie);
    }
}
