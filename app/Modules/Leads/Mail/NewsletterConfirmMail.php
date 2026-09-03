<?php

namespace App\Modules\Leads\Mail;

use App\Modules\Leads\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** newsletter.confirm — the double opt-in email (10-email §4). */
class NewsletterConfirmMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly NewsletterSubscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address((string) $this->subscriber->email)],
            from: new Address((string) config('sewa.emails.hello'), 'Sewa Hospitality'),
            subject: 'Confirm your subscription — Sewa Hospitality',
            tags: ['newsletter.confirm'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.newsletter.confirm',
            with: [
                'subscriber' => $this->subscriber,
                'confirmUrl' => $this->subscriber->confirmUrl(),
            ],
        );
    }
}
