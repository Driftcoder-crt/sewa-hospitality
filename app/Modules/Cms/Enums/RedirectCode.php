<?php

namespace App\Modules\Cms\Enums;

/**
 * Redirect status codes (03-technical-specs/03-database-schema.md §2
 * "redirects.code"): 301 (permanent, cache-friendly — the default for
 * slug moves) or 302 (temporary campaigns).
 */
enum RedirectCode: int
{
    case Permanent = 301;
    case Temporary = 302;

    /** @return array<int, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Permanent => '301 — permanent',
            self::Temporary => '302 — temporary',
        };
    }
}
