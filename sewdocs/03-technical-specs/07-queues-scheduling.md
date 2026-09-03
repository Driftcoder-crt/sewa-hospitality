# 07 — Queues & Scheduling

**How async work happens on shared hosting: database queues, burst-drained by cron, overlap-safe, idempotent, and fully retry-able.**

---

## 1. The model

```
Web request (form submit, admin save, portal action)
   │ dispatches job (after-commit)
   ▼
jobs table (QUEUE_CONNECTION=database)
   queues: default · emails · ai · syncs · exports
   ▲
   │ * * * * *  (hPanel cron → schedule:run → every minute)
schedule:run
   ├─ queue:work --stop-when-empty --queue=default,emails  (every minute)
   ├─ queue:work --stop-when-empty --queue=ai,syncs,exports (every minute, second invocation offset)
   └─ scheduled commands (sitemap, syncs, retention…) with withoutOverlapping locks
```

**Latency contract:** any dispatched job completes within ≤ 2 minutes (usually < 1). User-facing actions never wait on the queue — the request only writes to DB + dispatches. Every queue consumer feature is designed to tolerate 2-minute latency (emails, AI enrichment, syncs, exports, review requests).

## 2. Queues (names, jobs, ownership)

| Queue | Jobs | Retry policy |
|---|---|---|
| `default` | misc module jobs, activity digests, notification fan-out | tries=3, backoff 60/300/900 |
| `emails` | SendLeadNotification, SendApplicationReceived, NewsletterConfirm/Issue, PasswordResets, InvoiceIssued, ReviewRequestEmail | tries=5, backoff 60/300/900/1800/3600, retry_until 24h (email must eventually go) |
| `ai` | TranslateContent, EnrichLead, SummarizeThread, DraftFaqCandidates | tries=2, backoff 120/600; failure → fallback path, never user-blocking |
| `syncs` | SyncGoogleReviews, SyncGBPStats, PruneMedia, VerifyBackups, SitemapPing | tries=3, daily catch-up safe |
| `exports` | LeadExport (CSV), InvoicePdfBatch | tries=1; failure → notify user in admin |

Rules:
- **Idempotency:** every job re-validates state before acting (e.g. "was this email already sent / this subscriber already confirmed?"). Cron double-fire or retry storms cannot double-send.
- **No external HTTP inside DB transactions** ([05-security-reliability.md](05-security-reliability.md) §2.1); jobs are dispatched in-txn, executed after commit.
- **Circuit breakers** live in the job's service call, not the job wrapper — AI/email/sync jobs check the breaker first and fail fast when open.
- **Failed jobs** → `failed_jobs` table + Sentry event + daily digest to ops; the admin shows a "Failed jobs" widget with one-click retry.

## 3. Scheduler (the single source of truth)

`routes/console.php` / scheduler definitions:

| Command | Schedule | Lock/Overlap | Purpose |
|---|---|---|---|
| `queue:work --stop-when-empty` (2 invocations, queues split) | every minute | natural (stop-when-empty) | drain queues in bursts |
| `sitemap:generate` | nightly 02:00 | withoutOverlapping + onOneServer-equivalent cache lock | rebuild sitemap index; ping Search Console if ping API configured |
| `reviews:sync-gbp` | daily 06:00 | lock | pull Google reviews into cache; alert on new 5★/1★ |
| `seo:audit --pages=100` | daily 04:00 | lock | crawl key pages: 200s, single H1, canonical present, no accidental noindex → report anomalies to admin ([../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md)) |
| `media:prune` | weekly Sun 03:00 | lock | remove orphaned conversions/temp uploads |
| `backups:verify` | daily 08:00 | lock | last backup fresh + size sane → alert otherwise |
| `retention:anonymize` | monthly 1st 03:00 | lock | lead/application retention + AI invocation purge (90d) |
| `sla:calculate` | hourly | lock | SLA breach detection on unresponsive leads → escalate |
| `pulse:trim` | as Pulse default | — | keep Pulse tables lean (shared-hosting DB size discipline) |
| `queue:prune-batches` / `auth:prune` | daily | Laravel defaults | housekeeping |

All commands emit a Pulse event + structured log line; schedule table rendered in admin (Ops → Schedule) so ops can see "last run / next run / duration / failures" without SSH.

## 4. Cron configuration (single entry, hPanel)

```
* * * * * cd /home/uXXXX/sewahospitality && /usr/bin/php8.3 artisan schedule:run >> storage/logs/scheduler.log 2>&1
```

Log rotation: `scheduler.log` + `laravel.log` rotated by the retention command (keep 14 days); log health checked by UptimeRobot `/status` (age of last cron tick < 90s = green).

## 5. Designing for the burst-drain pattern (module rules)

1. **Split heavy work:** invoice PDF generation for a batch of 200 → chunked jobs of 10 per job, queue `exports` — each burst < 30s.
2. **Timeout awareness:** PHP `max_execution_time=120` — each job class documents expected duration; jobs exceeding 60s get chunked at design time.
3. **Chain smartly:** `Bus::chain([ValidateApplication, StoreResume, NotifyRecruiter, AiScoreCandidate])` with `->catch()` — later steps skipped cleanly on failure with a status note on the application.
4. **Batches for exports:** `Bus::batch()` for CSV exports; progress surfaced in admin (Livewire island polling the batch table).
5. **No DB-queue flooding:** public-write dispatches are capped by the same rate limits as the forms; AI translation of a new post enqueues exactly one job per locale (deduplicated by a `translations` state check).

## 6. Monitoring hooks
- Queue depth gauge in admin dashboard (Livewire island, wire:poll 30s): backlog > 50 jobs or oldest job > 10 min → amber; > 30 min → red + alert.
- Scheduler heartbeat: every `schedule:run` writes a cache timestamp; `/status` exposes it; UptimeRobot alerts if stale.
- Sentry cron monitors (free tier supports basic checks) on the nightly sitemap + backup jobs.

---

Related: [05-security-reliability.md](05-security-reliability.md) · [06-hosting-deployment.md](06-hosting-deployment.md) · [12-monitoring.md](12-monitoring.md) · [../08-ai-system/01-ai-architecture.md](../08-ai-system/01-ai-architecture.md)
