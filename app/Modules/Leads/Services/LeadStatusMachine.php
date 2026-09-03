<?php

namespace App\Modules\Leads\Services;

use App\Modules\Leads\Enums\LeadStatus;
use InvalidArgumentException;

/**
 * Lead status machine (04-modules/03-leads-crm.md §5):
 *
 *   new → contacted → qualified → proposal → won
 *    │         │           │           │
 *    └─────────┴───────────┴── lost(reason) / nurture
 *
 * Rules (error-locks doctrine: invalid transitions are REJECTED, not
 * silently coerced):
 *   • forward moves along the pipeline, plus lost/nurture from any
 *     non-terminal stage;
 *   • lost can reopen to contacted or nurture (ops correction, logged);
 *   • nurture can return to contacted;
 *   • won is terminal AND requires enrichment.deal_reference
 *     (organization link or quote ref — data hygiene; Billing/Portal
 *     land in M5 and will attach the formal references).
 */
final class LeadStatusMachine
{
    private const array TRANSITIONS = [
        LeadStatus::New->value => [LeadStatus::Contacted, LeadStatus::Qualified, LeadStatus::Proposal, LeadStatus::Won, LeadStatus::Lost, LeadStatus::Nurture],
        LeadStatus::Contacted->value => [LeadStatus::Qualified, LeadStatus::Proposal, LeadStatus::Won, LeadStatus::Lost, LeadStatus::Nurture],
        LeadStatus::Qualified->value => [LeadStatus::Proposal, LeadStatus::Won, LeadStatus::Lost, LeadStatus::Nurture],
        LeadStatus::Proposal->value => [LeadStatus::Won, LeadStatus::Lost],
        LeadStatus::Nurture->value => [LeadStatus::Contacted, LeadStatus::Qualified, LeadStatus::Lost],
        LeadStatus::Lost->value => [LeadStatus::Contacted, LeadStatus::Nurture],
        LeadStatus::Won->value => [],
    ];

    public static function canTransition(LeadStatus $from, LeadStatus $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from->value] ?? [], true);
    }

    /** @return list<LeadStatus> valid targets from $from. */
    public static function targets(LeadStatus $from): array
    {
        return self::TRANSITIONS[$from->value] ?? [];
    }

    /**
     * Guard one transition; throws on illegal moves so callers (Livewire
     * actions, API writes) surface a 422 instead of corrupting the pipeline.
     */
    public static function assertTransition(LeadStatus $from, LeadStatus $to, ?array $enrichment = null): void
    {
        if (! self::canTransition($from, $to)) {
            throw new InvalidArgumentException("Illegal lead status transition [{$from->value} → {$to->value}].");
        }

        if ($to === LeadStatus::Won) {
            $reference = is_array($enrichment) ? ($enrichment['deal_reference'] ?? null) : null;

            if (! is_string($reference) || trim($reference) === '') {
                throw new InvalidArgumentException('A won lead requires an organization link or quote reference (deal_reference).');
            }
        }
    }
}
