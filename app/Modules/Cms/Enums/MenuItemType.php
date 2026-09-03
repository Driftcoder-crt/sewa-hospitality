<?php

namespace App\Modules\Cms\Enums;

/**
 * Menu item types (03-technical-specs/03-database-schema.md §2
 * "menu_items.type"): route|page|service|custom. `service` and `city`
 * ref ids resolve once those modules exist (M2) — the editor validates
 * only what exists today, keeping the type list future-safe.
 */
enum MenuItemType: string
{
    case Route = 'route';
    case Page = 'page';
    case Service = 'service';
    case Custom = 'custom';

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
            self::Route => 'Named route',
            self::Page => 'CMS page',
            self::Service => 'Service (M2)',
            self::Custom => 'Custom URL',
        };
    }
}
