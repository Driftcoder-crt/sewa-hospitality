<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 2FA is mandatory for super-admin/admin (05-security-reliability §1.1,
 * 04-modules/05-admin-panel.md §5). Accounts without a confirmed TOTP
 * device are parked on the security bootstrap screen until enrolled.
 */
class EnsureTwoFactorConfirmed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $requiredRoles = (array) config('sewa.admin.two_factor_roles', ['super-admin', 'admin']);

        if (! $user->hasAnyRole($requiredRoles)) {
            return $next($request);
        }

        if ($user->two_factor_confirmed_at === null) {
            if ($request->routeIs('admin.security') || $request->routeIs('login')) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                abort(403, 'Two-factor authentication must be enabled for this role.');
            }

            return redirect()->route('admin.security')
                ->with('status', __('Two-factor authentication is required for your role.'));
        }

        return $next($request);
    }
}
