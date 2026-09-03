<?php

namespace App\Modules\Portal\Mail;

use App\Modules\Organizations\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * portal.invite (10-email §4): magic onboarding link (72h, single-use)
 * + what the portal gives them. From support@ per the from-address map.
 */
class PortalInviteMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $email,
        public readonly string $name,
        public readonly Organization $organization,
        public readonly string $roleInOrg,
        public readonly string $acceptUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->email, $this->name ?: $this->email)],
            from: new Address((string) config('sewa.emails.support'), 'Sewa Hospitality Client Portal'),
            subject: 'You are invited to the Sewa Hospitality client portal',
            tags: ['portal.invite'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.portal.invite',
            with: [
                'name' => $this->name,
                'organization' => $this->organization,
                'roleLabel' => $this->roleLabel(),
                'acceptUrl' => $this->acceptUrl,
            ],
        );
    }

    private function roleLabel(): string
    {
        return match ($this->roleInOrg) {
            'manager' => 'Mobility manager — organization-wide view',
            'billing' => 'Billing contact — invoices and payments',
            default => 'Relocating employee — your own move',
        };
    }
}
