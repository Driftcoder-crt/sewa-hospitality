<?php

namespace App\Modules\Blog\Enums;

/**
 * Editorial workflow (07-blog-news §4): authors submit draft → review;
 * editors approve + publish (role-gated). scheduled rows fire via the
 * single cron (cms:publish-scheduled sibling command lands with M4).
 */
enum PostStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Scheduled = 'scheduled';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Review => 'In review',
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
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
