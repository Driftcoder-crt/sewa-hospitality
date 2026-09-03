<?php

namespace App\Modules\Leads\Services;

use App\Modules\Leads\Enums\NewsletterStatus;
use App\Modules\Leads\Events\NewsletterSubscribed;
use App\Modules\Leads\Mail\NewsletterConfirmMail;
use App\Modules\Leads\Models\NewsletterSubscriber;
use App\Support\Mail\Jobs\SendTemplateMail;

/**
 * Newsletter double opt-in (04-modules/03-leads-crm.md §3/§4.5):
 * subscribe → pending + confirm email → clicked → confirmed. Only
 * confirmed subscribers ever receive marketing mail (10-email §1.4);
 * unsubscribe is one click and immediate.
 */
final class NewsletterService
{
    /**
     * Subscribe (or re-subscribe) an email. Idempotent at the UI level:
     * pending → resend confirm; unsubscribed → honest re-opt-in path;
     * confirmed → nothing changes.
     */
    public function subscribe(string $email, string $locale = 'en', ?string $source = null): NewsletterSubscriber
    {
        $email = mb_strtolower(trim($email));

        /** @var NewsletterSubscriber $subscriber */
        $subscriber = NewsletterSubscriber::query()->firstOrCreate(
            ['email' => $email],
            ['status' => NewsletterStatus::Pending, 'locale' => $locale, 'source' => $source],
        );

        match ($subscriber->status) {
            NewsletterStatus::Pending => $this->sendConfirm($subscriber),
            NewsletterStatus::Unsubscribed => (function () use ($subscriber): void {
                // User-initiated re-opt-in: back to pending, new confirm mail.
                $subscriber->forceFill([
                    'status' => NewsletterStatus::Pending,
                    'unsubscribed_at' => null,
                ])->save();
                $this->sendConfirm($subscriber);
            })(),
            NewsletterStatus::Confirmed, NewsletterStatus::Bounced => null,
        };

        return $subscriber->refresh();
    }

    /**
     * Double opt-in confirm via token. Returns the refreshed subscriber
     * plus whether THIS click performed the pending → confirmed
     * transition (`fresh`) — a replayed link changes nothing and the
     * status page must say so instead of re-welcoming.
     *
     * @return array{subscriber: ?NewsletterSubscriber, fresh: bool}
     */
    public function confirm(string $token): array
    {
        $subscriber = NewsletterSubscriber::query()->where('token', $token)->first();

        if (! $subscriber) {
            return ['subscriber' => null, 'fresh' => false];
        }

        if ($subscriber->status === NewsletterStatus::Pending) {
            $subscriber->forceFill([
                'status' => NewsletterStatus::Confirmed,
                'confirmed_at' => now(),
            ])->save();

            NewsletterSubscribed::dispatch($subscriber);

            return ['subscriber' => $subscriber->refresh(), 'fresh' => true];
        }

        return ['subscriber' => $subscriber->refresh(), 'fresh' => false];
    }

    /** One-click unsubscribe — honoured from ANY state. */
    public function unsubscribe(string $token): ?NewsletterSubscriber
    {
        $subscriber = NewsletterSubscriber::query()->where('token', $token)->first();

        if (! $subscriber) {
            return null;
        }

        if ($subscriber->status !== NewsletterStatus::Unsubscribed) {
            $subscriber->forceFill([
                'status' => NewsletterStatus::Unsubscribed,
                'unsubscribed_at' => now(),
            ])->save();
        }

        return $subscriber->refresh();
    }

    /**
     * Queue the double-opt-in email. The MailLog key makes retries and
     * explicit resends collision-free (one confirm per subscriber state);
     * a new confirm after a re-subscribe carries the attempt suffix.
     */
    public function sendConfirm(NewsletterSubscriber $subscriber): void
    {
        SendTemplateMail::dispatch(
            key: "newsletter.confirm:{$subscriber->getKey()}",
            template: 'newsletter.confirm',
            mailable: new NewsletterConfirmMail($subscriber),
        );
    }
}
