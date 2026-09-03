<?php

namespace App\Modules\Csr\Enums;

/** NGO partnership status (09 doc §5): archived partners render in the
 * "past associations" collapsed list — honest history. */
enum PartnerStatus: string
{
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active partnership',
            self::Archived => 'Past association',
        };
    }
}
