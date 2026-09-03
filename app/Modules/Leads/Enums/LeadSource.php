<?php

namespace App\Modules\Leads\Enums;

/**
 * Where a lead came from (03-database-schema §5). Drives the SLA clock
 * via SlaPolicy and the inbox filters. `import` is reserved for
 * role-gated CSV imports (03-leads-crm §4.6).
 */
enum LeadSource: string
{
    case Contact = 'contact';
    case ServicePage = 'service_page';
    case CareerNewsletter = 'career_newsletter';
    case PortalRequest = 'portal_request';
    case Campaign = 'campaign';
    case Import = 'import';

    public function label(): string
    {
        return match ($this) {
            self::Contact => 'Contact form',
            self::ServicePage => 'Service page',
            self::CareerNewsletter => 'Newsletter',
            self::PortalRequest => 'Portal request',
            self::Campaign => 'Campaign',
            self::Import => 'Import',
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
