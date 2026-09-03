<?php

namespace App\Modules\Testimonials\Enums;

/** Curated testimonial states (08 doc §4.1). */
enum TestimonialStatus: string
{
    case Pending = 'pending';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Published => 'Published',
            self::Archived => 'Archived',
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
