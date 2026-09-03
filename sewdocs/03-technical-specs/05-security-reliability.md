# 05 — Security, Reliability & Error Locks

**The commercial-grade doctrine: every write is safe, every external call is guarded, every failure degrades, every action is auditable. This doc binds the whole platform.**

---

## 1. Security foundations

### 1.1 Auth & sessions
| Surface | Policy |
|---|---|
| Public site | No auth; forms protected (below) |
| Client portal (`app.`) | Sanctum sessions; login rate-limited (5/attempt-window/IP + exponential lockout); optional 2FA for client managers; "remember device" opt-in (30d) |
| Admin (`admin.`) | Mandatory 2FA for super-admin/admin; 2h idle timeout; session regenerate on login; separate cookie domain; IP allowlist optional per env var |
| API (`api.`) | Sanctum tokens with scopes; token abilities listed per user in admin; token expiry 90d default; revocation instant |
| Passwords | bcrypt rounds 12 (Hostinger-safe); breach-list check on reset via haveibeenpwned k-anonymity (no privacy leak) |
| Login UX | No account enumeration: identical messages for wrong-password and unknown-email; forgot-password always responds 202 |

### 1.2 Public forms (the "error locks" on the front door)
Every public form (contact, quote, application, newsletter, callback):
1. **Cloudflare Turnstile** — server-side verify; single-use tokens (replay blocked).
2. **Honeypot** field + time-trap (form submitted < 2s after render = bot-scored).
3. **Rate limits**: 5 writes/min/IP, 20/hour/IP (429 with friendly Retry-After message).
4. **Server-side validation** (Livewire rules; client hints only assist).
5. **Idempotency-Key**: duplicate submits (double-click, retry) collapse to one lead — the reference loses these silently.
6. **Consent logging**: `consent_at` + policy version stored with every submission (DPDP/IT Act aligned).
7. **Draft persistence**: Livewire keeps state client-side; a network failure never erases typed text.
8. **Queue handoff**: emails/AI enrichment happen async — user success is instant, mail retries in background.

### 1.3 Application hardening (Laravel)
- CSP: `default-src 'self'`; frame-ancestors none (site), self (portal/admin); media/img from `media.sewa…`; script-src self + nonce'd inline; `upgrade-insecure-requests`.
- Headers: HSTS (6mo, preload), X-Content-Type-Options nosniff, Referrer-Policy strict-origin-when-cross-origin, Permissions-Policy minimal.
- `APP_ENV=production`, APP_DEBUG=false; config/route/view caches on; errors → Sentry (never stack traces to users).
- Trusted proxies (Cloudflare) configured for correct IPs (real rate limiting + logs).
- File uploads: media library + strict mime sniffing, 5 MB cap, randomized storage names, optional ClamAV hook (Phase 2), never executable-permitted paths, served from `media.` with `Content-Disposition` for originals.
- Mass assignment: explicit `$fillable` (never `unguard`); policies on every model ([../04-modules/05-admin-panel.md](../04-modules/05-admin-panel.md) matrix).
- SQL: Eloquent/query builder only; no raw concatenation. XSS: Blade escaping default; rich-text fields sanitized server-side (allowed-tag whitelist per field type).
- Signed URLs for document downloads (15-min expiry, one-time logging).
- Secrets in env only; `.env` never in git; separate keys per environment; rotation runbook ([06-hosting-deployment.md](06-hosting-deployment.md)).

### 1.4 Data protection (DPDP + GDPR posture)
| Requirement | Implementation |
|---|---|
| Lawful basis & consent | `consent_at`, policy version, purpose tags on leads/applications |
| Right to access/erasure | Admin "data subject" tool: export (JSON) + anonymize (keep invoice/audit integrity, purge PII) |
| Retention | Leads 36 months → anonymize · Applications 24 months (unless hired) · Audit logs 7 years (redacted PII) · AI invocations 90 days |
| Data localization | Primary storage Hostinger India-friendly region; backups documented ([06-hosting-deployment.md](06-hosting-deployment.md)) |
| Privacy pages | CMS-managed privacy + cookie policy; cookie consent (consent-gated analytics) |

## 2. Reliability: the "error locks" doctrine

### 2.1 Transactional writes (no partial state)
```php
DB::transaction(function () use ($dto) {
    $lead = Lead::create([...]);                       // idempotency-keyed
    LeadEvent::create(['lead_id'=>$lead->id, 'type'=>'form', …]);
    // notifications are DISPATCHED (queued) inside the txn; mail sends after commit
});
```
Rules: business writes in one transaction; jobs dispatched inside transactions (Laravel handles after-commit); invoice/quote numbering under `SELECT…FOR UPDATE` lock; no cross-service HTTP calls inside DB transactions.

### 2.2 Idempotency (every public + app write)
`Idempotency-Key` header → stored 24h with request fingerprint → replay returns the original result (not a duplicate). Protects double-clicks, flaky mobile networks, and app retries.

