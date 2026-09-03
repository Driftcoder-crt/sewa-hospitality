<?php

namespace App\Modules\I18n\Enums;

/**
 * UI-string namespaces (03-technical-specs/03-database-schema.md §10):
 * the four surfaces a string can belong to. The namespace browser in
 * the admin (11-multilingual §6.3) groups by this column.
 */
enum UiNamespace: string
{
    case Site = 'site';
    case Portal = 'portal';
    case Admin = 'admin';
    case Email = 'email';

    public function label(): string
    {
        return match ($this) {
            self::Site => 'Public site',
            self::Portal => 'Client portal',
            self::Admin => 'Admin panel',
            self::Email => 'Transactional email',
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
