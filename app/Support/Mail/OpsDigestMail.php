<?php

namespace App\Support\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * ops.digest (10-email §4): daily 09:00 — leads, SLA breaches, failed
 * jobs, queue depth, reviews, zero-result searches. Composed by the
 * ops:digest command; rendered through the shared ops alert template.
 */
class OpsDigestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, string>  $sections  label => summary line
     * @param  array<int, string>  $lines  headline bullets
     */
    public function __construct(
        public readonly array $sections,
        public readonly array $lines,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), (string) config('mail.from.name')),
            subject: 'Ops digest — '.now()->format('D d M Y'),
            tags: ['ops.digest'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.ops.digest',
            with: [
                'sections' => $this->sections,
                'lines' => $this->lines,
            ],
        );
    }
}
