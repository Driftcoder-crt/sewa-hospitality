<?php

namespace App\Modules\Careers\Enums;

/**
 * Job posting status machine (06-hr doc §4.1): draft → open → paused →
 * closed. Paused/closed keep the URL (history/SEO) but render their
 * honest state with "see similar" links — never a 404.
 */
enum JobStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Paused = 'paused';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Open => 'Open',
            self::Paused => 'Paused',
            self::Closed => 'Closed',
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
