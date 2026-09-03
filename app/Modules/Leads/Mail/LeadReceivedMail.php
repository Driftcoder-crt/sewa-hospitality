<?php

namespace App\Modules\Leads\Mail;

use App\Modules\Leads\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * lead.received (10-email §4): notification to ops (+ assigned
 * consultant) — lead fields, source, locale, SLA deadline, admin
 * deep-link. Sent by the LeadCreated listener as a queued job.
 */
class LeadReceivedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Lead $lead) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: array_map(static fn (string $e): Address => new Address($e), config('sewa.emails.ops')),
            from: new Address((string) config('mail.from.address'), (string) config('mail.from.name')),
            subject: '[Lead] '.$this->lead->type->label().' — '.$this->lead->name,
            tags: ['lead.received'],
        );
    }

    public function content(): Content
    {
        $opsEmails = (array) config('sewa.emails.ops', []);

        return new Content(
            view: 'mail.leads.received',
            with: [
                'lead' => $this->lead,
                'opsEmails' => $opsEmails,
            ],
        );
    }

    /** Ops + the assigned consultant (if already picked). */
    public function build(): self
    {
        if ($this->lead->assigned_user_id && $this->lead->assignedTo) {
            $this->to($this->lead->assignedTo->email);
        }

        return $this;
    }
}
