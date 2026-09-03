<?php

namespace App\Modules\Services\Enums;

/**
 * Service publish states (04-modules/02-services-module.md §4 status
 * chips): draft → published → archived. Archive (never delete while
 * leads reference the tag — §5) 301s to the family hub via the
 * Redirect manager.
 */
enum ServiceStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

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
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    public function isPublic(): bool
    {
        return $this === self::Published;
    }
}
