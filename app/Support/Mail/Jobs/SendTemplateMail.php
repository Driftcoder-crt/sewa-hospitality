<?php

namespace App\Support\Mail\Jobs;

use App\Support\Mail\MailDispatcher;
use App\Support\Queue\QueueHardened;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * One queued email send (queue `emails`, 03-technical-specs/
 * 07-queues-scheduling.md §2): tries=5, backoff 60/300/900/1800/3600,
 * retry_until 24h — "an email must eventually go". All idempotency and
 * breaker logic lives in MailDispatcher; this job is the retry wrapper.
 */
class SendTemplateMail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use QueueHardened;
    use SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900, 1800, 3600];

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addHours(24);
    }

    public function __construct(
        public readonly string $key,
        public readonly string $template,
        public readonly Mailable $mailable,
        public readonly bool $marketing = false,
    ) {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        MailDispatcher::send($this->key, $this->template, $this->mailable, $this->marketing);
    }

    public function failed(Throwable $exception): void
    {
        // Sentry picks this up via the integration; a row in the ops
        // digest closes the loop (10-email §5 "pending emails" view).
        Log::channel('ops')->error('SendTemplateMail exhausted retries', [
            'key' => $this->key,
            'template' => $this->template,
            'error' => $exception->getMessage(),
        ]);
    }
}
