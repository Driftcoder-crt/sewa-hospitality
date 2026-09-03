<?php

namespace App\Support\Analytics\Jobs;

use App\Modules\Careers\Models\JobApplication;
use App\Modules\Leads\Models\Lead;
use App\Support\Analytics\MeasurementProtocol;
use App\Support\Queue\QueueHardened;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Server-side conversion events (02-analytics-plan.md §1.2 + §3):
 * money-path events fire from Laravel AFTER the DB write, never from a
 * button click. Consent-checked upstream (§4: no MP calls for rejected
 * users), PII-free (§1.1), queued on `syncs`, breaker-guarded.
 */
final class ReportConversion implements ShouldQueue
{
    use QueueHardened;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600];

    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $event,
        public readonly string $subjectId,
        public readonly array $params = [],
    ) {
        // Queue on the `syncs` channel (§2: analytics never rides the
        // default queue). Constructor assignment, NOT a class-property
        // redeclaration: Queueable declares $queue untyped with a null
        // default, and PHP treats a differing class redeclaration as an
        // incompatible trait-composition fatal.
        $this->queue = 'syncs';
    }

    public static function forLead(Lead $lead): ?self
    {
        // §4: consent-checked — a subject who never consented never
        // generates a measurement-protocol call.
        if ($lead->consent_at === null) {
            return null;
        }

        return new self('generate_lead', (string) $lead->getKey(), array_filter([
            'source' => $lead->source?->value,
            'type' => $lead->type?->value,
            'locale' => $lead->locale,
        ]));
    }

    public static function forApplication(JobApplication $application): ?self
    {
        return new self('generate_application', (string) $application->getKey(), array_filter([
            'job_slug' => $application->posting?->slug,
        ]));
    }

    public function handle(): void
    {
        MeasurementProtocol::track($this->event, $this->subjectId, $this->params);
    }
}
