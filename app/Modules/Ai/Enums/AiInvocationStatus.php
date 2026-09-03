<?php

namespace App\Modules\Ai\Enums;

/**
 * Invocation outcome (08-ai-system/01-ai-architecture.md §3 behavior
 * contract): `ok` = primary served, `fallback` = a failover provider
 * served (or the call was served after a budget stop), `error` =
 * everything failed and the native fallback answered. Every state
 * change lands in the ai_invocations ledger.
 */
enum AiInvocationStatus: string
{
    case Ok = 'ok';
    case Fallback = 'fallback';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'OK',
            self::Fallback => 'Via fallback',
            self::Error => 'Failed',
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
