<?php

namespace App\Modules\Billing\Mail;

use App\Modules\Billing\Models\Invoice;
use App\Modules\Portal\Services\PortalAudience;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * invoice.reminder (10-email §4): polite, max 3 (12 doc §5 etiquette).
 * Tone de-escalates copy by rung; never threatens, never spams.
 */
class InvoiceReminderMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly int $daysPast,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->billingRecipients(),
            from: new Address((string) config('sewa.emails.billing'), 'Sewa Hospitality Billing'),
            subject: 'Gentle reminder: invoice '.$this->invoice->number.' — Sewa Hospitality',
            tags: ['invoice.reminder'],
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
            view: 'mail.billing.reminder',
            with: ['invoice' => $this->invoice, 'daysPast' => $this->daysPast],
        );
    }
}
