<?php

namespace App\Modules\Ai\Services;

use App\Modules\Ai\Enums\AiFeature;
use App\Modules\Ai\Enums\AiInvocationStatus;
use App\Modules\Ai\Models\AiInvocation;
use App\Modules\Ai\Services\Providers\OpenAiCompatibleProvider;
use App\Modules\Cms\Services\SettingsRepository;
use App\Support\Locks\CircuitBreaker;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * THE AI gateway (08-ai-system/01-ai-architecture.md §3) — one service
 * class every AI call goes through. Behavior contract:
 *
 *   kill switch   ai.enabled=false (or feature-level off) → null, zero
 *                 provider calls, forms/leads NEVER behind AI.
 *   budget        monthly per-feature caps; hard-stop → null (degrade
 *                 without exception leaks), 80% → one-time ops alert.
 *   breaker       per-provider CircuitBreaker ('ai.{provider}'): 5
 *                 consecutive failures open the circuit; half-open
 *                 probes; state changes hit the ops log.
 *   failover      primary → configured chain → null. One invocation =
 *                 ONE ledger row showing which path served.
 *   ledger        every real call → ai_invocations (status ok /
 *                 fallback / error, meta PII-scrubbed, no payloads).
 *   PII guard     assertClean before send + scrubMeta before ledger.
 *
 * Callers always receive null on any degradation — the no-AI fallback
 * belongs to the CALLING pipeline, never an exception leak.
 */
final class AiGateway
{
    /** @var list<string>|null the failover chain for this call */
    private ?array $chain = null;

    private function __construct(
        private readonly string $feature,
    ) {}

    /** Gateway::feature('translate') — the documented entry point. */
    public static function feature(string|AiFeature $feature): self
    {
        return new self($feature instanceof AiFeature ? $feature->value : $feature);
    }

    /** Explicit chain override (tests / per-call rerouting). */
    public function withFallbackChain(array $providers): self
    {
        $this->chain = $providers;

        return $this;
    }

    /**
     * Global kill switch (08-ai-system/01 §6): env AI_ENABLED is the
     * deploy-time default; the admin AI console can flip it at runtime
     * via the settings row `ai.enabled` (cached, flushed on set). One
     * toggle, platform unaffected.
     */
    public static function globallyEnabled(): bool
    {
        return (bool) app(SettingsRepository::class)
            ->get('ai.enabled', (bool) config('ai.enabled', true));
    }

    /** Feature-level gate: global switch AND feature on AND configured. */
    public function isEnabled(): bool
    {
        if (! self::globallyEnabled() || config("ai.features.{$this->feature}") === null) {
            return false;
        }

        // Per-feature kill switch (§6 "or per-feature"), same settings
        // mechanism: ai.enabled.translate = false kills only translate.
        // Env/config is the FALLBACK default — a settings row wins, but
        // flipping AI_ENABLED_* via config alone must also kill the path.
        return (bool) app(SettingsRepository::class)
            ->get("ai.enabled.{$this->feature}", (bool) config("ai.enabled.{$this->feature}", true));
    }

