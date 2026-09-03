<?php

namespace App\Modules\Billing\Mail;

use App\Modules\Billing\Models\Invoice;
use App\Modules\Portal\Services\PortalAudience;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * invoice.issued (10-email §4): org billing contact — PDF attached
 * (immutable snapshot) + portal link. From billing@ per the
 * from-address map.
 */
class InvoiceIssuedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly string $pdfPath,
        public readonly string $portalUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->billingRecipients(),
            from: new Address((string) config('sewa.emails.billing'), 'Sewa Hospitality Billing'),
            subject: 'Invoice '.$this->invoice->number.' — Sewa Hospitality',
            tags: ['invoice.issued'],
        );
    }

    /**
     * Org billing members (billing role first, managers fallback); the
     * billing identity address only when the org has no portal members.
     *
     * @return list<Address>
     */
    private function billingRecipients(): array
    {
        $recipients = Collection::make(PortalAudience::billingRecipients((string) $this->invoice->organization_id))
            ->map(static fn (array $r): Address => new Address($r['email'], $r['name']))
            ->values()
            ->all();

        if ($recipients === []) {
            $recipients = [new Address((string) config('sewa.emails.billing'), 'Sewa Hospitality Billing')];
        }

        return $recipients;
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.billing.invoice-issued',
            with: ['invoice' => $this->invoice, 'portalUrl' => $this->portalUrl],
        );
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('local', $this->pdfPath)
                ->as($this->invoice->number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
