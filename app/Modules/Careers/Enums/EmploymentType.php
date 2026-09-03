<?php

namespace App\Modules\Careers\Enums;

/** Employment types (03-database-schema §4). */
enum EmploymentType: string
{
    case Full = 'full';
    case Part = 'part';
    case Contract = 'contract';
    case Intern = 'intern';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Full-time',
            self::Part => 'Part-time',
            self::Contract => 'Contract',
            self::Intern => 'Internship',
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
