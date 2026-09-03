<?php

/*
|--------------------------------------------------------------------------
| CircuitBreaker contract tests (05-security-reliability.md §2.3)
|--------------------------------------------------------------------------
| The array cache store (phpunit.xml CACHE_STORE=array) keeps breaker state
| inside the test app — no RefreshDatabase needed. Carbon::setTestNow
| drives the OPEN → HALF-OPEN time travel.
*/

use App\Support\Locks\CircuitBreaker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
});

afterEach(function (): void {
    Carbon::setTestNow(); // unfreeze time for the next test
});

test('counts consecutive failures and opens the breaker at the threshold', function (): void {
    Carbon::setTestNow('2025-06-01 10:00:00');

    $task = fn (): string => throw new RuntimeException('downstream down');

    for ($i = 0; $i < CircuitBreaker::FAILURE_THRESHOLD; $i++) {
        $result = CircuitBreaker::call('email', $task, fn (): string => 'safe');

        expect($result)->toBe('safe');
    }

    expect((int) Cache::get('sewa.breaker.email.failures'))->toBe(5)
        ->and(Cache::has('sewa.breaker.email.opened_at'))->toBeTrue();
});

test('an open breaker serves the fallback without invoking the task', function (): void {
    Carbon::setTestNow('2025-06-01 10:00:00');

    $task = fn (): string => throw new RuntimeException('downstream down');

    for ($i = 0; $i < CircuitBreaker::FAILURE_THRESHOLD; $i++) {
        CircuitBreaker::call('email', $task, fn (): string => 'safe');
    }

    $taskRan = false;

    $result = CircuitBreaker::call(
        'email',
        function () use (&$taskRan): string {
            $taskRan = true;

            return 'live';
        },
        fn (): string => 'safe',
    );

    expect($taskRan)->toBeFalse()
        ->and($result)->toBe('safe');
});

test('an open breaker without a fallback throws a RuntimeException', function (): void {
    Carbon::setTestNow('2025-06-01 10:00:00');

    // Opened 60s ago — deep inside the OPEN window.
    Cache::put('sewa.breaker.gbp.opened_at', Carbon::now()->subSeconds(60));

    expect(fn (): mixed => CircuitBreaker::call('gbp', fn (): string => 'fine'))
        ->toThrow(RuntimeException::class, 'Circuit open: gbp');
});

test('after the half-open delay a single probe is allowed and success closes the breaker', function (): void {
    Carbon::setTestNow('2025-06-01 10:00:00');

    $task = fn (): string => throw new RuntimeException('downstream down');

    for ($i = 0; $i < CircuitBreaker::FAILURE_THRESHOLD; $i++) {
        CircuitBreaker::call('ai', $task, fn (): string => 'safe');
    }

    // Time-travel past HALF_OPEN_AFTER (5 min) by rewinding opened_at.
    Cache::put('sewa.breaker.ai.opened_at', Carbon::now()->subMinutes(6));

    $taskRan = false;

    $result = CircuitBreaker::call(
        'ai',
        function () use (&$taskRan): string {
            $taskRan = true;

            return 'recovered';
        },
        fn (): string => 'safe',
    );

    expect($taskRan)->toBeTrue()
        ->and($result)->toBe('recovered')
        // Closed: failure counter and open marker are both forgotten.
        ->and(Cache::has('sewa.breaker.ai.failures'))->toBeFalse()
        ->and(Cache::has('sewa.breaker.ai.opened_at'))->toBeFalse();
});

test('only one half-open probe is admitted per window — other callers get the fallback', function (): void {
    Carbon::setTestNow('2025-06-01 10:00:00');

    Cache::put('sewa.breaker.abl.opened_at', Carbon::now()->subMinutes(6));
    // Another caller already holds the probe admission token.
    Cache::add('sewa.breaker.abl.probe', 1, CircuitBreaker::OPEN_SECONDS);

    $taskRan = false;

    $result = CircuitBreaker::call(
        'abl',
        function () use (&$taskRan): string {
            $taskRan = true;

            return 'live';
        },
        fn (): string => 'safe',
    );

    expect($taskRan)->toBeFalse()
        ->and($result)->toBe('safe');
});

test('a failed half-open probe re-opens the breaker immediately', function (): void {
    Carbon::setTestNow('2025-06-01 10:00:00');

    Cache::put('sewa.breaker.ably.opened_at', Carbon::now()->subMinutes(6));

    $result = CircuitBreaker::call(
        'ably',
        fn (): string => throw new RuntimeException('still down'),
        fn (): string => 'safe',
    );

    expect($result)->toBe('safe')
        ->and(Cache::has('sewa.breaker.ably.opened_at'))->toBeTrue();

    // The breaker is OPEN again: the next call never touches the task.
    $taskRan = false;

    CircuitBreaker::call(
        'ably',
        function () use (&$taskRan): string {
            $taskRan = true;

            return 'live';
        },
        fn (): string => 'safe',
    );

    expect($taskRan)->toBeFalse();
});

test('success while closed clears the failure counter', function (): void {
    Carbon::setTestNow('2025-06-01 10:00:00');

    CircuitBreaker::call('mail', fn (): string => throw new RuntimeException('smtp hiccup'), fn (): string => 'safe');
    CircuitBreaker::call('mail', fn (): string => 'sent', fn (): string => 'safe');

    expect(Cache::has('sewa.breaker.mail.failures'))->toBeFalse()
        ->and(Cache::has('sewa.breaker.mail.opened_at'))->toBeFalse();
});
