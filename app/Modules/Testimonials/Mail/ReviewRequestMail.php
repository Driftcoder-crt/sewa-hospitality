<?php

namespace App\Modules\Testimonials\Mail;

use App\Modules\Testimonials\Models\ReviewRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * review.request (08 doc §4.3): the ONE customer-facing ask — sent to
 * the move's recipient, from the hello identity, polite, with the
 * Google review link. The single follow-up reuses this with softer
 * copy; after that the engine hard-stops.
 */
class ReviewRequestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly ReviewRequest $request,
        public readonly bool $followUp = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->request->recipient_email, (string) ($this->request->recipient_name ?: $this->request->recipient_email))],
            from: new Address((string) config('sewa.emails.hello'), 'Sewa Hospitality'),
            subject: $this->followUp
                ? 'A gentle nudge — how did your move go?'
                : 'How did your move go?',
            tags: ['review.request', $this->followUp ? 'follow-up' : 'initial'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.testimonials.review-request',
            with: [
                'request' => $this->request,
                'followUp' => $this->followUp,
                'reviewUrl' => 'https://g.page/r/'.(string) config('services.google.gbp_place_id', 'SEWA').'/review',
            ],
        );
    }
}
