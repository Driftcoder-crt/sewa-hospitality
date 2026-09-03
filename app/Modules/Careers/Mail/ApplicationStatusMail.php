<?php

namespace App\Modules\Careers\Mail;

use App\Modules\Careers\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * application status update (06-hr §5): candidate-facing status email
 * fired on screening/shortlisted/interview/offer/rejected via the
 * ApplicationStatusChanged listener. Honest what's-next copy per stage.
 */
class ApplicationStatusMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly JobApplication $application,
        public readonly string $stageLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address((string) $this->application->applicant_email, (string) $this->application->applicant_name)],
            from: new Address((string) config('sewa.emails.careers'), 'Sewa Hospitality Careers'),
            subject: 'Your application update — Sewa Hospitality',
            tags: ['application.status'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.careers.status',
            with: [
                'application' => $this->application,
                'stageLabel' => $this->stageLabel,
            ],
        );
    }
}
