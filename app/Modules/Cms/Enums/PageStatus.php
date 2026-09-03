<?php

namespace App\Modules\Cms\Enums;

/**
 * Publish states (04-modules/01-cms.md §5): draft → scheduled →
 * published → archived. Transitions are driven by the page editor and
 * the cms:publish-scheduled command; archive is a soft retirement that
 * keeps the revision trail.
 */
enum PageStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
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
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    /** Live to the public site (render path only serves these). */
    public function isPublic(): bool
    {
        return $this === self::Published;
    }
}
