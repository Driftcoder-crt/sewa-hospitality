<?php

namespace App\Modules\I18n\Services;

use App\Modules\I18n\Models\Locale;

/**
 * Path-based locale URL algebra (11-multilingual §5: "URLs: path prefix
 * only — never parameter or cookie-based locales"). One place knows how
 * to strip, swap and build locale-prefixed paths: the footer switcher,
 * the suggestion banner, hreflang fallbacks and the /locale chooser all
 * delegate here so the rule can never drift apart.
 *
 * URL shape: `/` (en, x-default) and `/{code}/…` for every other enabled
 * locale. Unknown prefixes never occur on matched routes (the {locale}
 * constraint + LocaleResolver both validate against Locale::isEnabled).
 */
final class LocaleUrls
{
    /**
     * The request path with any enabled-locale prefix removed:
     * 'ja/services/x' → 'services/x', 'services' → 'services', '/' → ''.
     * Traversal segments ('..') and null bytes are stripped — the path
     * algebra feeds redirects and hreflang and must never emit them.
     */
    public static function stripPrefix(string $path): string
    {
        $path = str_replace("\0", '', $path);
        $path = trim($path, '/');

        if ($path === '') {
            return '';
        }

        $segments = explode('/', $path);

        if (count($segments) > 0 && Locale::isEnabled($segments[0])) {
            array_shift($segments);
        }

        // Traversal guard: '..' segments never survive the algebra.
        $segments = array_values(array_filter(
            $segments,
            fn (string $segment): bool => $segment !== '..' && $segment !== '.',
        ));

        return implode('/', $segments);
    }

    /** Prefixed path for a locale: 'en' → '/', 'ja' → '/ja', 'ja' + 'services/x' → '/ja/services/x'. */
    public static function localized(string $code, string $path): string
    {
        $clean = self::stripPrefix($path);

        if ($code === Locale::DEFAULT) {
            return $clean === '' ? '/' : '/'.$clean;
        }

        return '/'.$code.($clean === '' ? '' : '/'.$clean);
    }

    /** The current request path expressed under another locale (switcher/banner). */
    public static function swap(string $code): string
    {
        return self::localized($code, request()->path() === '/' ? '/' : (string) request()->path());
    }
}
