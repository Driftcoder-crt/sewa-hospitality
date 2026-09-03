<?php

namespace App\Modules\Ai\Services;

/**
 * Immutable result of one gateway call (08-ai-system/01 §3): which
 * provider/model actually served, usage, latency and the invocation
 * status — ok (primary served), fallback (a failover provider served)
 * or error (everything failed; the caller's no-AI path answered).
 */
final readonly class AiResult
{
    public function __construct(
        public string $status, // ok|fallback|error — mirrors AiInvocationStatus
        public string $provider,
        public string $model,
        public ?string $content,
        public int $tokensIn,
        public int $tokensOut,
        public int $latencyMs,
        /** @var list<string> providers attempted, in order */
        public array $chain = [],
    ) {}

    public function served(): bool
    {
        return $this->content !== null;
    }
}