    /**
     * Chat through the contract. Returns null whenever the feature is
     * killed, over budget, or every provider failed — the caller's
     * no-AI path answers.
     *
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array{temperature?: float, max_tokens?: int, response_format?: array}  $options
     */
    public function chat(array $messages, array $options = []): ?AiResult
    {
        if (! $this->isEnabled()) {
            return null;
        }

        if (! AiBudget::allows($this->feature)) {
            // Hard-stop → degrade without exception leaks (§3).
            return null;
        }

        // PII boundary: refuse the call rather than leak (§5).
        PiiGuard::assertClean($options);
        PiiGuard::assertClean($this->flattenMeta($messages));

        $chain = $this->failoverChain();
        $attempted = [];
        $startedAt = microtime(true);
        $lastFailure = null;

        foreach ($chain as $providerId) {
            $provider = new OpenAiCompatibleProvider($providerId);

            if (! $provider->isConfigured()) {
                // Unconfigured providers are skipped, not attempted —
                // the error row's `provider` must say 'none' when no
                // provider was ever callable.
                continue;
            }

            $attempted[] = $providerId;
            $model = (string) (config("ai.features.{$this->feature}.model") ?: $provider->defaultModel());

            try {
                $served = CircuitBreaker::call(
                    "ai.{$providerId}",
                    fn (): array => $provider->chat($model, $messages, $options),
                );

                return $this->record(
                    status: $providerId === $chain[0] ? AiInvocationStatus::Ok : AiInvocationStatus::Fallback,
                    provider: $providerId,
                    model: $served['model'],
                    content: $served['content'],
                    tokensIn: $served['tokens_in'],
                    tokensOut: $served['tokens_out'],
                    chain: $attempted,
                    startedAt: $startedAt,
                );
            } catch (Throwable $e) {
                $lastFailure = $e;

                Log::channel('ops')->info('AI provider attempt failed — degrading along chain', [
                    'feature' => $this->feature,
                    'provider' => $providerId,
                    'exception' => $e::class,
                ]);
            }
        }

        // Everything failed: ONE error row, null to the caller.
        $this->record(
            status: AiInvocationStatus::Error,
            provider: $attempted === [] ? 'none' : (string) end($attempted),
            model: (string) config("ai.features.{$this->feature}.model", 'unknown'),
            content: null,
            tokensIn: 0,
            tokensOut: 0,
            chain: $attempted,
            startedAt: $startedAt,
            failure: $lastFailure,
        );

        return null;
    }

    /** config failover chain: [primary, ...fallbacks] filtered to known ids. */
    private function failoverChain(): array
    {
        if ($this->chain !== null) {
            return $this->chain;
        }

        $primary = (string) config('ai.primary', 'tokenrouter');
        $fallbacks = (array) config("ai.failover.{$primary}", []);
        $known = array_keys((array) config('ai.providers', []));

        return array_values(array_unique(array_intersect([$primary, ...$fallbacks], $known)));
    }

    /** The ledger write — one row per invocation, never payloads. */
    private function record(
        AiInvocationStatus $status,
        string $provider,
        string $model,
        ?string $content,
        int $tokensIn,
        int $tokensOut,
        array $chain,
        float $startedAt,
        ?Throwable $failure = null,
    ): AiResult {
        $latency = (int) round((microtime(true) - $startedAt) * 1000);

        $meta = PiiGuard::scrubMeta([
            'feature' => $this->feature,
            'chain' => array_values($chain),
            'locale' => app()->getLocale(),
            'fingerprint' => $content !== null ? PiiGuard::fingerprint($content) : null,
            'failure' => $failure !== null ? $failure::class : null,
        ]);

        AiInvocation::query()->create([
            'user_id' => auth()->id(),
            'feature' => $this->feature,
            'provider' => $provider,
            'model' => $model,
            'tokens_in' => $tokensIn,
            'tokens_out' => $tokensOut,
            'cost_estimate' => 0, // free-tier launch; paise when paid tiers land
            'status' => $status->value,
            'latency_ms' => $latency,
            'meta' => $meta,
        ]);

        // Post-invocation threshold check: the pre-call allows() saw
        // usage BEFORE this row existed, so a call that lands the month
        // at/over the 80% line would otherwise alert a month late.
        AiBudget::allows($this->feature);

        return new AiResult(
            status: $status->value,
            provider: $provider,
            model: $model,
            content: $content,
            tokensIn: $tokensIn,
            tokensOut: $tokensOut,
            latencyMs: $latency,
            chain: array_values($chain),
        );
    }

    /** Message keys flattened for the PII key-scan (values are content). */
    private function flattenMeta(array $messages): array
    {
        return collect($messages)->map(fn (array $message): array => ['role' => $message['role'] ?? ''])->all();
    }
}
