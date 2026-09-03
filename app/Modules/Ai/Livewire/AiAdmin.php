<?php

namespace App\Modules\Ai\Livewire;

use App\Modules\Ai\Enums\AiFeature;
use App\Modules\Ai\Enums\AiInvocationStatus;
use App\Modules\Ai\Models\AiInvocation;
use App\Modules\Ai\Services\AiBudget;
use App\Modules\Ai\Services\AiGateway;
use App\Modules\Ai\Services\PromptLibrary;
use App\Modules\Cms\Services\SettingsRepository;
use App\Support\Audit\ActivityLogger;
use App\Support\Locks\CircuitBreaker;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * AI admin console (08-ai-system/01-ai-architecture.md §6): provider
 * status chips (breaker + last latency + monthly usage), budget gauges
 * per feature, the invocation ledger browser, the kill switches and
 * the admin-only test-call console. admin+ only (ai.manage).
 */
#[Layout('layouts.admin')]
final class AiAdmin extends Component
{
    public string $logFeature = '';

    public string $logStatus = '';

    // Test console
    public string $testFeature = 'translate';

    public string $testPrompt = '';

    public ?array $testResult = null;

    public bool $testRunning = false;

    /* ── Kill switches ─────────────────────────────────────────────── */

    public function toggleGlobalKill(): void
    {
        $this->authorize('ai.manage');

        $settings = app(SettingsRepository::class);
        $next = ! AiGateway::globallyEnabled();

        $settings->set('ai.enabled', $next, 'ai');
        ActivityLogger::log('admin', 'update', null, ['ai.enabled' => $next]);

        $this->dispatch('notify', tone: 'success', message: 'AI '.($next ? 'enabled' : 'killed — every feature degrades to its no-AI path').'.');
    }

    public function toggleFeatureKill(string $feature): void
    {
        $this->authorize('ai.manage');

        if (AiFeature::tryFrom($feature) === null) {
            return;
        }

        $settings = app(SettingsRepository::class);
        $current = (bool) $settings->get("ai.enabled.{$feature}", true);

        $settings->set("ai.enabled.{$feature}", ! $current, 'ai');
        ActivityLogger::log('admin', 'update', null, ["ai.enabled.{$feature}" => ! $current]);

        $this->dispatch('notify', tone: 'success', message: "Feature {$feature} ".(! $current ? 'enabled' : 'disabled').'.');
    }

    /* ── Test console (admin only, ledger-recorded) ────────────────── */

    public function runTest(): void
    {
        $this->authorize('ai.manage');

        $this->validate([
            'testFeature' => 'required|string',
            'testPrompt' => 'required|string|max:2000',
        ]);

        if (AiFeature::tryFrom($this->testFeature) === null) {
            $this->addError('testFeature', 'Unknown feature.');

            return;
        }

        $this->testRunning = true;

        $result = AiGateway::feature($this->testFeature)->chat(
            PromptLibrary::testMessages($this->testFeature, $this->testPrompt),
            ['max_tokens' => 500],
        );

        $this->testRunning = false;

        if ($result === null) {
            $this->testResult = ['ok' => false, 'note' => 'No provider served the call — kill switch, budget stop, breaker open or every provider failed. Check the ledger row.'];

            return;
        }

        $this->testResult = [
            'ok' => true,
            'provider' => $result->provider,
            'model' => $result->model,
            'status' => $result->status,
            'latency_ms' => $result->latencyMs,
            'tokens' => $result->tokensIn.' → '.$result->tokensOut,
            'content' => $result->content,
        ];
    }

    public function clearBreaker(string $provider): void
    {
        $this->authorize('ai.manage');

        Cache::forget("sewa.breaker.ai.{$provider}.opened_at");
        Cache::forget("sewa.breaker.ai.{$provider}.failures");
        Cache::forget("sewa.breaker.ai.{$provider}.probe");

        ActivityLogger::log('admin', 'update', null, ['ai.breaker_reset' => $provider]);
        $this->dispatch('notify', tone: 'success', message: "Breaker for {$provider} reset.");
    }

    public function render(): View
    {
        $this->authorize('ai.manage');

        $settings = app(SettingsRepository::class);
        $globalEnabled = AiGateway::globallyEnabled();

        $providers = collect((array) config('ai.providers'))
            ->map(fn (array $config, string $id): array => [
                'id' => $id,
                'base_url' => (string) $config['base_url'],
                'configured' => is_string($config['key'] ?? null) && $config['key'] !== '',
                'breaker_open' => CircuitBreaker::isOpen("ai.{$id}"),
                'primary' => $id === (string) config('ai.primary'),
                'last_latency' => AiInvocation::query()->withStatus('ok')->where('provider', $id)->latest('created_at')->value('latency_ms'),
            ]);

        $budgets = collect(AiFeature::cases())
            ->map(fn (AiFeature $feature): array => [
                'feature' => $feature,
                'usage' => AiBudget::usage($feature),
                'enabled' => (bool) $settings->get("ai.enabled.{$feature->value}", true),
            ]);

        $log = AiInvocation::query()
            ->when($this->logFeature !== '', fn ($q) => $q->forFeature($this->logFeature))
            ->when($this->logStatus !== '', fn ($q) => $q->withStatus($this->logStatus))
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        return view('ai.livewire.ai-admin', [
            'globalEnabled' => $globalEnabled,
            'providers' => $providers,
            'budgets' => $budgets,
            'log' => $log,
            'features' => AiFeature::options(),
            'statuses' => AiInvocationStatus::options(),
        ]);
    }
}
