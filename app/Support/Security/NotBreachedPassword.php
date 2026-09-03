<?php

namespace App\Support\Security;

use App\Support\Locks\CircuitBreaker;
use Closure;
use Illuminate\Support\Facades\Http;

/**
 * HIBP breached-password rule (05-security-reliability.md §1.1: "breach-
 * list check on reset via haveibeenpwned k-anonymity — no privacy
 * leak"). Only the SHA-1 PREFIX (5 chars) leaves the server; the full
 * hash never does.
 *
 * Fail-open by contract (05-security-reliability §2.3): a HIBP outage
 * must never lock anyone out of resetting their password — the breaker
 * guards the dependency and an open breaker / failed call ACCEPTS the
 * password (complexity rules still apply) and logs an ops note.
 */
final class NotBreachedPassword
{
    public const SERVICE = 'hibp';

    private const RANGE_URL = 'https://api.pwnedpasswords.com/range/';

    public function validate(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $password = (string) $value;

            if ($password === '') {
                return; // 'required' handles empties
            }

            $hash = strtoupper(sha1($password));
            $prefix = substr($hash, 0, 5);
            $suffix = substr($hash, 5);

            $suffixes = CircuitBreaker::call(
                self::SERVICE,
                fn (): array => self::range($prefix),
                fallback: fn (): array => [], // fail-open, see docblock
            );

            foreach ($suffixes as $line) {
                [$candidateSuffix, $count] = array_pad(explode(':', trim($line), 2), 2, '0');

                if (hash_equals($suffix, strtoupper($candidateSuffix)) && (int) $count > 0) {
                    $fail('This password has appeared in a public data breach. Please choose a different one.');

                    return;
                }
            }
        };
    }

    /** @return list<string> raw suffix lines for the prefix */
    private static function range(string $prefix): array
    {
        $response = Http::timeout(5)
            ->connectTimeout(3)
            ->withHeaders(['Add-Padding' => 'true'])
            ->get(self::RANGE_URL.$prefix);

        if ($response->failed()) {
            throw new \RuntimeException('HIBP range request failed: HTTP '.$response->status());
        }

        return preg_split('/\r\n|\r|\n/', trim($response->body())) ?: [];
    }
}
