<?php

namespace App\Modules\Leads\Mail;

use App\Modules\I18n\Services\UiStrings;
use App\Modules\Leads\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * lead.ack (03-technical-specs/10-email.md §4): warm acknowledgment to
 * the lead — what happens next, the SLA promise, reply-to hello@.
 *
 * Locale serving (04-modules/11-multilingual.md §5 "forms" row): the
 * ack renders in the LEAD's language when a HUMAN-REVIEWED email
 * translation exists (machine strings never touch outbound mail);
 * otherwise the EN template serves plus the "reply in any language"
 * note — a machine-only ack never sends as final.
 */
class LeadAckMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Lead $lead) {}

    public function envelope(): Envelope
    {
        $locale = (string) ($this->lead->locale ?: 'en');

        return new Envelope(
            to: [new Address((string) $this->lead->email, (string) $this->lead->name)],
            from: new Address((string) config('sewa.emails.hello'), 'Sewa Hospitality'),
            replyTo: [new Address((string) config('sewa.emails.hello'), 'Sewa Hospitality')],
            subject: UiStrings::get('email', 'lead.ack_subject', $locale, 'We received your enquiry — Sewa Hospitality'),
            tags: ['lead.ack'],
        );
    }

    public function content(): Content
    {
        $locale = (string) ($this->lead->locale ?: 'en');
        $s = fn (string $key, string $default): string => UiStrings::get('email', $key, $locale, $default);

        $strings = [
            'greeting' => $s('lead.ack_greeting', 'Dear :name,'),
            'intro' => $s('lead.ack_intro', 'Thank you for reaching out to Sewa Hospitality. We have received your :kind and a consultant is being assigned.'),
            'next' => $s('lead.ack_next', 'What happens next:'),
            'step_1' => $s('lead.ack_step_1', 'We review the details you shared.'),
            'step_2' => $s('lead.ack_step_2', 'A consultant contacts you within our published response window (:window).'),
            'step_3' => $s('lead.ack_step_3', 'You get a clear plan — no obligations, no surprises.'),
            'urgent' => $s('lead.ack_urgent', "If anything is urgent in the meantime, call us at :phone — we're happy to help."),
        ];

        // Reviewed localized body? (null when the ack would render EN-only.)
        $localizedBody = UiStrings::get('email', 'lead.ack_body_intro', $locale);

        return new Content(
            view: 'mail.leads.ack',
            with: [
                'lead' => $this->lead,
                'strings' => $strings,
                // The honest fallback note (11-multilingual §5): shown when
                // the ack body is EN even though the lead wrote to us in
                // another language.
                'reply_note' => $localizedBody === null && $locale !== 'en'
                    ? $s('lead.ack_note', 'Feel free to reply in any language — our consultants will answer in yours.')
                    : null,
            ],
        );
    }
}
