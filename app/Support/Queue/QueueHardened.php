<?php

namespace App\Support\Queue;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Bounded-retry contract for every queued job and listener (07-queues-
 * scheduling + 05-security-reliability §2): the Hostinger worker is
 * cron-driven `queue:work --stop-when-empty`, so a queued task without
 * $tries is retried FOREVER — it never reaches failed_jobs and re-runs
 * on every schedule:run cycle (a poison task becomes a zombie loop).
 *
 * The trait deliberately provides ONLY the failed() handler. The retry
 * properties ($tries / $backoff) live on each class: a trait property
 * colliding with a class property of the same name but different default
 * is a FATAL error in PHP, and several tasks legitimately tune their own
 * schedule (SendTemplateMail tries=5 over 24h; TranslateContent 3 with
 * AI-aware backoff; LeadEnrich 2). The architecture test enforces that
 * every queued class defines its own $tries/$backoff — nothing unbounded.
 */
trait QueueHardened
{
    /** Called when all attempts are exhausted. */
    public function failed(Throwable $exception): void
    {
        Log::channel('ops')->warning('Queued task permanently failed', [
            'task' => static::class,
            'exception' => $exception::class,
            'message' => mb_substr((string) $exception->getMessage(), 0, 200),
        ]);
    }
}
