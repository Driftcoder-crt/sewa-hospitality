<?php

/*
|--------------------------------------------------------------------------
| IdempotencyStore contract tests (05-security-reliability.md §2.2)
|--------------------------------------------------------------------------
| 24h replay window: the first call executes, retries with the same key +
| fingerprint replay the stored result, and key reuse with a DIFFERENT
| payload fingerprint is a hard conflict (HTTP 409 in M3 controllers).
*/

use App\Support\Locks\Exceptions\IdempotencyConflictException;
use App\Support\Locks\IdempotencyStore;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
});

test('executes the task once and returns the fresh result', function (): void {
    $executions = 0;

    $first = IdempotencyStore::remember('lead-1', 'fingerprint-1', function () use (&$executions): array {
        $executions++;

        return ['id' => 'x'];
    });

    expect($first)->toBe(['result' => ['id' => 'x'], 'replayed' => false])
        ->and($executions)->toBe(1);
});

test('retries with the same key and fingerprint replay the original result', function (): void {
    $executions = 0;

    $task = function () use (&$executions): array {
        $executions++;

        return ['id' => 'x'];
    };

    $first = IdempotencyStore::remember('lead-1', 'fingerprint-1', $task);
    $second = IdempotencyStore::remember('lead-1', 'fingerprint-1', $task);

    expect($second)->toBe(['result' => ['id' => 'x'], 'replayed' => true])
        ->and($executions)->toBe(1)
        ->and($first['result'])->toBe($second['result']);
});

test('the same key with a different fingerprint throws IdempotencyConflictException', function (): void {
    IdempotencyStore::remember('lead-1', 'fingerprint-a', fn (): array => ['id' => 'x']);

    expect(fn (): array => IdempotencyStore::remember('lead-1', 'fingerprint-b', fn (): array => ['id' => 'y']))
        ->toThrow(IdempotencyConflictException::class, 'Idempotency-Key reuse with different payload');
});

test('different keys never collide even with identical fingerprints', function (): void {
    $a = IdempotencyStore::remember('lead-1', 'shared-fingerprint', fn (): string => 'A');
    $b = IdempotencyStore::remember('lead-2', 'shared-fingerprint', fn (): string => 'B');

    expect($a['result'])->toBe('A')
        ->and($a['replayed'])->toBeFalse()
        ->and($b['result'])->toBe('B')
        ->and($b['replayed'])->toBeFalse();
});

test('a non-serializable result stores a truthy replay marker instead of failing', function (): void {
    $first = IdempotencyStore::remember('weird-key', 'fingerprint-1', fn (): Closure => fn (): string => 'closure');

    expect($first['replayed'])->toBeFalse();

    $second = IdempotencyStore::remember('weird-key', 'fingerprint-1', fn (): string => 'never executed');

    expect($second['replayed'])->toBeTrue()
        ->and($second['result'])->toBeTrue();
});
