<?php

namespace App\Modules\Cities\Enums;

/**
 * Housing inventory types (04-modules/10-cities-content.md §2): the
 * three inventory lines on /housing.
 */
enum HousingType: string
{
    case ServicedApartment = 'serviced-apartment';
    case CorporateHousing = 'corporate-housing';
    case GuestHouse = 'guest-house';

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
            self::ServicedApartment => 'Serviced apartment',
            self::CorporateHousing => 'Corporate housing',
            self::GuestHouse => 'Guest house',
        };
    }
}
