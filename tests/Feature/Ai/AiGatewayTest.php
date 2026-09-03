<?php

use App\Modules\Ai\Enums\AiInvocationStatus;
use App\Modules\Ai\Models\AiInvocation;
use App\Modules\Ai\Services\AiBudget;
use App\Modules\Ai\Services\AiGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

const AI_OK_PAYLOAD = [
    'choices' => [
        ['message' => ['content' => 'AI completion content']],
    ],
    'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 80],
    'model' => 'z-ai/glm-5.3-free',
];

beforeEach(function () {
    config([
        'ai.enabled' => true,
        'ai.providers.tokenrouter.key' => 'test-tokenrouter-key',
        'ai.providers.openrouter.key' => 'test-openrouter-key',
    ]);
});

function fakeProviderPayload(): void
{
    Http::fake([
        'api.tokenrouter.com/*' => Http::response(AI_OK_PAYLOAD),
        'openrouter.ai/*' => Http::response(AI_OK_PAYLOAD),
    ]);
}

it('returns null and touches nothing under the global kill switch', function () {
    config(['ai.enabled' => false]);

    $result = AiGateway::feature('translate')->chat([['role' => 'user', 'content' => 'hello']]);

    expect($result)->toBeNull()
        ->and(AiInvocation::count())->toBe(0);
});

it('returns null and touches nothing under a per-feature kill switch', function () {
    config(['ai.enabled.translate' => false]);

    $result = AiGateway::feature('translate')->chat([['role' => 'user', 'content' => 'hello']]);

    expect($result)->toBeNull()
        ->and(AiInvocation::count())->toBe(0);
});

it('records one error row and degrades when no provider is configured', function () {
    // No keys configured at all.
    config(['ai.providers.tokenrouter.key' => null, 'ai.providers.openrouter.key' => null]);

    $result = AiGateway::feature('translate')->chat([['role' => 'user', 'content' => 'hello']]);

    expect($result)->toBeNull();

    $row = AiInvocation::query()->sole();

    expect($row->status)->toBe(AiInvocationStatus::Error)
        ->and($row->provider)->toBe('none');
});

it('fails over along the chain and logs ONE row showing the serving path', function () {
    Http::fake([
        'api.tokenrouter.com/*' => Http::response('upstream exploded', 500),
        'openrouter.ai/*' => Http::response(AI_OK_PAYLOAD),
    ]);

    $result = AiGateway::feature('translate')->chat([['role' => 'user', 'content' => 'hello']]);

    expect($result?->served())->toBeTrue()
        ->and($result->status)->toBe(AiInvocationStatus::Fallback->value)
        ->and($result->provider)->toBe('openrouter')
        ->and($result->chain)->toBe(['tokenrouter', 'openrouter'])
        ->and(AiInvocation::count())->toBe(1);

    $row = AiInvocation::query()->sole();

    expect($row->provider)->toBe('openrouter')
        ->and($row->meta['chain'])->toBe(['tokenrouter', 'openrouter']);
});

it('logs status ok when the primary serves', function () {
    fakeProviderPayload();

    $result = AiGateway::feature('translate')->chat([['role' => 'user', 'content' => 'hello']]);

    expect($result?->status)->toBe(AiInvocationStatus::Ok->value)
        ->and($result->provider)->toBe('tokenrouter')
        ->and($result->tokensIn)->toBe(120)
        ->and(AiInvocation::query()->sole()->status)->toBe(AiInvocationStatus::Ok);
});

it('hard-stops at 100% of the monthly budget without exception leaks', function () {
    config(['ai.features.translate.budget_calls' => 1]);
    fakeProviderPayload();

    expect(AiGateway::feature('translate')->chat([['role' => 'user', 'content' => 'first']]))->not()->toBeNull();

    // Budget exhausted: the next call degrades to null — no throw, no call.
    expect(AiGateway::feature('translate')->chat([['role' => 'user', 'content' => 'second']]))->toBeNull()
        ->and(AiInvocation::count())->toBe(1);
});

it('alerts ops once at the 80% budget threshold', function () {
    config(['ai.features.translate.budget_calls' => 2]);
    fakeProviderPayload();

    AiGateway::feature('translate')->chat([['role' => 'user', 'content' => 'first']]);

    $key = 'sewa.ai.budget.alert.translate.'.now()->format('Ym');

    expect(Cache::has($key))->toBeTrue();

    // Once per feature per month: the flag survives (not refired/forgotten).
    AiBudget::allows('translate');

    expect(Cache::has($key))->toBeTrue();
});

it('skips an open breaker and serves via the next provider', function () {
    fakeProviderPayload();

    // Open the tokenrouter breaker.
    Cache::put('sewa.breaker.ai.tokenrouter.opened_at', now()->toImmutable(), 3600);

    $result = AiGateway::feature('translate')->chat([['role' => 'user', 'content' => 'hello']]);

    expect($result?->provider)->toBe('openrouter')
        ->and($result->status)->toBe(AiInvocationStatus::Fallback->value);
});

it('refuses the call when a forbidden key rides the options', function () {
    $thrown = false;

    try {
        AiGateway::feature('translate')->chat([['role' => 'user', 'content' => 'hello']], [
            'password' => 'hunter2',
        ]);
    } catch (RuntimeException $e) {
        $thrown = str_contains($e->getMessage(), 'PII guard');
    }

    expect($thrown)->toBeTrue()
        ->and(AiInvocation::count())->toBe(0);
});

it('never stores payloads in the invocation ledger', function () {
    fakeProviderPayload();

    AiGateway::feature('enrich')->chat(
        [['role' => 'user', 'content' => 'customer message content that must NOT be stored']],
        ['max_tokens' => 100],
    );

    $row = AiInvocation::query()->sole();

    expect($row->meta)->not()->toHaveKey('messages')
        ->and($row->meta)->not()->toHaveKey('prompt')
        ->and(json_encode($row->meta))->not()->toContain('customer message content');
});
