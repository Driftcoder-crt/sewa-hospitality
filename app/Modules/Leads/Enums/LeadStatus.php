<?php

namespace App\Modules\Leads\Enums;

/**
 * Pipeline status machine (03-leads-crm §5):
 * new → contacted → qualified → proposal → won | lost(reason) | nurture.
 * Transitions are validated by LeadStatusMachine and every change is
 * logged as a lead_event. Won carries the data-hygiene rule: it requires
 * an organization link or a quote reference (enrichment.deal_reference).
 */
enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Won = 'won';
    case Lost = 'lost';
    case Nurture = 'nurture';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Qualified => 'Qualified',
            self::Proposal => 'Proposal',
            self::Won => 'Won',
            self::Lost => 'Lost',
            self::Nurture => 'Nurture',
        };
    }

    /** Pipeline order (kanban columns left → right). */
    public static function pipeline(): array
    {
        return [
            self::New,
            self::Contacted,
            self::Qualified,
            self::Proposal,
            self::Won,
            self::Lost,
            self::Nurture,
        ];
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
