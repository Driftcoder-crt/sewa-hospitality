<?php

namespace App\Modules\Portal\Enums;

/** Document categories (04-client-portal §4.3; schema §8). */
enum DocumentCategory: string
{
    case Visa = 'visa';
    case Lease = 'lease';
    case Inventory = 'inventory';
    case Invoice = 'invoice';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Visa => 'Visa & immigration',
            self::Lease => 'Lease',
            self::Inventory => 'Inventory',
            self::Invoice => 'Invoice',
            self::Other => 'Other',
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
