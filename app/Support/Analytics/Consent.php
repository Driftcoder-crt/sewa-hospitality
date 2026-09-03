<?php

namespace App\Support\Analytics;

use Illuminate\Http\Request;

/**
 * Consent-first analytics (07-marketing-trust/02-analytics-plan.md §1.1
 * + §4; 01-google-ecosystem §2 Consent Mode v2): NO tag ever fires
 * before an explicit consent choice. The sewa_consent cookie is written
 * exclusively by an explicit banner click — three honest states:
 *
 *   essential  — strictly necessary only; no analytics scripts, no MP
 *   analytics  — GA4/GTM may load (measurement only, no ads signals)
 *   all        — analytics granted (ads personalization stays off at launch)
 *
 * No cookie = undecided = no scripts at all (the strictest reading of
 * the "no tags pre-consent" gate — not even consent-mode defaults).
 */
final class Consent
{
    public const COOKIE = 'sewa_consent';

    public const ESSENTIAL = 'essential';

    public const ANALYTICS = 'analytics';

    public const ALL = 'all';

    /** Load GA4/GTM? (explicit analytics-or-better choice + an id configured). */
    public static function analyticsGranted(?Request $request = null): bool
    {
        $state = self::state($request);

        return $state === self::ANALYTICS || $state === self::ALL;
    }

    /** Current consent state from the request cookie (undecided → null). */
    public static function state(?Request $request = null): ?string
    {
        $request ??= request();
        $state = (string) $request->cookie(self::COOKIE, '');

        return in_array($state, [self::ESSENTIAL, self::ANALYTICS, self::ALL], true) ? $state : null;
    }

    /** Is any analytics surface configured at all (GA4 id or GTM id)? */
    public static function configured(): bool
    {
        return self::ga4Id() !== null || self::gtmId() !== null;
    }

    public static function ga4Id(): ?string
    {
        $id = config('sewa.analytics.ga4_id');

        return is_string($id) && str_starts_with($id, 'G-') ? $id : null;
    }

    public static function gtmId(): ?string
    {
        $id = config('sewa.analytics.gtm_id');

        return is_string($id) && str_starts_with($id, 'GTM-') ? $id : null;
    }
}
