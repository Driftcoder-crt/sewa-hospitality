<?php

namespace App\Http\Middleware;

use App\Support\Analytics\Consent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers on every response (05-security-reliability §1.3):
 * CSP with per-request nonce, HSTS (6 months, preload) in production,
 * nosniff, strict referrer, minimal permissions policy.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(Str::random(24));
        View::share('cspNonce', $nonce);
        app()->instance('sewa.csp_nonce', $nonce);

        $response = $next($request);

        $media = rtrim((string) config('sewa.domains.media'), '/');
        $isHttps = $request->isSecure() || app()->isProduction();

        // Analytics endpoints enter script-src/connect-src ONLY when the
        // visitor explicitly consented AND a measurement id is configured
        // (02-analytics-plan §1.1 consent-first; strict elsewhere).
        $analyticsScript = '';
        $analyticsConnect = '';

        if (Consent::analyticsGranted($request) && Consent::configured()) {
            $analyticsScript = ' https://www.googletagmanager.com';
            $analyticsConnect = ' https://www.google-analytics.com https://*.google-analytics.com https://www.googletagmanager.com';
        }

        $csp = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "img-src 'self' data: blob: https:{$media}",
            // 'unsafe-eval' BY RECORDED DECISION: Livewire's bundled
            // Alpine evaluates reactive expressions (x-data/x-show/
            // @click) through new Function()/AsyncFunction — without
            // it the CSP throws EvalError and every reactive element
            // site-wide freezes (observed on the live build). The
            // nonce'd script-src discipline still applies to page
            // scripts; the eval allowance is engine-only, no
            // remote-origin loosening.
            "script-src 'self' 'unsafe-eval' 'nonce-{$nonce}' https://challenges.cloudflare.com{$analyticsScript}",
            // style-src stays 'unsafe-inline' BY RECORDED DECISION (M6):
            // the spec CSP contract (§1.3) mandates nonce'd SCRIPTS only;
            // styles need inline allowance for dynamic width bars,
            // Alpine :style mutations and the Turnstile widget.
            "style-src 'self' 'unsafe-inline'",
            "font-src 'self'",
            'frame-src https://challenges.cloudflare.com https://www.youtube-nocookie.com',
            "connect-src 'self' https://challenges.cloudflare.com{$analyticsConnect}",
            'upgrade-insecure-requests',
        ]);

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');
        $response->headers->set('X-Frame-Options', 'DENY');

        if (app()->isProduction() && $isHttps) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=15552000; includeSubDomains; preload',
            );
        }

        return $response;
    }
}
