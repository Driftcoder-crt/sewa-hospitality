<?php

namespace App\Modules\Careers\Enums;

/** Employee directory states (03-database-schema §4). */
enum EmployeeStatus: string
{
    case Active = 'active';
    case Notice = 'notice';
    case Alumni = 'alumni';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Notice => 'On notice',
            self::Alumni => 'Alumni',
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
