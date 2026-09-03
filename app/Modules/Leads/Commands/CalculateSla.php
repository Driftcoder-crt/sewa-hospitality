<?php

namespace App\Modules\Leads\Commands;

use App\Modules\Leads\Enums\LeadEventType;
use App\Modules\Leads\Enums\LeadStatus;
use App\Modules\Leads\Events\SlaBreached;
use App\Modules\Leads\Mail\OpsAlertMail;
use App\Modules\Leads\Models\Lead;
use App\Support\Audit\ActivityLogger;
use App\Support\Mail\Jobs\SendTemplateMail;
use Illuminate\Console\Command;

/**
 * sla:calculate — hourly (03-technical-specs/07-queues-scheduling.md
 * §3): SLA breach detection on unresponsive leads + escalation for
 * unassigned leads (03-leads-crm §4.4/§5). Idempotent: each lead can
 * carry at most one sla_breached and one escalation system event, so
 * cron re-fires never double-alert.
 */
class CalculateSla extends Command
{
    protected $signature = 'sla:calculate';

    protected $description = 'Detect SLA breaches and escalate unassigned leads (hourly)';

    public function handle(): int
    {
        $breaches = 0;
        $escalations = 0;

        // 1) SLA breach: due time passed, no first response, still live.
        Lead::query()
            ->slaPending()
            ->where('sla_due_at', '<', now())
            ->with('assignedTo:id,email')
            ->chunkById(200, function ($leads) use (&$breaches): void {
                foreach ($leads as $lead) {
                    $already = $lead->events()
                        ->where('type', LeadEventType::System->value)
                        ->where('payload->kind', 'sla_breached')
                        ->exists();

                    if ($already) {
                        continue;
                    }

                    $lead->logEvent(LeadEventType::System, [
                        'kind' => 'sla_breached',
                        'due_at' => $lead->sla_due_at?->toIso8601String(),
                        'note' => 'SLA deadline passed without first response.',
                    ], null);

                    SlaBreached::dispatch($lead);
                    $breaches++;
                }
            });

        // 2) Unassigned escalation: a lead older than the threshold with
        //    nobody on it → system event + one ops alert (mail_log keyed).
        $threshold = (int) config('sewa.leads.escalate_unassigned_minutes', 15);

        Lead::query()
            ->active()
            ->whereNull('assigned_user_id')
            ->whereIn('status', [LeadStatus::New])
            ->where('created_at', '<', now()->subMinutes($threshold))
            // "No escalated event yet" — whereDoesntHave instead of
            // withCount+having: HAVING without GROUP BY is rejected by
            // SQLite outright and by MySQL 8 under ONLY_FULL_GROUP_BY.
            ->whereDoesntHave('events', fn ($query) => $query->where('payload->kind', 'escalated'))
            ->chunkById(200, function ($leads) use (&$escalations, $threshold): void {
                foreach ($leads as $lead) {
                    $lead->logEvent(LeadEventType::System, [
                        'kind' => 'escalated',
                        'note' => "Unassigned for over {$threshold} minutes — escalated to admins.",
                    ], null);

                    // One keyed alert per lead; the dispatcher makes it safe.
                    SendTemplateMail::dispatch(
                        key: "lead.escalation:{$lead->getKey()}",
                        template: 'lead.escalation',
                        mailable: new OpsAlertMail(
                            alertSubject: 'Lead unassigned — escalated to admins',
                            lines: [
                                "Lead: {$lead->name} ({$lead->type->label()} via {$lead->source->label()})",
                                'Waiting unassigned since '.$lead->created_at->format('H:i').' IST',
                            ],
                            linkUrl: rtrim((string) config('app.url'), '/').'/admin/leads/'.$lead->getKey(),
                            linkLabel: 'Assign now',
                        ),
                    );

                    $escalations++;
                }
            });

        if ($breaches > 0 || $escalations > 0) {
            ActivityLogger::log('system', 'sla_check', null, [
                'breaches' => $breaches,
                'escalations' => $escalations,
            ]);
        }

        $this->info("sla:calculate — {$breaches} breach(es), {$escalations} escalation(s).");

        return self::SUCCESS;
    }
}
