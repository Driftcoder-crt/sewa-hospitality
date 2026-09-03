<?php

namespace App\Http\Controllers\Api;

use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * GET /v1/health — the UptimeRobot target (04-api-spec.md §2, runbook
 * post-deploy gate in 06-hosting-deployment.md §7). No auth, cheap, honest:
 * it really touches the database and the cache store and reports the live
 * queue depth and scheduler heartbeat age.
 *
 * `status` is `ok` only when the two hard dependencies (database, cache)
 * answer; queue/scheduler are informational — a stale heartbeat degrades
 * the /status page, not this probe (the queue drains every minute via
 * cron and tolerates short gaps).
 */
final class HealthController
{
    public function __invoke(): JsonResponse
    {
        $database = 'ok';
        try {
            DB::select('select 1');
        } catch (Throwable) {
            $database = 'fail';
        }

        $cache = 'ok';
        try {
            Cache::put('sewa.health.probe', true, 5);

            if (Cache::get('sewa.health.probe') !== true) {
                $cache = 'fail';
            }
        } catch (Throwable) {
            $cache = 'fail';
        } finally {
            try {
                Cache::forget('sewa.health.probe');
            } catch (Throwable) {
                // The read/put above already decided the verdict.
            }
        }

        $queueStatus = 'ok';
        $pending = null;
        try {
            $pending = (int) DB::table('jobs')->count();
        } catch (Throwable) {
            $queueStatus = 'fail';
        }

        return response()->json([
            'status' => $database === 'ok' && $cache === 'ok' ? 'ok' : 'degraded',
            'checks' => [
                'database' => $database,
                'cache' => $cache,
                'queue' => [
                    'status' => $queueStatus,
                    'pending' => $pending,
                ],
            ],
            'scheduler_heartbeat_age_seconds' => self::heartbeatAge(),
            'time' => now()->toIso8601String(),
        ], $database === 'ok' && $cache === 'ok' ? 200 : 503);
    }

    /**
     * Seconds since the scheduler's last tick (routes/console.php heartbeat
     * writes an ISO-8601 string every minute), or null when it has never
     * ticked / the value cannot be parsed. Never invented — null is
     * reported honestly.
     */
    public static function heartbeatAge(): ?int
    {
        $heartbeat = Cache::get('sewa.scheduler.heartbeat');

        if ($heartbeat === null) {
            return null;
        }

        $timestamp = $heartbeat instanceof DateTimeInterface
            ? $heartbeat->getTimestamp()
            : strtotime((string) $heartbeat);

        if ($timestamp === false) {
            return null;
        }

        return max(0, now()->getTimestamp() - $timestamp);
    }
}
