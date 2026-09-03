<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Determines which surface is being served (site|admin|app|api|media),
 * scopes the session cookie per area, shares context with views, and
 * noindexes non-public areas.
 *
 * Spec: 03-technical-specs/02-architecture.md §4 + 05-security-reliability §1.3.
 */
class SetRequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower((string) $request->host());
        $domains = config('sewa.domains');

        $area = 'site';
        foreach (['admin', 'app', 'api', 'media'] as $candidate) {
            if ($host === strtolower((string) $domains[$candidate])) {
                $area = $candidate;
                break;
            }
        }

        config(['sewa.area' => $area]);

        // Separate cookie per surface (admin cookie never shared with the
        // public site; 05-security-reliability §1.1).
        if (in_array($area, ['admin', 'app'], true)) {
            config(['session.cookie' => 'sewa_'.$area.'_session']);
        }

        View::share('sewaArea', $area);

        $response = $next($request);

        if (in_array($area, ['admin', 'app', 'api'], true)) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
