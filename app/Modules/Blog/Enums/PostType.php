<?php

namespace App\Modules\Blog\Enums;

/** Editorial type (03-database-schema §4): blog explains vs news items. */
enum PostType: string
{
    case Blog = 'blog';
    case News = 'news';

    public function label(): string
    {
        return match ($this) {
            self::Blog => 'Blog',
            self::News => 'News',
        };
    }

    public function basePath(): string
    {
        return match ($this) {
            self::Blog => '/blog',
            self::News => '/news',
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
