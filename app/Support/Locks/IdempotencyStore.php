<?php

namespace App\Support\Locks;

use App\Support\Locks\Exceptions\IdempotencyConflictException;
use Closure;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Idempotency store for every public + app write (05-security-reliability.md
 * §2.2): an `Idempotency-Key` collapses duplicate submits — double-clicks,
 * flaky mobile networks, client retries — into ONE executed task. The first
 * result is cached for 24h (86400s default) with the request fingerprint;
 * replays return the original result instead of producing a duplicate
 * (the reference platform lost duplicate leads this way).
 *
 * Fingerprint mismatch on the SAME key is a client bug or a replay attack:
 * an IdempotencyConflictException is thrown, which the M3 controllers map
 * to HTTP 409 Conflict (04-api-spec.md §1 error table).
 *
 * Concurrency note: two perfectly simultaneous requests with the same key
 * may both execute the task (the task itself must be transactional per
 * §2.1 — this store collapses the *result*, not the write lock). M3
 * controllers wrap the transactional write, so the second execution lands
 * on the same row/UNIQUE constraint and both callers receive the same
 * payload.
 */
final class IdempotencyStore
{
    /**
     * Execute $task under the idempotency key, or replay a prior result.
     *
     * @param  string  $key  Client-supplied idempotency key (ULID per
     *                       04-api-spec.md §1).
     * @param  string  $requestFingerprint  Stable hash of the request payload
     *                                      (method + route + normalized body).
     * @param  Closure  $task  The guarded write; its return value is what
     *                         replays receive.
     * @param  int  $ttlSeconds  Replay window (spec: 24h).
     * @return array{result: mixed, replayed: bool} The task result and
     *                                              whether it came from cache.
     *
     * @throws IdempotencyConflictException
     */
    public static function remember(string $key, string $requestFingerprint, Closure $task, int $ttlSeconds = 86400): array
    {
        $cacheKey = 'sewa.idem.'.sha1($key);
        $fingerprint = sha1($requestFingerprint);

        $cached = Cache::get($cacheKey);

        if (is_string($cached)) {
            $payload = @unserialize($cached);

            if (is_array($payload) && isset($payload['fingerprint'])) {
                if (! hash_equals((string) $payload['fingerprint'], $fingerprint)) {
                    throw new IdempotencyConflictException(
                        'Idempotency-Key reuse with different payload',
                    );
                }

                return ['result' => $payload['result'], 'replayed' => true];
            }
        }

        $result = $task();

        try {
            $stored = serialize(['fingerprint' => $fingerprint, 'result' => $result]);
        } catch (Throwable) {
            // The result is not serializable (closure/resource/live PDO
            // handle, …) — nothing cache-safe to replay. Store a truthy
            // marker so retries within the window still collapse to ONE
            // acknowledgement instead of re-running the write, and let the
            // client re-fetch state from its own read endpoints.
            $stored = serialize(['fingerprint' => $fingerprint, 'result' => true]);
        }

        Cache::put($cacheKey, $stored, $ttlSeconds);

        return ['result' => $result, 'replayed' => false];
    }
}
