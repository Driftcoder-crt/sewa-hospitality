<?php

namespace App\Modules\Ai\Jobs;

use App\Modules\Ai\Enums\AiFeature;
use App\Modules\Ai\Services\AiGateway;
use App\Modules\Ai\Services\PromptLibrary;
use App\Modules\Leads\Models\Lead;
use App\Support\Queue\QueueHardened;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Lead enrichment (08-ai-system/02 §3): suggested segment, message
 * language, one-line summary, priority hint — from ANONYMOUS metadata
 * only (city name, service name, locale, company name, message text
 * with direct PII removed). Results land in lead.enrichment.ai and
 * render in the ADVISORY "AI assist (draft)" panel on LeadDetail —
 * nothing auto-assigns, auto-scores or auto-pipelines (§3 human gate).
 *
 * Fallback: breaker open / budget stop → enrichment silently skipped;
 * the lead flow is untouched and the panel shows the paused state.
 */
final class LeadEnrich implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use QueueHardened;
    use SerializesModels;

    public int $tries = 2;

    /** @var array<int, int> */
    public array $backoff = [90];

    public function __construct(public readonly string $leadId) {}

    public function handle(): void
    {
        $lead = Lead::query()->find($this->leadId);

        if ($lead === null) {
            return;
        }

        // PII boundary (08-ai-system/01 §5): allowlisted anonymous
        // fields only — never name, email, phone, or message blobs.
        $anonymous = array_filter([
            'city' => $lead->city?->name,
            'service' => $lead->service?->name,
            'locale' => $lead->locale,
            'company' => $lead->company,
            'message' => Str::limit(
                preg_replace(
                    '/[\w\.\-]+@[\w\.\-]+|\+?\d[\d\s\-]{7,}/',
                    '[redacted]',
                    (string) $lead->message,
                ),
                1200,
            ),
        ], fn ($value): bool => $value !== null && $value !== '');

        $result = AiGateway::feature(AiFeature::Enrich)->chat(
            PromptLibrary::enrichMessages($anonymous),
            ['max_tokens' => 400],
        );

        if ($result === null) {
            return; // enrichment paused — leads flow is never affected
        }

        $suggestion = json_decode((string) ($result->content ?? ''), true);

        if (! is_array($suggestion)) {
            return;
        }

        $enrichment = is_array($lead->enrichment) ? $lead->enrichment : [];

        $enrichment['ai'] = [
            'segment' => (string) ($suggestion['segment'] ?? ''),
            'language' => (string) ($suggestion['language'] ?? ''),
            'summary' => (string) ($suggestion['summary'] ?? ''),
            'priority_hint' => (string) ($suggestion['priority_hint'] ?? ''),
            'generated_at' => now()->toIso8601String(),
            'provider' => $result->provider,
        ];

        // Advisory-only write; no status machine, no events, no scoring.
        $lead->forceFill(['enrichment' => $enrichment])->save();
    }
}
