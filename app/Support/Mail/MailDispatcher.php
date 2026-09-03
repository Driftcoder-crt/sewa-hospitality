<?php

namespace App\Support\Mail;

use App\Support\Locks\CircuitBreaker;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * The single send path for the email catalog (03-technical-specs/
 * 10-email.md §4/§6). Rules enforced here, nowhere else:
 *
 *  1. IDEMPOTENCY — every send carries a deterministic key
 *     ("lead.ack:{id}", "application.ack:{id}", …). A mail_log row with
 *     that key short-circuits the send: queue retries and cron
 *     double-fires can never double-send.
 *  2. BREAKER — the provider transport sits behind the `mail` circuit
 *     breaker. Marketing sends PAUSE while the breaker is open
 *     (transactional takes priority, §5); transactional sends rethrow so
 *     the queue retries with its backoff ladder.
 *  3. PRIVACY — recipients are logged hashed, never as live addresses.
 */
final class MailDispatcher
{
    /**
     * Send (or replay) one catalog email.
     *
     * @param  string  $key  Deterministic idempotency key, e.g. "lead.ack:{id}".
     * @param  string  $template  Catalog key for the log, e.g. "lead.ack".
     * @param  Mailable  $mailable  Ready-to-send mailable (envelope+content).
     * @param  bool  $marketing  True for marketing sends (pause when the
     *                           breaker is open; transactional default false).
     * @return bool True when sent (or replayed), false when skipped.
     *
     * @throws Throwable Transport failure (transactional) — the queued
     *                   job retries per the emails ladder.
     */
    public static function send(string $key, string $template, Mailable $mailable, bool $marketing = false): bool
    {
        // Idempotency first — cheap and it makes retries safe.
        if (MailLog::query()->where('key', $key)->where('status', 'sent')->exists()) {
            return true;
        }

        // Marketing pauses while the provider is down (10-email §5).
        if ($marketing && CircuitBreaker::isOpen('mail')) {
            Log::channel('ops')->warning('Mail breaker open — marketing send paused', ['key' => $key]);

            return false;
        }

        $recipients = collect($mailable->envelope()->to)
            ->map(function (object $to): string {
                if ($to instanceof Address) {
                    return (string) $to->address;
                }

                // Defensive: assoc arrays (envelope data), named-props objects.
                return (string) ($to->address ?? $to['address'] ?? '');
            })
            ->filter()
            ->implode(', ');

        try {
            CircuitBreaker::call(service: 'mail', task: static function () use ($mailable): void {
                Mail::send($mailable);
            });
        } catch (Throwable $e) {
            // Transactional failures surface to the queue retry ladder —
            // never swallowed (§5 fallback chain).
            Log::channel('ops')->error('Mail send failed', [
                'key' => $key,
                'template' => $template,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        MailLog::query()->create([
            'key' => $key,
            'template' => $template,
            'recipient_hash' => $recipients === '' ? null : hash('sha256', $recipients.'|'.(string) config('app.key')),
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return true;
    }
}