### 2.3 Circuit breakers & fallbacks (external calls)
All external dependencies wrapped in `Support\Locks\CircuitBreaker` (open after 5 consecutive failures/60s; half-open probe after 5 min; logged to Pulse):

| Dependency | Fallback if breaker opens |
|---|---|
| AI provider (TokenRouter/OpenRouter) | cached translation/response → human-translation queue → native language default; feature degrades visibly-but-gracefully in admin |
| Ably | automatic switch to `wire:poll` transport (feature-flagged; users never see an error) |
| Email provider | secondary SMTP (Hostinger) + queue retries; alert ops at breaker-open |
| GBP sync / Google APIs | cached stats + alert; site unaffected |
| Any admin-side integration | banner notice in admin; core CMS never depends on externals |

### 2.4 Queue reliability (database driver)
- Default queue + named queues: `emails`, `ai`, `syncs`, `exports`.
- `tries=3`, `backoff: 60,300,900`, `retry_until` 24h for emails; failed jobs → `failed_jobs` + Sentry + daily digest.
- Idempotent job design: every job re-checks state (e.g. newsletter confirm already-sent).
- Overlap prevention: scheduled commands wrapped in cache mutex locks (no double-cron races).

### 2.5 Caching & invalidation (shared-hosting CPU discipline)
- Response cache for anonymous GETs (key: path+locale), event-driven invalidation on CMS saves — one save flushes only its tags (`pages:home`, `services:relocation`).
- Fragment caches for expensive blocks (city grids, reviews strip) with short TTLs + tag invalidation.
- ETag/If-None-Match on API GETs; `Cache-Control` headers tuned per surface.
- Never cache authenticated or form-refill responses; purge rules documented in [06-hosting-deployment.md](06-hosting-deployment.md).

### 2.6 Graceful degradation matrix (user-facing promise)

| Failure | What the user sees | What actually happens |
|---|---|---|
| AI down (locale content) | English/native fallback content | breaker logs, translation queue parks, admin banner |
| Ably down | Same UI, slightly slower updates | transport auto-switch to wire:poll |
| Email provider down | Instant form success anyway | mail queued with retries; ops alerted |
| GBP sync fails | Last-known stats shown | stale-data note in admin |
| MySQL overload | Cached pages keep serving | UptimeRobot + Pulse alert |
| 5xx bug (should never ship) | Friendly error page + search box + top links | Sentry instant alert; on-call runbook |

## 3. Abuse & fraud protection

- Rate limiting surfaces: login (5/min/IP), password reset (3/hour/email), public writes (5/min/IP), search (30/min/IP), API (per-token).
- Turnstile on every public form + portal login optional.
- Honeypot + time-trap + hidden-field entropy (bot scoring, soft-reject to "review" queue, never silent drop).
- Lead poisoning guard: links/URLs in messages neutralized on render; attachments never in lead emails (resume files only via media library on applications).
- Admin audit log of exports (CSV dumps of leads/applications = logged with user + scope + count).
- 2FA + role least-privilege: editors cannot touch billing; finance cannot publish content; consultants see only assigned moves.

## 4. Audit & observability hooks (brief — full: [12-monitoring.md](12-monitoring.md))
- `activity_log` on every admin/portal mutation (who/what/when/diff, sensitive redacted).
- Login/logout/token issuance events; failed-login IP tracking.
- AI invocation ledger (budget guard rails + provider health, [../08-ai-system/01-ai-architecture.md](../08-ai-system/01-ai-architecture.md)).
- Sentry + Pulse dashboards wired to alerts (Sentry → email/Slack webhook, Pulse thresholds → daily digest).

## 5. Security QA gates (enforced in CI — [13-testing-qa.md](13-testing-qa.md))
1. `pest` suite: auth matrix (403s), validation, idempotency, throttle, transaction rollback tests.
2. Route audit: no debug routes; admin routes role-gated; API scopes enforced.
3. Dependency audit: `composer audit` + `npm audit` in CI, blocking on high/critical.
4. CSP/header test: response snapshots assert security headers on every surface.
5. Backup restore drill (quarterly): restore latest DB backup to staging, verify row counts + admin login, document RTO (see hosting doc for backup strategy).

## 6. Incident response runbook (summary)

| Severity | Example | Response |
|---|---|---|
| SEV-1 | Site down / data breach suspicion | Cloudflare under-attack mode, snapshot logs, rotate secrets, assess, notify (DPDP timelines), post-mortem doc |
| SEV-2 | Forms failing / provider outage | Fallback engages automatically; ops fix within SLA 4h; users unaffected |
| SEV-3 | Stale content, sync lag | Queue catch-up; note in Pulse; no user impact |

Every SEV becomes a CHANGELOG entry + a prevention test where possible.

---

Related: [02-architecture.md](02-architecture.md) · [04-api-spec.md](04-api-spec.md) · [06-hosting-deployment.md](06-hosting-deployment.md) · [07-queues-scheduling.md](07-queues-scheduling.md) · [12-monitoring.md](12-monitoring.md) · [13-testing-qa.md](13-testing-qa.md)
