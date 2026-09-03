<?php

namespace App\Modules\Cities\Enums;

/**
 * Rate units (schema §3 rate_unit): night|month. Honest "from ₹X /
 * unit" display only — Billing (M5) keeps its own integer-paise ledger.
 */
enum RateUnit: string
{
    case Night = 'night';
    case Month = 'month';

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Night => 'night',
            self::Month => 'month',
        };
    }
}
