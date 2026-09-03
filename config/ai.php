<?php

/*
|--------------------------------------------------------------------------
| AI layer configuration (08-ai-system/01-ai-architecture.md)
|--------------------------------------------------------------------------
| Providers are CONFIG, not code (§2): every provider speaks the
| OpenAI-compatible chat-completions format, so swapping TokenRouter →
| OpenRouter → any compatible endpoint is a .env change. Model selection
| per feature (§4), monthly budgets per feature (§3 — alert at 80%,
| hard-stop at 100%), and the global kill switch all live here.
|
| RECORDED DEVIATION: the first-party Laravel AI SDK is NOT on the
| locked composer allowlist (M5 doctrine) — a thin native HTTP adapter
| (app/Modules/Ai/Services/Providers/OpenAiCompatibleProvider.php)
| speaks the same OpenAI-compatible format instead. Provider/model
| swaps remain config-only per the architecture contract.
|
| PII rule (§5): no passwords, tokens, document blobs or client PII
| ever reaches a provider — PiiGuard + the invocation ledger enforce.
*/

return [

    // Global kill switch — one toggle, platform unaffected (§6).
    // AI_ENABLED=false degrades every feature to its no-AI path.
    'enabled' => (bool) env('AI_ENABLED', true),

    // Shared-hosting PHP timeout contract (§3): 30s chat cap — long
    // tasks belong on the `ai` queue, never a web request.
    'timeout' => (int) env('AI_TIMEOUT_SECONDS', 30),
    'connect_timeout' => (int) env('AI_CONNECT_TIMEOUT', 10),

    // Primary provider id (the failover chain starts here).
    'primary' => env('AI_PROVIDER_PRIMARY', 'tokenrouter'),

    'providers' => [
        'tokenrouter' => [
            'base_url' => env('AI_BASE_URL_PRIMARY', 'https://api.tokenrouter.com/v1'),
            'key' => env('AI_KEY_PRIMARY'),
            'default_model' => env('AI_MODEL_PRIMARY', 'z-ai/glm-5.3-free'),
        ],
        'openrouter' => [
            'base_url' => env('AI_BASE_URL_SECONDARY', 'https://openrouter.ai/api/v1'),
            'key' => env('AI_KEY_SECONDARY'),
            'default_model' => env('AI_MODEL_SECONDARY', 'z-ai/glm-5.3-free'),
        ],
    ],

    // Provider failover chains (§3): primary → configured fallbacks →
    // native no-AI path. One invocation = one ledger row showing the
    // serving path.
    'failover' => [
        'tokenrouter' => ['openrouter'],
        'openrouter' => [],
    ],

    /*
     | Feature → model/capability map (§4). Every feature carries a
     | no-AI fallback in its consuming pipeline; budgets are MONTHLY
     | (tokens and calls; 0 = uncapped).
     */
    'features' => [
        'translate' => [
            'model' => env('AI_MODEL_TRANSLATE', 'z-ai/glm-5.3-free'),
            'budget_tokens' => (int) env('AI_BUDGET_TRANSLATE_TOKENS', 2_000_000),
            'budget_calls' => (int) env('AI_BUDGET_TRANSLATE_CALLS', 5_000),
        ],
        'enrich' => [
            'model' => env('AI_MODEL_ENRICH', 'z-ai/glm-5.3-free'),
            'budget_tokens' => (int) env('AI_BUDGET_ENRICH_TOKENS', 500_000),
            'budget_calls' => (int) env('AI_BUDGET_ENRICH_CALLS', 2_000),
        ],
        'summarize' => [
            'model' => env('AI_MODEL_SUMMARIZE', 'z-ai/glm-5.3-free'),
            'budget_tokens' => (int) env('AI_BUDGET_SUMMARIZE_TOKENS', 200_000),
            'budget_calls' => (int) env('AI_BUDGET_SUMMARIZE_CALLS', 1_000),
        ],
        'draft' => [
            'model' => env('AI_MODEL_DRAFT', 'z-ai/glm-5.3-free'),
            'budget_tokens' => (int) env('AI_BUDGET_DRAFT_TOKENS', 500_000),
            'budget_calls' => (int) env('AI_BUDGET_DRAFT_CALLS', 1_000),
        ],
        'score' => [
            'model' => env('AI_MODEL_SCORE', 'z-ai/glm-5.3-free'),
            'budget_tokens' => (int) env('AI_BUDGET_SCORE_TOKENS', 200_000),
            'budget_calls' => (int) env('AI_BUDGET_SCORE_CALLS', 1_000),
        ],
    ],

    // Monthly budget alert threshold (§3: alert at 80%, hard-stop at 100%).
    'budget_alert_ratio' => 0.8,
];
