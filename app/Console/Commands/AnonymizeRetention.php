<?php

namespace App\Console\Commands;

use App\Modules\Ai\Models\AiInvocation;
use App\Modules\Careers\Models\JobApplication;
use App\Modules\Leads\Models\Lead;
use App\Support\Audit\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * retention:anonymize — monthly, 1st 03:00 (07-queues-scheduling §3;
 * DPDP Act 2023 data-minimization + 05-security-reliability §4):
 * personal data past its retention window is anonymized in place —
 * pipeline history and audit trails survive, identities do not.
 *
 * Windows (deliberate constants — change with legal review):
 *   • leads: 24 months after last update (lost/nurture/closed-out rows).
 *   • job applications: 12 months after last update for rejected or
 *     withdrawn candidates — resume bytes are DELETED from disk.
 */
class AnonymizeRetention extends Command
{
    protected $signature = 'retention:anonymize {--dry-run : Count what would be anonymized}';

    protected $description = 'Anonymize expired leads and job applications (DPDP retention)';

    public function handle(): int
    {
        $leadCutoff = now()->subMonths(24);
        $applicationCutoff = now()->subMonths(12);

        $leadQuery = Lead::query()
            ->whereIn('status', ['lost', 'nurture'])
            ->where('updated_at', '<', $leadCutoff);

        $applicationQuery = JobApplication::query()
            ->whereIn('status', ['rejected', 'withdrawn'])
            ->where('updated_at', '<', $applicationCutoff);

        if ($this->option('dry-run')) {
            $this->info("Leads due for anonymization: {$leadQuery->count()}");
            $this->info("Applications due for anonymization: {$applicationQuery->count()}");

            return self::SUCCESS;
        }

        $leads = 0;
        $leadQuery->chunkById(200, function ($rows) use (&$leads): void {
            foreach ($rows as $lead) {
                $lead->forceFill([
                    'name' => '[anonymized]',
                    'email' => 'anonymized+'.$lead->getKey().'@retention.invalid',
                    'phone' => null,
                    'company' => null,
                    'message' => null,
                    'enrichment' => null,
                    'utm' => null,
                    'ip_hash' => null,
                    'user_agent' => null,
                ])->save();

                $leads++;
            }
        });

        $applications = 0;
        $applicationQuery->chunkById(200, function ($rows) use (&$applications): void {
            foreach ($rows as $application) {
                if ($application->resume_path) {
                    Storage::disk(JobApplication::RESUME_DISK)->delete($application->resume_path);
                }

                $application->forceFill([
                    'applicant_name' => '[anonymized]',
                    'applicant_email' => 'anonymized+'.$application->getKey().'@retention.invalid',
                    'applicant_phone' => null,
                    'cover_message' => null,
                    'resume_path' => null,
                ])->save();

                $applications++;
            }
        });

        // AI invocation ledger: 90-day purge (08-ai-system/01 §5 DPDP
        // posture — metadata + hashes only, and even that expires).
        $invocations = AiInvocation::query()
            ->where('created_at', '<', now()->subDays(AiInvocation::RETENTION_DAYS))
            ->delete();

        ActivityLogger::log('system', 'retention', null, [
            'leads_anonymized' => $leads,
            'applications_anonymized' => $applications,
            'ai_invocations_purged' => $invocations,
        ]);

        $this->info("retention:anonymize — {$leads} lead(s), {$applications} application(s) anonymized, {$invocations} AI invocation row(s) purged.");

        return self::SUCCESS;
    }
}
