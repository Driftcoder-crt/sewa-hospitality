<?php

namespace App\Modules\Leads\Listeners;

use App\Modules\Leads\Events\LeadCreated;
use App\Modules\Leads\Mail\LeadAckMail;
use App\Modules\Leads\Mail\LeadReceivedMail;
use App\Support\Mail\Jobs\SendTemplateMail;
use App\Support\Queue\QueueHardenedListener;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Lead → emails (03-leads-crm §7, 10-email §4): lead.ack to the lead +
 * lead.received to ops/consultant. Queued AFTER commit — a mail outage
 * never loses a lead, and the request never waits on SMTP.
 */
class SendLeadNotifications implements ShouldQueue
{
    use QueueHardenedListener;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600];

    public function handle(LeadCreated $event): void
    {
        $lead = $event->lead;

        // Ack to the lead — their language when a reviewed translation
        // exists (I18n M6); EN fallback for now.
        SendTemplateMail::dispatch(
            key: "lead.ack:{$lead->getKey()}",
            template: 'lead.ack',
            mailable: (new LeadAckMail($lead))->locale($lead->locale),
        );

        // Ops + consultant notification.
        SendTemplateMail::dispatch(
            key: "lead.received:{$lead->getKey()}",
            template: 'lead.received',
            mailable: new LeadReceivedMail($lead),
        );
    }
}
