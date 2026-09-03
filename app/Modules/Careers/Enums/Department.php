<?php

namespace App\Modules\Careers\Enums;

/** Job departments (03-database-schema §4). */
enum Department: string
{
    case Relocation = 'relocation';
    case Immigration = 'immigration';
    case Fleet = 'fleet';
    case Housing = 'housing';
    case Finance = 'finance';
    case Hr = 'hr';
    case Ops = 'ops';
    case Tech = 'tech';

    public function label(): string
    {
        return match ($this) {
            self::Relocation => 'Relocation',
            self::Immigration => 'Immigration',
            self::Fleet => 'Fleet',
            self::Housing => 'Housing',
            self::Finance => 'Finance',
            self::Hr => 'HR',
            self::Ops => 'Operations',
            self::Tech => 'Technology',
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
