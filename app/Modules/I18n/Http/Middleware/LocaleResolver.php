<?php

namespace App\Modules\I18n\Http\Middleware;

use App\Modules\I18n\Models\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locale resolution for the PUBLIC SITE (04-modules/11-multilingual.md
 * §3 — deterministic, respectful, SEO-safe):
 *
 *   (1) explicit path prefix /ja/…    → serve that locale
 *   (2) user cookie (sewa_locale)     → suggestion only
 *   (3) Accept-Language quality match → suggestion only
 *   (4) Cloudflare country header     → tiebreaker for suggestions
 *   (5) en (x-default)                → always the unprefixed locale
 *
 * Detection NEVER silently swaps the served language and NEVER
 * redirects: an unprefixed URL is canonically EN — cookie/header/geo
 * signals only drive the one-time suggestion banner ("View in
 * 한국어?"), and the sewa_locale cookie is set exclusively by an
 * explicit visitor action (the /locale/{code} chooser). English stays
 * reachable in one click from every surface; no IP lockouts exist.
 *
 * When a prefix IS present the middleware also seeds URL::defaults so
 * every route() call in the request keeps the prefix (11-multilingual
 * §5 — path-prefix locales only).
 */
final class LocaleResolver
{
    /** Cookie stores an explicit choice for a year; banner then rests. */
    public const COOKIE = 'sewa_locale';

    /** CF country → launch-locale hints (tiebreaker tier only). */
    private const GEO_MAP = [
        'JP' => 'ja', 'KR' => 'ko', 'TR' => 'tr', 'IN' => 'hi',
        'SA' => 'ar', 'AE' => 'ar', 'EG' => 'ar', 'QA' => 'ar',
        'KW' => 'ar', 'OM' => 'ar', 'BH' => 'ar', 'JO' => 'ar',
        'LB' => 'ar', 'IQ' => 'ar', 'MA' => 'ar', 'DZ' => 'ar',
        'TN' => 'ar', 'LY' => 'ar', 'SD' => 'ar', 'YE' => 'ar',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isNonSiteArea($request)) {
            return $next($request);
        }

        $segments = explode('/', trim($request->path(), '/'));
        $prefix = $segments[0] !== '' && Locale::isEnabled($segments[0]) ? $segments[0] : null;

        if ($prefix !== null) {
            // (1) Explicit path choice — the URL IS the locale contract.
            app()->setLocale($prefix);
            URL::defaults(['locale' => $prefix]);
            $suggested = null;
        } else {
            app()->setLocale(Locale::DEFAULT);
            URL::defaults(['locale' => null]);
            $suggested = $this->suggestionFor($request);
        }

        // Drop the locale segment from the route parameter bag BEFORE the
        // controller runs. Laravel spreads route parameters POSITIONALLY
        // into scalar action arguments (ControllerDispatcher: ...array_values) —
        // with {locale} first in the bag, every {slug}-style action received
        // the LOCALE as its first argument and the whole prefixed surface
        // 404'd/500'd. The middleware already carries the locale (above);
        // URL::defaults keeps localized route() generation unaffected.
        $request->route()?->forgetParameter('locale');

        view()->share('i18n', [
            'current' => app()->getLocale(),
            'suggested' => $suggested,
            'dismissed' => $request->session()->get('i18n.banner_dismissed', false),
            'locales' => Locale::enabledSwitcher(),
        ]);

        return $next($request);
    }

    /**
     * Suggested locale for the banner: cookie → Accept-Language → geo.
     * Never suggests en (nothing to suggest), never suggests when the
     * visitor already chose (cookie present) — the banner is one-time.
     */
    private function suggestionFor(Request $request): ?string
    {
        $cookie = (string) $request->cookie(self::COOKIE, '');

        if ($cookie !== '' && Locale::isEnabled($cookie)) {
            return null;
        }

        return $this->fromAcceptLanguage($request->header('Accept-Language'))
            ?? $this->fromGeo($request->header('CF-IPCountry') ?? $request->header('HTTP_CF_IPCOUNTRY'));
    }

    /**
     * Accept-Language with RFC-quality matching. Primary subtags match
     * enabled locales (ja-KR → ja); highest q wins; equal q resolves to
     * the FIRST match in header order — deterministic for every weird
     * list the wild produces (11-multilingual §8).
     */
    private function fromAcceptLanguage(?string $header): ?string
    {
        if ($header === null || trim($header) === '') {
            return null;
        }

        $best = null;
        $bestQuality = -1.0;

        foreach (explode(',', $header) as $part) {
            $segments = explode(';', trim($part));
            $tag = strtolower(trim($segments[0]));

            if ($tag === '' || $tag === '*') {
                continue;
            }

            $quality = 1.0;
            if (isset($segments[1]) && preg_match('/q\s*=\s*([0-9.]+)/', $segments[1], $m) === 1) {
                $quality = (float) $m[1];
            }

            $primary = explode('-', $tag)[0];

            // RFC 7231: q=0 means explicitly NOT acceptable — never a
            // suggestion candidate.
            if ($quality <= 0.0) {
                continue;
            }

            if (! Locale::isEnabled($primary) || $quality <= $bestQuality) {
                continue;
            }

            $best = $primary;
            $bestQuality = $quality;
        }

        return $best !== Locale::DEFAULT ? $best : null;
    }

    /** Geo is a TIEBREAKER only — never the sole auto-switch reason. */
    private function fromGeo(?string $country): ?string
    {
        if ($country === null || $country === '' || strtoupper($country) === 'XX') {
            return null;
        }

        return self::GEO_MAP[strtoupper($country)] ?? null;
    }

    /**
     * Admin/portal/api areas resolve their own locale context (staff
     * and client preferences) — the public detection chain must not
     * touch them. Domain checks cover production; path checks cover
     * the collapsed local/staging hosts.
     */
    private function isNonSiteArea(Request $request): bool
    {
        foreach (['admin', 'app', 'api', 'media'] as $key) {
            $domain = config("sewa.domains.{$key}");

            if ($domain !== null && $domain !== '' && strcasecmp((string) $request->getHost(), $domain) === 0) {
                return true;
            }
        }

        return $request->is('admin/*')
            || $request->is('portal/*')
            || $request->is('api/*')
            || $request->is('v1/*')
            || $request->is('up')
            || $request->is('live*');
    }
}
