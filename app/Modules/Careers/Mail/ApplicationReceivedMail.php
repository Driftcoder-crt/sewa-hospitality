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
 * application.received (10-email §4): careers@ + recruiter notification
 * with a 24-hour SIGNED resume link — the resume itself is private-disk
 * PII and never travels as an attachment (09-media-pipeline §2).
 */
class ApplicationReceivedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly JobApplication $application,
        public readonly string $resumeUrl,
    ) {}

    public function envelope(): Envelope
    {
        $posting = $this->application->posting;

        return new Envelope(
            to: array_map(static fn (string $e): Address => new Address($e), config('sewa.emails.ops')),
            from: new Address((string) config('mail.from.address'), (string) config('mail.from.name')),
            subject: '[Application] '.($posting?->title ?? 'General').' — '.$this->application->applicant_name,
            tags: ['application.received'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.careers.received',
            with: [
                'application' => $this->application,
                'resumeUrl' => $this->resumeUrl,
            ],
        );
    }
}
