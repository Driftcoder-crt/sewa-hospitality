<?php

namespace App\Modules\Cms\Enums;

/**
 * Page types (03-technical-specs/03-database-schema.md §2 "pages.type").
 * standard  — regular marketing/site pages (/, /about, /contact)
 * landing   — campaign pages served at /p/{slug}
 * legal     — privacy/cookies/terms served at /legal/{page}
 * about     — long-form identity pages (kept distinct for template +
 *             schema-graph selection; renders like standard).
 */
enum PageType: string
{
    case Standard = 'standard';
    case Landing = 'landing';
    case Legal = 'legal';
    case About = 'about';

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
            self::Standard => 'Standard',
            self::Landing => 'Landing (/p/{slug})',
            self::Legal => 'Legal (/legal/{slug})',
            self::About => 'About',
        };
    }
}
