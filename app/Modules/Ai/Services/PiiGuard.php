<?php

namespace App\Modules\Ai\Services;

use Illuminate\Support\Facades\Log;

/**
 * The PII boundary (08-ai-system/01-ai-architecture.md §5 + §7 canary
 * contract): what may NEVER reach a provider or the ledger. Applied
 * twice — once before an outbound call, once before a ledger write —
 * so a future call-site mistake fails loudly instead of leaking.
 *
 * Never send: passwords, auth tokens, document blobs (leases/visas),
 * resume files, direct client PII.
 * Allowed: public marketing text, anonymous lead metadata (city /
 * service / locale / company name), thread text with names masked,
 * aggregated ops data.
 */
final class PiiGuard
{
    /** Keys that must never appear in an outbound payload or ledger meta. */
    public const FORBIDDEN_KEYS = [
        'password', 'new_password', 'password_confirmation', 'token',
        'secret', 'authorization', 'api_key', 'apikey', 'credit_card',
        'card_number', 'cvv', 'ssn', 'aadhaar', 'pan_number', 'passport',
        'resume', 'cv_file', 'document', 'lease', 'visa', 'two_factor',
        'recovery_codes', 'session', 'cookie',
    ];

    /** Keys never allowed in ledger meta (payload-shaped). */
    private const META_FORBIDDEN = ['messages', 'prompt', 'content', 'body', 'resume_text'];

    /**
     * Assert an outbound payload carries no forbidden keys. Throws —
     * a leak attempt must stop the call, not sanitize silently.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws \RuntimeException
     */
    public static function assertClean(array $payload): void
    {
        $violations = self::violations($payload, self::FORBIDDEN_KEYS);

        if ($violations !== []) {
            Log::channel('ops')->warning('AI PII guard blocked an outbound payload', [
                'keys' => $violations,
            ]);

            throw new \RuntimeException('PII guard: forbidden keys present in AI payload: '.implode(', ', $violations));
        }
    }

    /**
     * Ledger meta is metadata + hashes only (§5): strip payload-shaped
     * keys and any forbidden key before the row is written.
     *
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public static function scrubMeta(array $meta): array
    {
        foreach (array_merge(self::FORBIDDEN_KEYS, self::META_FORBIDDEN) as $key) {
            unset($meta[$key]);
        }

        return $meta;
    }

    /**
     * Runtime canary (§7): hash a piece of content for the ledger so
     * dedupe/audit works WITHOUT storing the content itself.
     */
    public static function fingerprint(string $text): string
    {
        return substr(hash('sha256', $text), 0, 32);
    }

    /**
     * Keys are matched as separator-delimited segments (snake/kebab/camel
     * all normalize: 'auth_token', 'authToken', 'auth-token' → auth+token),
     * so genuine leak keys trip the guard while benign provider parameters
     * that merely CONTAIN a needle as a substring pass: 'max_tokens' has
     * segments [max, tokens] — no needle 'token' segment, so the standard
     * gateway option never blocks the pipeline ('visa' ⊂ 'visibility' and
     * 'lease' ⊂ 'release' were the same substring false-positive class).
     *
     * A key violates when: it equals a needle, any segment equals a needle
     * (auth_token → token), or a contiguous segment run composes a
     * multi-word needle (cardNumberHash → card_number).
     *
     * @return list<string>
     */
    private static function violations(array $payload, array $forbidden): array
    {
        $hits = [];

        $needles = array_map(
            fn (string $needle): string => str_replace(' ', '_', strtolower($needle)),
            $forbidden,
        );

        foreach (array_keys($payload) as $key) {
            $raw = (string) $key;

            // camelCase → snake before normalizing separators.
            $normalized = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $raw) ?? $raw;
            $normalized = trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower($normalized)), '_');

            if ($normalized === '' || in_array($normalized, $needles, true)) {
                $hits[] = $raw;

                continue;
            }

            $segments = explode('_', $normalized);
            $count = count($segments);

            foreach ($needles as $needle) {
                $width = substr_count($needle, '_') + 1;

                for ($i = 0; $i + $width <= $count; $i++) {
                    if (implode('_', array_slice($segments, $i, $width)) === $needle) {
                        $hits[] = $raw;

                        continue 3;
                    }
                }
            }
        }

        return $hits;
    }
}
