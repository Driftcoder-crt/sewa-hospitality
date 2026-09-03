<?php

namespace App\Support\Security;

use App\Support\Locks\CircuitBreaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Server-side Cloudflare Turnstile verification — error lock #3 on every
 * public form (05-security-reliability.md §1.2).
 *
 * Failure policy (honest, per environment):
 *   • Missing secret in local/testing — a config fault that cannot be fixed
 *     mid-flow; verification is SKIPPED so developer/test flows keep moving
 *     (ops warning logged).
 *   • Missing secret in staging/production — verification fails CLOSED.
 *     Submitting without a verifier is a config fault, not a user problem.
 *   • Cloudflare outage — guarded by the `turnstile` circuit breaker.
 *     `fail_mode=grace` (default) fails OPEN so an external outage can
 *     never block a user flow (§2.6 graceful-degradation matrix; error
 *     lock #2); the honeypot + time-trap + rate limits still protect the
 *     form. `fail_mode=strict` fails CLOSED for high-paranoia windows.
 *
 * Note: Cloudflare siteverify tokens are single-use — replays of the same
 * token are rejected by Cloudflare itself, so no extra replay ledger is
 * kept here.
 */
final class TurnstileVerifier
{
    /**
     * Verify a Turnstile token issued to the browser widget.
     *
     * @param  string|null  $token  `cf-turnstile-response` from the form.
     * @param  string|null  $ip  Visitor IP (Cloudflare siteverify `remoteip`).
     * @return bool True only when Cloudflare answers `success: true`, or
     *              when degrading per the policy above.
     */
    public static function verify(?string $token, ?string $ip = null): bool
    {
        $secret = (string) config('sewa.turnstile.secret');

        if ($secret === '') {
            if (app()->isLocal() || app()->runningUnitTests()) {
                Log::channel('ops')->warning('Turnstile secret missing — verification skipped');

                return true;
            }

            Log::channel('ops')->error('Turnstile secret missing in "'.app()->environment().'" — failing CLOSED (config fault)');

            return false;
        }

        if ($token === null || trim($token) === '') {
            return false;
        }

        return CircuitBreaker::call(
            service: 'turnstile',
            task: static function () use ($secret, $token, $ip): bool {
                // Transport failures (timeout/DNS/reset) propagate to the
                // circuit breaker, which counts them and applies the
                // fallback policy. A business rejection (`success: false`)
                // is a plain `false` result — it does NOT trip the breaker.
                $response = Http::asForm()->timeout(5)->post((string) config('sewa.turnstile.verify_url'), [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ]);

                return $response->json('success') === true;
            },
            fallback: static function (): bool {
                if ((string) config('sewa.turnstile.fail_mode') === 'strict') {
                    return false;
                }

                Log::channel('ops')->warning('Turnstile breaker open — failing OPEN (grace mode); honeypot remains enforced');

                return true;
            },
        );
    }
}
