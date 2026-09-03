<?php

namespace App\Modules\Services\Enums;

/**
 * Service families (03-service-catalog.md + schema §3): the two
 * mobility hubs plus the standalone immigration sub-tree whose 3
 * children live at /services/immigration/*.
 */
enum ServiceFamily: string
{
    case EmployeeMobility = 'employee-mobility';
    case BusinessMobility = 'business-mobility';
    case Standalone = 'standalone';

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
            self::EmployeeMobility => 'Employee mobility',
            self::BusinessMobility => 'Business mobility',
            self::Standalone => 'Standalone',
        };
    }

    /** Family hub path (03-service-catalog §URLs). */
    public function path(): string
    {
        return '/services/'.$this->value;
    }
}
