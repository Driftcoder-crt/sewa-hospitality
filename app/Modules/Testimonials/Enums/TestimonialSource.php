<?php

namespace App\Modules\Testimonials\Enums;

/** Testimonial sources (03-database-schema §6): Google reviews are
 * synced, the rest arrive through consent-gated collection. */
enum TestimonialSource: string
{
    case Google = 'google';
    case Direct = 'direct';
    case Email = 'email';
    case Form = 'form';

    public function label(): string
    {
        return match ($this) {
            self::Google => 'Google',
            self::Direct => 'Sewa client',
            self::Email => 'Email',
            self::Form => 'Feedback form',
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
