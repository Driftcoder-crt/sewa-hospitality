<?php

namespace App\Modules\Portal\Enums;

/** Operational status of a move (schema §8 — stage is progress, status is health). */
enum MoveStatus: string
{
    case Active = 'active';
    case OnHold = 'on_hold';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::OnHold => 'On hold',
            self::Cancelled => 'Cancelled',
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
