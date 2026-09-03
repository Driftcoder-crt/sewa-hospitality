<?php

namespace App\Modules\Leads\Enums;

/**
 * Newsletter subscriber states (03-leads-crm §4.5): pending → confirmed
 * is the double-opt-in gate; bounced arrives from provider feedback
 * (10-email §7); unsubscribed is one-click and always honoured.
 */
enum NewsletterStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Unsubscribed = 'unsubscribed';
    case Bounced = 'bounced';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Unsubscribed => 'Unsubscribed',
            self::Bounced => 'Bounced',
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
