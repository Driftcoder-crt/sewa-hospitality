# 01 — AI Architecture

**The AI layer: first-party Laravel AI SDK as the single gateway; TokenRouter/OpenRouter with `z-ai/glm-5.3-free` as a provider config (not code); budget guards, circuit breakers, and human gates everywhere — AI is a utility that can never block the platform.**

---

## 1. Design principles
1. **Provider-agnostic by construction.** All AI access goes through the first-party Laravel AI SDK's unified API (chat, structured output, tool-calling, embeddings). Providers are config rows, not code paths — swapping TokenRouter → OpenRouter → any OpenAI-compatible endpoint is a `.env` change.
2. **Free-native fallback doctrine.** Every AI feature has a no-AI path (cached translation, human queue, default content). Breaker open → platform works, feature degrades visibly-but-gracefully ([../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) §2.3).
3. **Human gates on anything public.** Machine output never publishes without review (translations: [../04-modules/11-multilingual.md](../04-modules/11-multilingual.md) §4; content: authorship policy [../06-content-seo/01-content-strategy.md](../06-content-seo/01-content-strategy.md) §5).
4. **Budgets + audit.** Every invocation logged (feature, provider, model, tokens, latency, status) with monthly budget gauges and alerting ([../03-technical-specs/03-database-schema.md](../03-technical-specs/03-database-schema.md) §10 `ai_invocations`, [../03-technical-specs/12-monitoring.md](../03-technical-specs/12-monitoring.md) §4).

## 2. Provider configuration (the TokenRouter/OpenRouter reality)

The reference prompt that drove this platform decision (OpenAI-compatible API):

```
POST https://api.tokenrouter.com/v1/chat/completions
Authorization: Bearer <TOKENROUTER_KEY>
{ "model": "z-ai/glm-5.3-free", "messages": [ {"role":"user","content":[{"type":"text","text":"…"}]} ] }
```

Laravel AI SDK mapping (env + services config):
```
AI_PROVIDER_PRIMARY=tokenrouter
AI_MODEL_PRIMARY=z-ai/glm-5.3-free
AI_BASE_URL_PRIMARY=https://api.tokenrouter.com/v1
AI_KEY_PRIMARY=***

AI_PROVIDER_SECONDARY=openrouter      # fallback chain
AI_MODEL_SECONDARY=<openrouter model slug>
AI_BASE_URL_SECONDARY=https://openrouter.ai/api/v1
```
- Both providers speak the OpenAI-compatible format the SDK already handles — one adapter, two (or N) providers.
- Model selection per feature (config map below) — e.g. cheap/free model for translations, stronger model for content assistance when a paid tier is ever justified.
- Keys per environment, rotation runbook with the rest of the secrets ([../03-technical-specs/06-hosting-deployment.md](../03-technical-specs/06-hosting-deployment.md) §6).

## 3. The gateway (`Ai\Gateway` — one service class)

```
Gateway::feature('translate')            // resolves feature → model/config
  ->withBreaker()                        // circuit breaker per provider
  ->withBudget('translate', limit)       // monthly token/call guard
  ->withFallbackChain(['openrouter'])    // provider failover before degrading
  ->chat($messages, schema?)             // SDK call; structured output where needed
  ->log()                                // ai_invocations row (never raw sensitive PII)
```
Behavior contract:
- Breaker: 5 consecutive failures → open 60s; half-open probe; every state change → Pulse + ops alert.
- Budget: feature-level monthly caps; alert at 80%; hard-stop → fallback at 100% (translation queue parks, content assists disable — forms/leads NEVER touched).
- Failover: primary fails → secondary (if configured) → native fallback. One invocation = one log row showing which path served.
- Timeouts tuned for shared-hosting PHP (30s chat cap; long tasks belong to the `ai` queue anyway — [../03-technical-specs/07-queues-scheduling.md](../03-technical-specs/07-queues-scheduling.md) §2).
- Prompt templates versioned in code (`ai/prompts/{feature}.php`), never inline strings scattered in business logic; prompts reviewed like code.

## 4. Feature → model config map

| Feature | Model tier | Output policy | Fallback |
|---|---|---|---|
| `translate` (content/UI) | free primary | human review queue (gated publish) | cached EN/native; human translation task |
| `lead_enrich` (company detect, language detect, summary) | free | internal-only suggestion, human decides | panel shows "enrichment paused" |
| `content_assist` (briefs, outlines, first-draft suggestions) | free | author-owned; never auto-published | authors write without assist |
| `faq_harvest` (cluster zero-result searches → FAQ drafts) | free | editor curation queue | manual backlog grooming |
| `chat_assist` (portal consultant reply suggestions) | free | consultant sends; AI never talks to clients directly at launch | consultants reply normally |
| `summarize` (long threads, weekly ops digest phrasing) | free | internal | static templates |

Phase-2 candidates (spec sketch only): site "Ask Sewa" RAG assistant over city/FAQ corpus ([../06-content-seo/05-aeo-llm-presence.md](../06-content-seo/05-aeo-llm-presence.md) §6), CV→skills parsing for ATS, image alt-text drafting suggestions.

## 5. Data-handling rules (what may never reach a provider)
- Never send: passwords, auth tokens, document blobs (leases/visas), resume files, full client PII.
- Allowed: content being translated (marketing/public text), anonymous lead metadata (city/service/locale/company name), thread text **with names masked** for summarize, aggregated ops data for digests.
- DPDP posture: providers configured with zero-retention where supported; invocation log stores metadata + hashes, not full prompts of PII-bearing features; 90-day purge ([../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) §1.4).

## 6. Admin surface (AI section — [../04-modules/05-admin-panel.md](../04-modules/05-admin-panel.md) §3)
- Providers page (status chips: breaker, last latency, monthly usage), model map, budget gauges per feature, invocation log browser (filter by feature/status), translation review queue link, test-call console (admin only).
- Kill switch: `AI_ENABLED=false` (or per-feature) — one toggle, platform unaffected.

## 7. Tests ([../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md))
- Gateway contract: provider adapters mocked; failover chain exercised; breaker open → fallback asserted; budget stop → feature degrades without exception leaks.
- Pipeline invariants: translation jobs idempotent (one per entity-locale), review-gated publish enforced, kill-switch disables all dispatch.
- PII guards: assertion suite that forbidden fields never appear in outbound payloads (static analysis + runtime canary).

---

Related: [02-ai-use-cases.md](02-ai-use-cases.md) · [../04-modules/11-multilingual.md](../04-modules/11-multilingual.md) · [../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) · [../03-technical-specs/07-queues-scheduling.md](../03-technical-specs/07-queues-scheduling.md) · [../03-technical-specs/12-monitoring.md](../03-technical-specs/12-monitoring.md)
