<?php

namespace App\Modules\Testimonials\Services;

use App\Modules\Testimonials\Mail\ReviewRequestMail;
use App\Modules\Testimonials\Models\ReviewRequest;
use App\Support\Mail\Jobs\SendTemplateMail;
use App\Support\Mail\MailLog;

/**
 * Review-request engine (08 doc §4.3): ONE request chain per move, one
 * polite follow-up after 7 days, HARD STOP — the invariant is enforced
 * by move_reference UNIQUE + the attempt counter. The MoveStageChanged
 * (complete) trigger wires in from the Portal module (M5); the engine
 * is fully functional now so M5 only dispatches the event.
 */
final class ReviewRequestEngine
{
    /**
     * Queue the initial request (idempotent by move reference).
     */
    public function requestFor(string $moveReference, string $email, ?string $name = null): ReviewRequest
    {
        $request = ReviewRequest::query()->firstOrCreate(
            ['move_reference' => $moveReference],
            ['recipient_email' => mb_strtolower($email), 'recipient_name' => $name, 'status' => 'queued'],
        );

        // Freshly created models don't hydrate DB column defaults —
        // attempts is null in memory until a save/refresh, so compare
        // null-safely: the initial send runs only for untouched chains.
        if (($request->attempts ?? 0) === 0 && $request->sent_at === null) {
            $this->send($request, followUp: false);
        }

        return $request;
    }

    /** Daily cron: send the single 7-day follow-up where due. */
    public function processFollowUps(): int
    {
        $due = ReviewRequest::query()
            ->where('status', 'sent')
            ->whereNotNull('sent_at')
            ->where('sent_at', '<=', now()->subDays(7))
            ->whereNull('follow_up_at')
            ->get();

        $sent = 0;

        foreach ($due as $request) {
            // Hard stop if they engaged (a done state never gets nudged).
            if ($request->status === 'done') {
                continue;
            }

            $this->send($request, followUp: true);
            $sent++;
        }

        return $sent;
    }

    private function send(ReviewRequest $request, bool $followUp): void
    {
        $key = 'review.request:'.$request->getKey().($followUp ? ':followup' : '');

        // mail_log idempotency keeps retry storms harmless.
        if (MailLog::query()->where('key', $key)->where('status', 'sent')->exists()) {
            return;
        }

        SendTemplateMail::dispatch(
            key: $key,
            template: 'review.request',
            mailable: new ReviewRequestMail($request, $followUp),
        );

        $request->forceFill([
            'attempts' => $request->attempts + 1,
            'sent_at' => $request->sent_at ?? now(),
            'follow_up_at' => $followUp ? now() : $request->follow_up_at,
            'status' => $followUp ? 'followed_up' : 'sent',
        ])->save();
    }
}
