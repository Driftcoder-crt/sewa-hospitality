<?php

namespace App\Modules\Leads\Enums;

/**
 * Lead type (03-database-schema §5). The type — not the source — sets
 * the published SLA promise (03-leads-crm §4.4): contact 2 business
 * hours, quote 4, callback 2.
 */
enum LeadType: string
{
    case Enquiry = 'enquiry';
    case Newsletter = 'newsletter';
    case Callback = 'callback';
    case QuoteRequest = 'quote_request';
    case Demo = 'demo';

    public function label(): string
    {
        return match ($this) {
            self::Enquiry => 'Enquiry',
            self::Newsletter => 'Newsletter',
            self::Callback => 'Callback',
            self::QuoteRequest => 'Quote request',
            self::Demo => 'Demo',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
