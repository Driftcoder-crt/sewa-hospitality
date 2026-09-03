<?php

namespace App\Modules\Cities\Enums;

/**
 * Housing tiers (03-service-catalog.md tiers Essential/Professional/
 * Executive + schema §3): drives badges and price-band filters.
 */
enum HousingTier: string
{
    case Essential = 'essential';
    case Professional = 'professional';
    case Executive = 'executive';

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
            self::Essential => 'Essential',
            self::Professional => 'Professional',
            self::Executive => 'Executive',
        };
    }
}
