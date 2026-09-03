<?php

namespace App\Modules\I18n\Enums;

/**
 * UI-string review states (04-modules/11-multilingual.md §4 + schema
 * §10). `machine` rows are seeded/drafted by the translation pipeline
 * and NEVER serve public surfaces while machine-only; `human-reviewed`
 * rows carry reviewer attribution and are publishable.
 */
enum TranslationStatus: string
{
    case Machine = 'machine';
    case HumanReviewed = 'human-reviewed';

    public function label(): string
    {
        return match ($this) {
            self::Machine => 'Machine draft',
            self::HumanReviewed => 'Human reviewed',
        };
    }

    /** Machine drafts may only serve where auto-publish policy allows. */
    public function isPublishable(): bool
    {
        return $this === self::HumanReviewed;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
