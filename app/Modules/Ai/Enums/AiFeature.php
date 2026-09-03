<?php

namespace App\Modules\Ai\Enums;

/**
 * AI feature registry (08-ai-system/01-ai-architecture.md §3/§4 +
 * schema §10 feature list). Each feature maps to a model tier, an
 * output policy and a fallback — the map lives in config/ai.php so
 * provider/model swaps stay config, not code.
 */
enum AiFeature: string
{
    case Translate = 'translate';
    case Enrich = 'enrich';
    case Summarize = 'summarize';
    case Draft = 'draft';
    case Score = 'score';

    public function label(): string
    {
        return match ($this) {
            self::Translate => 'Content translation',
            self::Enrich => 'Lead enrichment',
            self::Summarize => 'Internal summaries',
            self::Draft => 'Content drafting',
            self::Score => 'Lead scoring hints',
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
