<?php

namespace App\Modules\Leads\Services;

use App\Models\User;
use App\Modules\Leads\Enums\LeadEventType;
use App\Modules\Leads\Enums\LeadSource;
use App\Modules\Leads\Enums\LeadStatus;
use App\Modules\Leads\Enums\LeadType;
use App\Modules\Leads\Events\LeadCreated;
use App\Modules\Leads\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * The one write path for leads (04-modules/03-leads-crm.md §2/§5):
 * transactional create + SLA clock + 48h dedupe flag + round-robin
 * assignment + full timeline. Form islands (Livewire) and any future
 * API write funnel through HERE — never around it.
 *
 * Error handling contract (§6): "a submission cannot fail invisibly" —
 * the DB write is the source of truth, emails are dispatched as queued
 * jobs AFTER commit; a mail outage never loses a lead.
 */
final class LeadIntakeService
{
    /**
     * Create a lead from validated public/admin input.
     *
     * @param  array<string, mixed>  $payload  Already-validated fields:
     *                                         source, type, name, email, phone?, company?, message?,
     *                                         service_id?, city_id?, locale?, utm?, consent_version?
     * @param  Request|null  $request  For ip/UA capture (hashed).
     *
     * @throws ValidationException on unrecoverable input conflict.
     */
    public function create(array $payload, ?Request $request = null): Lead
    {
        $payload['email'] = mb_strtolower(trim((string) $payload['email']));
        $payload['name'] = trim((string) $payload['name']);
        $payload['phone'] = isset($payload['phone']) ? trim((string) $payload['phone']) : null;
        $payload['company'] = isset($payload['company']) ? trim((string) $payload['company']) : null;
        $payload['type'] = $payload['type'] instanceof LeadType ? $payload['type'] : LeadType::from((string) $payload['type']);
        $payload['source'] = $payload['source'] instanceof LeadSource ? $payload['source'] : LeadSource::from((string) $payload['source']);
        $payload['locale'] = $payload['locale'] ?? app()->getLocale();

        // Dedupe: same email + phone within 48h → keep the submission but
        // flag it for one-click review (never silently drop, never spam
        // the pipeline — 03-leads-crm §5).
        $duplicateOf = null;
        if (! empty($payload['phone'])) {
            $duplicateOf = Lead::query()
                ->where('email', $payload['email'])
                ->where('phone', $payload['phone'])
                ->where('created_at', '>=', now()->subHours(48))
                ->orderByDesc('created_at')
                ->first();
        }

        $lead = DB::transaction(function () use ($payload, $request, $duplicateOf): Lead {
            $enrichment = is_array($payload['enrichment'] ?? null) ? $payload['enrichment'] : [];
            $enrichment['score_basis'] = 'heuristic';

            $lead = Lead::query()->create([
                'source' => $payload['source'],
                'type' => $payload['type'],
                'name' => $payload['name'],
                'email' => $payload['email'],
                'phone' => $payload['phone'],
                'company' => $payload['company'],
                'message' => $payload['message'] ?? null,
                'service_id' => $payload['service_id'] ?? null,
                'city_id' => $payload['city_id'] ?? null,
                'locale' => $payload['locale'],
                'status' => LeadStatus::New,
                'score' => $this->score($payload),
                'enrichment' => $enrichment,
                'merged_into_lead_id' => $duplicateOf?->getKey(),
                'idempotency_key' => $payload['idempotency_key'],
                'consent_at' => now(),
                'consent_version' => $payload['consent_version'] ?? null,
                'ip_hash' => $this->hashIp($request),
                'user_agent' => $request?->userAgent(),
                'sla_due_at' => SlaPolicy::dueFor($payload['type']),
                'utm' => $payload['utm'] ?? null,
            ]);

            $lead->logEvent(LeadEventType::Form, [
                'source' => $payload['source']->value,
                'type' => $payload['type']->value,
                'message' => str($payload['message'] ?? '')->limit(500)->toString(),
                'duplicate_of' => $duplicateOf?->getKey(),
            ], null);

            if ($duplicateOf) {
                $lead->logEvent(LeadEventType::System, [
                    'kind' => 'dedupe_candidate',
                    'of' => $duplicateOf->getKey(),
                    'note' => 'Same email + phone within 48h — merge review suggested.',
                ], null);
            }

            // Round-robin assignment among available consultants (§5):
            // least open assignments first — manual override wins later.
            $consultantId = $this->pickConsultant($payload['service_id'] ?? null);
            if ($consultantId !== null) {
                $lead->forceFill(['assigned_user_id' => $consultantId])->save();
                $lead->logEvent(LeadEventType::Assign, [
                    'assigned_to' => $consultantId,
                    'strategy' => 'round-robin',
                ], null);
            }

            return $lead;
        });

        // After commit — emails queue separately, never in the write txn
        // (03-technical-specs/07-queues-scheduling.md §2).
        LeadCreated::dispatch($lead);

        return $lead;
    }

    /** Heuristic score 0–99 (AI enrichment is optional, guarded, M6). */
    private function score(array $payload): int
    {
        $score = match ($payload['type']) {
            LeadType::QuoteRequest => 60,
            LeadType::Demo => 55,
            LeadType::Callback => 45,
            default => 35,
        };

        if (! empty($payload['company'])) {
            $score += 10;
        }

        if (! empty($payload['phone'])) {
            $score += 5;
        }

        if (mb_strlen((string) ($payload['message'] ?? '')) > 200) {
            $score += 5;
        }

        return min(99, $score);
    }

    /** Privacy: store only a salted hash of the IP (05-security §1.2). */
    private function hashIp(?Request $request): ?string
    {
        $ip = $request?->ip();

        return $ip ? Hash::make($ip, ['memory_cost' => 1024, 'time_cost' => 2, 'threads' => 1]) : null;
    }

    /**
     * Round-robin: the consultant with the FEWEST open assigned leads;
     * deterministic tie-break on the most recent assignment so work
     * rotates. Returns null when no consultant exists (escalation to
     * admin happens in sla:calculate).
     */
    private function pickConsultant(?string $serviceId): ?string
    {
        $consultants = User::query()
            ->role('consultant')
            ->get(['id']);

        if ($consultants->isEmpty()) {
            return null;
        }

        $load = Lead::query()
            ->active()
            ->whereIn('assigned_user_id', $consultants->pluck('id'))
            ->whereIn('status', [LeadStatus::New, LeadStatus::Contacted, LeadStatus::Qualified, LeadStatus::Proposal])
            ->selectRaw('assigned_user_id, count(*) as open_count')
            ->groupBy('assigned_user_id')
            ->pluck('open_count', 'assigned_user_id');

        return $consultants
            ->sortBy(fn (User $user): int => (int) ($load[$user->getKey()] ?? 0))
            ->first()
            ?->getKey();
    }
}
