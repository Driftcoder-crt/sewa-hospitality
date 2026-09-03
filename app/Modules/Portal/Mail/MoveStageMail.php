<?php

namespace App\Modules\Portal\Mail;

use App\Modules\Portal\Models\PortalMove;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * move.stage_changed (10-email §4): stage, what's next, portal link —
 * to the employee (and manager via cc row) per the catalog.
 */
class MoveStageMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly PortalMove $move,
        public readonly string $fromLabel,
        public readonly string $toLabel,
        public readonly string $whatsNext,
        public readonly string $portalUrl,
        public readonly string $recipientName,
        public readonly string $recipientEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->recipientEmail, $this->recipientName ?: $this->recipientEmail)],
            from: new Address((string) config('sewa.emails.support'), 'Sewa Hospitality Client Portal'),
            subject: 'Move '.$this->move->reference.' is now "'.$this->toLabel.'" — Sewa Hospitality',
            tags: ['move.stage_changed'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.portal.move-stage',
            with: [
                'move' => $this->move,
                'fromLabel' => $this->fromLabel,
                'toLabel' => $this->toLabel,
                'whatsNext' => $this->whatsNext,
                'portalUrl' => $this->portalUrl,
                'recipientName' => $this->recipientName,
            ],
        );
    }
}
