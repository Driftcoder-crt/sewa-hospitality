# 02 — Future Scaling & Phase-2 Options

**What happens when Sewa outgrows shared hosting or a free tier — every upgrade is a documented trigger + a swappable component, not a rewrite. This doc is the platform's insurance policy.**

---

## 1. The exit map (one decision tree)

```
TRIGGER (any) ──────────────────────────────────────► ACTION
Pulse shows CPU throttling / slow queue drain (>15 min backlog)
                                                    → VPS migration (§2)
Need true self-hosted websockets (Reverb) / Redis / Octane
                                                    → VPS migration (§2)
Ably free tier sustained > 80% monthly
                                                    → scale event volume first (§3.2), else VPS+Reverb
Search needs facets/typo tolerance beyond FULLTEXT
                                                    → Typesense/Meilisearch Cloud (§3.4)
Media bandwidth/disk pressures Hostinger
                                                    → S3/R2 object storage (§3.5)
Email volumes exceed free tier
                                                    → one provider plan upgrade (§3.6)
Mobile app greenlit
                                                    → app project on the frozen v1 contract (§4)
A service line outgrows the platform (venture split)
                                                    → subdomain promotion with 301 map (§5)
Multi-tenant SaaS demand (Sewa powering other brands)
                                                    → tenants layer (§6, far future)
```

## 2. VPS migration (the big one — documented now, trivial then)
**Triggers:** CPU throttling visible in Pulse + queue drain > 15 min at peak + a need for Reverb/Redis/Octane.
**The migration is a re-deploy, not a rewrite** (architectural ADRs made sure — [../03-technical-specs/02-architecture.md](../03-technical-specs/02-architecture.md) §8):
1. Provision a small VPS (₹700–1,200/mo class) — Forge/Ploi-style provisioning or manual Nginx+PHP-FPM.
2. Same codebase, new `.env`: `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, add Reverb broadcaster; run `queue:work` as a supervised daemon (no more burst-drain pattern), scheduler via systemd.
3. DB: either managed MySQL on the VPS or keep Hostinger MySQL during transition; one cutover window + backup restore drill first.
4. DNS: flip Cloudflare origin to VPS; subdomains unchanged (public never notices).
5. Add: Redis (cache/queues/Pulse), Reverb (websockets replaces Ably — protocol-compatible: [../03-technical-specs/11-realtime.md](../03-technical-specs/11-realtime.md) §7), optional Octane once stable.
**Rollback:** Cloudflare points back to Hostinger (kept warm for 30 days post-migration).
**Estimated effort:** 1–2 days including drills — the whole point of the layered design.

## 3. Component-level upgrades (each independent)

### 3.1 Realtime
Ably → Reverb on VPS (above) or stay on Ably paid if usage grows moderately. Native/poll layer never changes.

### 3.2 AI providers
TokenRouter/OpenRouter free tiers are config rows — model swaps, provider swaps, budget re-tiering are settings changes ([../08-ai-system/01-ai-architecture.md](../08-ai-system/01-ai-architecture.md) §2). New providers (any OpenAI-compatible aggregator) slot in without code changes.

### 3.3 Search
Scout database → Typesense Cloud (free tier) when faceting/typo-tolerance triggers fire ([../03-technical-specs/08-search.md](../03-technical-specs/08-search.md) §5): swap driver config + import. App code unchanged.

### 3.4 Storage/media
Local disk (Hostinger) → S3/R2/Cloudflare Images when bandwidth/disk triggers ([../03-technical-specs/09-media-pipeline.md](../03-technical-specs/09-media-pipeline.md) §9): Spatie disk config swap; existing media migrates via one queued job; hash URLs keep CDN cache coherent.

### 3.5 Email
Free tiers → paid plan when daily volume passes ~300 mails ([../03-technical-specs/10-email.md](../03-technical-specs/10-email.md) §2): same provider, plan upgrade; fallback chain stays.

### 3.6 Monitoring
Sentry/Pulse scale with plans; Pulse moves to Redis on VPS with one config change ([../03-technical-specs/12-monitoring.md](../03-technical-specs/12-monitoring.md) §4).

## 4. Mobile app (Phase-2 decision, contract already frozen)
When greenlit: React Native/Flutter client consuming `/v1` + the event catalog ([../04-modules/13-mobile-readiness.md](../04-modules/13-mobile-readiness.md) §3); FCM/APNs as a new transport on `notification.created`; document caching + idempotent mutations already specified. App is an add-on project — the platform changes only additively (push listener).

## 5. Venture/brand split (the "Sewa Housing moment")
Trigger: a line has dedicated revenue + staff + content mass. Procedure ([../01-platform-vision/04-subdomains-ventures.md](../01-platform-vision/04-subdomains-ventures.md) §4): subdomain site from the same codebase (multi-site `site_id` on CMS entities — schema-ready), 301 map from the section paths, shared tokens, interlinked "by Sewa Hospitality" branding. SEO equity transfers; nothing is lost — the reverse of the reference's 8-site fragmentation.

## 6. Multi-tenant SaaS (far future, documented only)
If Sewa ever powers other companies' portals/content: a `tenant_id` layer on the module system with per-tenant domains. Deliberately not designed in now (complexity tax); the module boundaries make this an additive project rather than a rewrite when justified.

## 7. What must NEVER change (the constants)
- The v1 API contract + event catalog (additive only).
- ULID identifiers, locale-path URLs, immutable media hashing.
- The error-locks doctrine: transactions, idempotency, breakers, fallbacks — every new component inherits it.
- NAP single-source, schema-matches-visible, honest-claims rules.

## 8. Review cadence
Quarterly scaling review (ops): Pulse trends, queue depth history, Ably/AI/email budgets vs. actual, DB size, search latency, media bandwidth → against the trigger table above. Decisions logged in [CHANGELOG.md](CHANGELOG.md).

---

Related: [01-build-roadmap.md](01-build-roadmap.md) · [CHANGELOG.md](CHANGELOG.md) · [../03-technical-specs/02-architecture.md](../03-technical-specs/02-architecture.md) · [../03-technical-specs/11-realtime.md](../03-technical-specs/11-realtime.md) · [../03-technical-specs/08-search.md](../03-technical-specs/08-search.md) · [../01-platform-vision/04-subdomains-ventures.md](../01-platform-vision/04-subdomains-ventures.md)
