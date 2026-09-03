<?php

namespace App\Modules\Leads\Mail;

use App\Modules\Leads\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * newsletter.issue (10-email §4): a campaign issue to CONFIRMED
 * subscribers only — markdown-sourced body rendered to HTML by the
 * Newsletter manager, one-click unsubscribe mandatory on marketing mail.
 * The M3 manager checks the mail breaker before dispatching (§5).
 */
class NewsletterIssueMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly NewsletterSubscriber $subscriber,
        public readonly string $issueSubject,
        public readonly string $issueHtml,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address((string) $this->subscriber->email)],
            from: new Address((string) config('sewa.emails.hello'), 'Sewa Hospitality'),
            subject: $this->issueSubject,
            tags: ['newsletter.issue'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.newsletter.issue',
            with: [
                'issueHtml' => $this->issueHtml,
                'unsubscribeUrl' => $this->subscriber->unsubscribeUrl(),
            ],
        );
    }
}
