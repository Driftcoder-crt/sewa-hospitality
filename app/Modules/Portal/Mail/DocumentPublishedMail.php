<?php

namespace App\Modules\Portal\Mail;

use App\Modules\Portal\Models\PortalDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * document.published (10-email §4): category + portal link — the
 * document itself NEVER travels as an attachment (04 doc §5).
 */
class DocumentPublishedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly PortalDocument $document,
        public readonly string $portalUrl,
        public readonly string $recipientName,
        public readonly string $recipientEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->recipientEmail, $this->recipientName ?: $this->recipientEmail)],
            from: new Address((string) config('sewa.emails.support'), 'Sewa Hospitality Client Portal'),
            subject: 'New document available: '.$this->document->title.' — Sewa Hospitality',
            tags: ['document.published'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.portal.document-published',
            with: [
                'document' => $this->document,
                'portalUrl' => $this->portalUrl,
                'recipientName' => $this->recipientName,
            ],
        );
    }
}
