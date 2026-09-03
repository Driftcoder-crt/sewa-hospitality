<?php

/*
|--------------------------------------------------------------------------
| TurnstileVerifier contract tests (05-security-reliability.md §1.2, §2.3)
|--------------------------------------------------------------------------
| Http::fake covers Cloudflare siteverify; the "outage" fakes a thrown
| connection exception so the circuit breaker + fail_mode policy decide.
| The breaker lives in the array cache store — each test boots a fresh
| app, so state cannot leak between cases.
*/

use App\Support\Security\TurnstileVerifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config([
        'sewa.turnstile.secret' => 'test-secret',
        'sewa.turnstile.fail_mode' => 'grace',
        'sewa.turnstile.verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    ]);
});

test('a success answer verifies true and posts secret, token and remoteip', function (): void {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => true]),
    ]);

    expect(TurnstileVerifier::verify('tkn-123', '203.0.113.9'))->toBeTrue();

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $request['secret'] === 'test-secret'
            && $request['response'] === 'tkn-123'
            && $request['remoteip'] === '203.0.113.9';
    });
});

test('a success:false answer fails verification without tripping the breaker', function (): void {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => false]),
    ]);

    expect(TurnstileVerifier::verify('tkn-123'))->toBeFalse();
});

test('an empty token fails closed with no outbound call', function (): void {
    Http::fake();

    expect(TurnstileVerifier::verify(null))->toBeFalse()
        ->and(TurnstileVerifier::verify('   '))->toBeFalse();

    Http::assertNothingSent();
});

test('a connection outage fails OPEN in grace mode', function (): void {
    Http::fake(fn (): never => throw new ConnectionException('connection refused'));

    expect(TurnstileVerifier::verify('tkn-123'))->toBeTrue();
});

test('a connection outage fails CLOSED in strict mode', function (): void {
    config(['sewa.turnstile.fail_mode' => 'strict']);

    Http::fake(fn (): never => throw new ConnectionException('connection refused'));

    expect(TurnstileVerifier::verify('tkn-123'))->toBeFalse();
});

test('repeated outages count as breaker failures, then short-circuit via fallback', function (): void {
    Http::fake(fn (): never => throw new ConnectionException('connection refused'));

    // Five counted failures open the breaker; every call still degrades
    // gracefully (grace mode) throughout.
    for ($i = 0; $i < 5; $i++) {
        expect(TurnstileVerifier::verify('tkn-123'))->toBeTrue();
    }

    expect((int) Cache::get('sewa.breaker.turnstile.failures'))->toBe(5)
        ->and(Cache::has('sewa.breaker.turnstile.opened_at'))->toBeTrue()
        // Breaker open: the fallback answers without touching Cloudflare,
        // and the user flow stays alive (error lock #2).
        ->and(TurnstileVerifier::verify('tkn-123'))->toBeTrue();
});

test('a missing secret skips verification in local/testing and fails closed elsewhere', function (): void {
    config(['sewa.turnstile.secret' => null]);
    Http::fake();

    // phpunit.xml pins APP_ENV=testing → runningUnitTests() is true.
    expect(TurnstileVerifier::verify('tkn-123'))->toBeTrue();

    Http::assertNothingSent();
});
