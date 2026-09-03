<?php

namespace App\Modules\Cms\Enums;

/**
 * Menu locations (03-technical-specs/03-database-schema.md §2
 * "menus.location"): header | footer | sitemap.
 */
enum MenuLocation: string
{
    case Header = 'header';
    case Footer = 'footer';
    case Sitemap = 'sitemap';

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
            self::Header => 'Header navigation',
            self::Footer => 'Footer columns',
            self::Sitemap => 'Sitemap page tree',
        };
    }
}
