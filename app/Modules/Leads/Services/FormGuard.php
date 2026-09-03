<?php

namespace App\Modules\Leads\Services;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Spam guard for every public form (05-security-reliability §1.2 /
 * error lock #3): honeypot + time-trap + layered rate limits. Turnstile
 * is verified separately (TurnstileVerifier) — this guard never fails
 * a real user: the honeypot path fakes success silently (bots must
 * learn nothing), and rate limits answer with a readable retry message.
 */
final class FormGuard
{
    /** Honeypot empty AND the form was open for ≥ min_seconds. */
    public static function human(string $honeypot, float $openedAt): bool
    {
        if (trim($honeypot) !== '') {
            return false;
        }

        return (microtime(true) - $openedAt) >= (float) config('sewa.forms.min_seconds', 2);
    }

    /**
     * Layered limiter: 5/min/IP + 20/h/IP per bucket
     * (config/sewa.php forms.*). Returns false when over the cap.
     */
    public static function allowed(string $bucket): bool
    {
        $key = $bucket.'|'.(string) request()->ip();
        $perMinute = (int) config('sewa.forms.per_minute', 5);
        $perHour = (int) config('sewa.forms.per_hour', 20);

        if (RateLimiter::tooManyAttempts($key.':m', $perMinute)) {
            return false;
        }

        if (RateLimiter::tooManyAttempts($key.':h', $perHour)) {
            return false;
        }

        RateLimiter::hit($key.':m', 60);
        RateLimiter::hit($key.':h', 3600);

        return true;
    }
}
