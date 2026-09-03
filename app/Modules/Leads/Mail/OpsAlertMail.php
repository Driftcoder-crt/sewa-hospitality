<?php

namespace App\Modules\Leads\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Generic ops alert (10-email §4 monitoring.alert): SLA breaches,
 * unassigned escalations, queue problems — subject + bullet lines +
 * one deep-link. One template, many monitors (keeps the catalog lean).
 */
class OpsAlertMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<int, string>  $lines
     */
    public function __construct(
        public readonly string $alertSubject,
        public readonly array $lines,
        public readonly ?string $linkUrl = null,
        public readonly ?string $linkLabel = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: array_map(static fn (string $e): Address => new Address($e), config('sewa.emails.ops')),
            from: new Address((string) config('mail.from.address'), (string) config('mail.from.name')),
            subject: $this->alertSubject,
            tags: ['ops.alert'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.ops.alert',
            with: [
                'alertSubject' => $this->alertSubject,
                'lines' => $this->lines,
                'linkUrl' => $this->linkUrl,
                'linkLabel' => $this->linkLabel,
            ],
        );
    }
}
