<?php

namespace App\Modules\Careers\Mail;

use App\Modules\Careers\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** application.ack (10-email §4): candidate confirmation + retention note. */
class ApplicationAckMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly JobApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address((string) $this->application->applicant_email, (string) $this->application->applicant_name)],
            from: new Address((string) config('sewa.emails.careers'), 'Sewa Hospitality Careers'),
            subject: 'We received your application — Sewa Hospitality',
            tags: ['application.ack'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.careers.ack',
            with: ['application' => $this->application],
        );
    }
}
