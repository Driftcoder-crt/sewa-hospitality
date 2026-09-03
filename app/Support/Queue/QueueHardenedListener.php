<?php

namespace App\Support\Queue;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The LISTENER variant of the QueueHardened contract (07-queues-
 * scheduling + 05-security-reliability §2). Queued LISTENERS receive a
 * different failed() signature than jobs: CallQueuedListener calls
 * $handler->failed(...array_values($data), $e) — the EVENT first, the
 * exception second — whereas jobs get failed(Throwable) alone. A listener
 * using the job-shaped trait fataled with a TypeError the first time it
 * actually failed (the exact class of bug that only a real run exposes).
 *
 * The retry properties ($tries / $backoff) live on each class: a trait
 * property colliding with a class property of the same name but different
 * default is a FATAL error in PHP.
 */
trait QueueHardenedListener
{
    /** Called when all attempts are exhausted: event first, exception second. */
    public function failed(mixed $event = null, ?Throwable $exception = null): void
    {
        Log::channel('ops')->warning('Queued listener permanently failed', [
            'listener' => static::class,
            'event' => is_object($event) ? $event::class : null,
            'exception' => $exception ? $exception::class : null,
            'message' => mb_substr((string) $exception?->getMessage(), 0, 200),
        ]);
    }
}
