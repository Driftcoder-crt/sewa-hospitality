<?php

namespace App\Modules\Ai\Services;

use App\Modules\Ai\Enums\AiFeature;
use App\Modules\Ai\Models\AiInvocation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Feature-level monthly budgets (08-ai-system/01 §3): usage gauges
 * read the ai_invocations ledger; an ops alert fires ONCE at the
 * 80% threshold; ≥100% hard-stops the feature — which degrades to its
 * native no-AI path. Forms, leads and portals are NEVER behind AI, so
 * a hard-stop can never touch a client-facing flow (§3 contract).
 */
final class AiBudget
{
    /** Monthly usage gauge for the admin budget meters. */
    public static function usage(string|AiFeature $feature): array
    {
        $feature = $feature instanceof AiFeature ? $feature->value : $feature;

        [$since, $until] = self::monthWindow();

        $row = AiInvocation::query()
            ->forFeature($feature)
            ->since($since)
            ->selectRaw('COALESCE(SUM(tokens_in + tokens_out), 0) as tokens, COUNT(*) as calls')
            ->first();

        $tokens = (int) ($row->tokens ?? 0);
        $calls = (int) ($row->calls ?? 0);

        $tokenBudget = (int) config("ai.features.{$feature}.budget_tokens", 0);
        $callBudget = (int) config("ai.features.{$feature}.budget_calls", 0);

        return [
            'tokens' => $tokens,
            'calls' => $calls,
            'token_budget' => $tokenBudget,
            'call_budget' => $callBudget,
            'token_ratio' => $tokenBudget > 0 ? min(1.0, $tokens / $tokenBudget) : 0.0,
            'call_ratio' => $callBudget > 0 ? min(1.0, $calls / $callBudget) : 0.0,
            'window' => [$since->toIso8601String(), $until->toIso8601String()],
        ];
    }

    /** Hard-stop check: FALSE means the feature must degrade (no throw). */
    public static function allows(string|AiFeature $feature): bool
    {
        $feature = $feature instanceof AiFeature ? $feature->value : $feature;
        $usage = self::usage($feature);

        $overTokens = $usage['token_budget'] > 0 && $usage['tokens'] >= $usage['token_budget'];
        $overCalls = $usage['call_budget'] > 0 && $usage['calls'] >= $usage['call_budget'];

        if ($overTokens || $overCalls) {
            return false;
        }

        // 80% alert — once per feature per month (ops digest + log).
        $alertRatio = (float) config('ai.budget_alert_ratio', 0.8);

        // Integer call budgets are lumpy: 1 call of a 2-call budget is
        // only 50% by ratio, yet it is the last moment before the hard
        // stop — the floored threshold (min one call) fires the ops
        // alert there instead of never.
        $atAlert = ($usage['token_budget'] > 0 && $usage['token_ratio'] >= $alertRatio)
            || ($usage['call_budget'] > 0 && $usage['calls'] >= max(1, (int) floor($usage['call_budget'] * $alertRatio)));

        if ($atAlert) {
            $key = 'sewa.ai.budget.alert.'.$feature.'.'.now()->format('Ym');

            // TTL as a future DATETIME, never seconds-from-diff():
            // Carbon 3 diffs are SIGNED — endOfMonth()->diffInSeconds()
            // is negative here and a negative integer TTL stores as
            // already-expired (secondsUntil doesn't clamp ints), which
            // would re-alert on every call instead of once per month.
            if (Cache::add($key, 1, now()->endOfMonth()->addMinute())) {
                Log::channel('ops')->warning('AI budget alert threshold reached (80%)', [
                    'feature' => $feature,
                    'usage' => $usage,
                ]);
            }
        }

        return true;
    }

    /** First millisecond of this month → now (the budget window). */
    private static function monthWindow(): array
    {
        $since = Carbon::now()->startOfMonth();

        return [$since, Carbon::now()];
    }
}
