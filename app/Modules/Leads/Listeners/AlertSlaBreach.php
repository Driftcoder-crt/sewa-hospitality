<?php

namespace App\Modules\Leads\Listeners;

use App\Modules\Leads\Events\SlaBreached;
use App\Modules\Leads\Mail\OpsAlertMail;
use App\Support\Mail\Jobs\SendTemplateMail;
use App\Support\Queue\QueueHardenedListener;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * SLA breach → ops alert (03-leads-crm §4.4 "SLA monitor": breaches
 * list + alert). The mail_log key sla.alert:{lead} makes the hourly
 * command idempotent — one alert per breach, never a nagging loop.
 */
class AlertSlaBreach implements ShouldQueue
{
    use QueueHardenedListener;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600];

    public function handle(SlaBreached $event): void
    {
        $lead = $event->lead;

        SendTemplateMail::dispatch(
            key: "sla.alert:{$lead->getKey()}",
            template: 'sla.alert',
            mailable: new OpsAlertMail(
                alertSubject: 'SLA breached — lead overdue without response',
                lines: [
                    "Lead: {$lead->name} ({$lead->email}) — {$lead->type->label()} via {$lead->source->label()}",
                    'SLA deadline passed: '.$lead->sla_due_at?->timezone('Asia/Kolkata')->format('d M Y H:i').' IST',
                    'Status: '.$lead->status->label().' · assigned: '.($lead->assignedTo?->email ?? 'NOBODY'),
                ],
                linkUrl: rtrim((string) config('app.url'), '/').'/admin/leads/'.$lead->getKey(),
                linkLabel: 'Open lead in CRM',
            ),
        );
    }
}
