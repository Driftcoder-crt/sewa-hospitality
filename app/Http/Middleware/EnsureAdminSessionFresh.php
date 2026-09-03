<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin idle timeout — 2 hours (05-security-reliability §1.1).
 * Tracks per-request activity in the session; silent expiry logs the
 * user out and lands them on the login screen with a clear message.
 */
class EnsureAdminSessionFresh
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $timeout = (int) config('sewa.admin.idle_timeout_minutes', 120) * 60;
            $last = (int) $request->session()->get('sewa.admin.last_activity', 0);

            if ($last > 0 && (time() - $last) > $timeout) {
                Auth::guard('web')->logoutCurrentDevice();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->with('status', __('Your admin session expired after inactivity. Please sign in again.'));
            }

            $request->session()->put('sewa.admin.last_activity', time());
        }

        return $next($request);
    }
}
