<?php

namespace App\Support\Media;

/**
 * Media URL rewriter (ADR-004: immutable media host).
 *
 * Every public render of a conversion is served from media.sewahospitality.com
 * (09-media-pipeline §5): a static-only host with immutable hash URLs fronted
 * by Cloudflare cache-everything — a re-upload produces a NEW URL, so stale
 * pixels are impossible and no purge choreography exists. Locally and under
 * test the Laravel /storage URLs pass through untouched so uploads render
 * without the media host existing.
 */
final class MediaUrl
{
    /** Rewrite a Laravel /storage URL to the media host when it is live. */
    public static function to(string $url): string
    {
        if (str_contains($url, '/storage/') && ! app()->isLocal() && ! app()->runningUnitTests()) {
            return 'https://'.config('sewa.domains.media').substr($url, (int) strpos($url, '/storage/'));
        }

        return $url;
    }
}
