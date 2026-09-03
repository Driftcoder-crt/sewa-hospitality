<?php

namespace App\Support\Locks;

use Carbon\CarbonImmutable;
use Closure;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Circuit breaker for every external dependency (05-security-reliability.md
 * §2.3: AI provider, Ably, email provider, GBP/Google APIs, …).
 *
 * State machine (cache keys per service):
 *   CLOSED    — task runs; each Throwable increments
 *               `sewa.breaker.{service}.failures` (TTL 86400s). At
 *               FAILURE_THRESHOLD consecutive failures the breaker OPENS
 *               (`sewa.breaker.{service}.opened_at`) and an ops warning
 *               fires (12-monitoring.md alert matrix: "Breaker open").
 *   OPEN      — while opened_at is younger than HALF_OPEN_AFTER the task is
 *               never attempted: the fallback runs, or a RuntimeException
 *               surfaces when no fallback was provided.
 *   HALF-OPEN — after HALF_OPEN_AFTER the breaker allows ONE probe attempt
 *               per OPEN_SECONDS window. Admission uses the database cache
 *               driver's atomic `Cache::add` on `sewa.breaker.{service}.probe`
 *               so concurrent callers during the probe window keep receiving
 *               the fallback. A successful probe CLOSES the breaker (all keys
 *               forgotten); a failed probe re-opens it immediately.
 *
 * Accepted race: the half-open single-probe admission is atomic only on
 * lock-capable/atomic stores (database cache `add`). Two processes could
 * theoretically interleave between `add` and the probe on a degraded store —
 * this is accepted: worst case is one extra downstream request per minute,
 * never double side effects, because tasks are idempotent per §2.2.
 */
final class CircuitBreaker
{
    /** Consecutive Throwables (while CLOSED) that open the breaker. */
    public const FAILURE_THRESHOLD = 5;

    /** Seconds a single half-open probe admission is held. */
    public const OPEN_SECONDS = 60;

    /** Seconds an OPEN breaker waits before allowing a half-open probe. */
    public const HALF_OPEN_AFTER = 300;

    /** TTL of the failure counter (a day of quiet is long enough to forget). */
    private const FAILURE_TTL_SECONDS = 86400;

    /**
     * Read-only peek: is the breaker currently OPEN (or half-open-crowded)?
     * Callers use this to SKIP work that shouldn't even be attempted —
     * e.g. marketing newsletter sends pause while the mail breaker is open
     * (10-email.md §5: transactional takes priority).
     */
    public static function isOpen(string $service): bool
    {
        return Cache::get("sewa.breaker.{$service}.opened_at") !== null;
    }

    /**
     * Run $task guarded by the breaker; degrade to $fallback on failure.
     *
     * @param  string  $service  Dependency id, e.g. 'turnstile', 'ai', 'ably'.
     * @param  Closure  $task  The guarded call. Any Throwable it throws
     *                         counts as a dependency failure.
     * @param  Closure|null  $fallback  Degrade path (cached value, queue
     *                                  hand-off, fail-open/false, …). When
     *                                  null, Throwables rethrow.
     * @return mixed The task result, or the fallback result on failure.
     *
     * @throws RuntimeException When OPEN/HALF-OPEN-crowded and no fallback.
     * @throws Throwable The original exception when CLOSED, failed, and no
     *                   fallback was provided (never swallowed silently).
     */
    public static function call(string $service, Closure $task, ?Closure $fallback = null): mixed
    {
        $openedAtKey = "sewa.breaker.{$service}.opened_at";
        $failuresKey = "sewa.breaker.{$service}.failures";
        $probeKey = "sewa.breaker.{$service}.probe";

        $openedAt = Cache::get($openedAtKey);

        if ($openedAt !== null) {
            if (self::secondsSince($openedAt) < self::HALF_OPEN_AFTER) {
                // OPEN: short-circuit without touching the dependency.
                return self::degrade($service, $fallback);
            }

            // HALF-OPEN: exactly one probe attempt per OPEN_SECONDS.
            if (! Cache::add($probeKey, 1, self::OPEN_SECONDS)) {
                return self::degrade($service, $fallback);
            }

            try {
                $result = $task();
            } catch (Throwable $e) {
                // Probe failed: re-open immediately and keep degrading.
                Cache::put($openedAtKey, CarbonImmutable::now());
                Log::channel('ops')->warning('CircuitBreaker probe failed — re-opened', [
                    'service' => $service,
                    'exception' => $e::class,
                ]);

                return $fallback !== null ? $fallback() : throw $e;
            }

            // Probe succeeded: close the breaker fully.
            Cache::forget($openedAtKey);
            Cache::forget($failuresKey);
            Cache::forget($probeKey);

            return $result;
        }

        // CLOSED
        try {
            $result = $task();
        } catch (Throwable $e) {
            $failures = self::incrementFailures($failuresKey);

            if ($failures >= self::FAILURE_THRESHOLD) {
                Cache::put($openedAtKey, CarbonImmutable::now());
                Log::channel('ops')->warning('CircuitBreaker opened', [
                    'service' => $service,
                    'failures' => $failures,
                ]);
            }

            // Rethrow the original unless the caller can degrade — a
            // provided fallback always wins (§2.6 graceful degradation).
            return $fallback !== null ? $fallback() : throw $e;
        }

        // Success (a `false`/business-rejection result is NOT a failure):
        // remember the dependency as healthy again.
        Cache::forget($failuresKey);
        Cache::forget($openedAtKey);
        Cache::forget($probeKey);

        return $result;
    }

    /**
     * Serve the fallback, or fail loudly with a typed RuntimeException
     * when the caller gave no degrade path.
     *
     * @throws RuntimeException
     */
    private static function degrade(string $service, ?Closure $fallback): mixed
    {
        if ($fallback === null) {
            throw new RuntimeException("Circuit open: {$service}");
        }

        return $fallback();
    }

    private static function incrementFailures(string $key): int
    {
        // `add` is the atomic first-write; `put` covers the increment race
        // on the database cache driver (single shared-hosting MySQL).
        if (Cache::add($key, 1, self::FAILURE_TTL_SECONDS)) {
            return 1;
        }

        $next = ((int) Cache::get($key, 0)) + 1;
        Cache::put($key, $next, self::FAILURE_TTL_SECONDS);

        return $next;
    }

    /**
     * Age of the cached opened_at marker in seconds. Accepts any
     * \DateTimeInterface (Carbon/CarbonImmutable) or unix timestamp int.
     */
    private static function secondsSince(mixed $openedAt): int
    {
        $timestamp = $openedAt instanceof DateTimeInterface
            ? $openedAt->getTimestamp()
            : (int) $openedAt;

        return max(0, now()->getTimestamp() - $timestamp);
    }
}
