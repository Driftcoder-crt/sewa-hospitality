<?php

namespace App\Support\Locks;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Named cache mutex — the overlap guard for everything the single hPanel
 * cron can double-fire (05-security-reliability.md §2.4: two cron ticks
 * must never double-run a job on shared hosting).
 *
 * Usage:
 *
 *   Mutex::run('db.backup', 600, fn () => $this->dump());
 *
 * When the lock cannot be acquired (a previous tick still holds it) the
 * task is skipped and `false` is returned — silence is the correct
 * behaviour for overlap, callers log their own outcomes. TTL is the
 * lock expiry in seconds: always set it above the worst-case runtime so
 * a crashed process cannot starve the next tick forever.
 */
final class Mutex
{
    /**
     * Attempt to run the task exclusively under the named lock.
     *
     * @param  string  $name  Bare lock name (no prefixing needed).
     * @param  int  $ttlSeconds  Lock expiry — cover the worst-case runtime.
     * @param  Closure  $task  The exclusive work to perform.
     * @return mixed The task's return value, or `false` when the lock
     *               was already held (task skipped).
     */
    public static function run(string $name, int $ttlSeconds, Closure $task): mixed
    {
        $lock = Cache::lock("sewa.lock.{$name}", $ttlSeconds);

        if ($lock->get()) {
            try {
                return $task();
            } finally {
                $lock->release();
            }
        }

        return false;
    }
}
