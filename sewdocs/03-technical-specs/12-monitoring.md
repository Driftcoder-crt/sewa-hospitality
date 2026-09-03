# 12 — Monitoring & Observability

**Free-tier stack: Laravel Pulse (database driver) for app internals, Sentry for errors, UptimeRobot for uptime, plus an honest public /status page. Everything runs within shared-hosting limits.**

---

## 1. The three layers

| Layer | Tool (free tier) | Answers | Where data lives |
|---|---|---|---|
| Uptime | UptimeRobot 5-min checks | "Is the site/API up?" | UptimeRobot + /status page |
| Errors & performance | Sentry (small-app free tier) | "What broke, where, for whom, since which release?" | Sentry SaaS |
| App internals | Laravel Pulse (database driver) | slow queries, slow routes, job health, scheduler heartbeat | MySQL pulse tables |

## 2. UptimeRobot checks (configure at launch)

| Check | Target | Alert if |
|---|---|---|
| Site | `https://sewahospitality.com/` (302/200, keyword "Sewa") | non-200 or keyword missing |
| API health | `https://api.sewahospitality.com/v1/health` | non-200 |
| Scheduler heartbeat | `https://sewahospitality.com/status` → reports last cron tick age | stale > 90s |
| Admin login | `https://admin.sewahospitality.com/login` | non-200 (auth pages still return 200) |
| Portal | `https://app.sewahospitality.com/login` | non-200 |
| SSL | all above | cert expiry < 14 days |
| Contact form e2e (weekly manual + scripted check) | submit staging lead via API | lead not in admin |

Alerts → ops email + WhatsApp/Slack webhook (UptimeRobot free supports email + one integration).

## 3. Sentry setup

- `sentry/sentry-laravel` (the one JS/PHP agent we allow — justified in [01-stack-and-dependencies.md](01-stack-and-dependencies.md) allowlist amendments if not covered by first-party).
- Release markers on every deploy (git SHA as release); issues auto-resolve on fix release.
- Traces: sampled 10% (free tier friendly); breadcrumbs on; PII scrubbing: strip request bodies of message/phone fields (`before_send` scrub list), never log secrets.
- Alerts: new issue + regression + release-marked spike → email; SEV classification per [05-security-reliability.md](05-security-reliability.md) §6.

## 4. Laravel Pulse (database driver — no Redis needed)

Cards rendered in admin Ops dashboard:
- Slow queries (top 10) · slow routes (p95) · job throughput/failures · queue backlog age ·
- Custom recorders we ship:
  - `CronHeartbeat` (every schedule:run tick → last-run ages)
  - `CircuitBreakerStatus` (AI / Ably / Email / GBP — open/half-open counters)
  - `AiBudgetGauge` (tokens/month vs. cap, provider latency)
  - `SearchZeroResults` (top zero-result queries → content backlog)
  - `MailLogStats` (sent/bounced/suppressed)
- `pulse:trim` scheduled daily to keep tables small (shared-hosting DB size discipline).
- Pulse is **admin-only** (never public), auth-gated behind admin roles.

## 5. The honest public status page (`/status`)

Read-only, cached 30s, shows green/amber/red for:
- Website · API · Portal · Scheduler (age of last tick) · Queue backlog (oldest job age)
- Recent maintenance windows (from a maintenance log table)

This transparency page is itself a trust asset for corporate clients (enterprise mobility teams care about vendor ops maturity) — the reference has nothing like it.

## 6. Ops digest & alerting routes

Daily 09:00 `ops.digest` email ([10-email.md](10-email.md) §4) aggregates: leads (by status/source), SLA breaches, new applications, failed jobs, queue depth chart, reviews (new + rating), zero-result searches, AI usage vs. budget, media bandwidth, backup verification.

Alert matrix:
| Event | Channel | Timing |
|---|---|---|
| Uptime fail | UptimeRobot → ops | immediate |
| Sentry new issue | email | immediate |
| Breaker open (any) | Sentry + digest | immediate + digest |
| SLA breach count > 3/day | digest + instant if > 5 | day-end / immediate |
| AI budget 80% | digest flag | day-end |
| Backup verify fail | instant email | immediate (SEV-1 data) |
| Queue oldest > 30 min | instant | immediate |

## 7. Logs & retention
- Laravel logs: `storage/logs/*.log` rotated by retention command, 14-day retention, no PII at info level; errors carry request-id correlation with Sentry.
- `activity_log` (admin/portal actions) — 7 years, redacted diffs ([05-security-reliability.md](05-security-reliability.md) §4).
- Access analytics: GA4 via GTM with Consent Mode ([../07-marketing-trust/02-analytics-plan.md](../07-marketing-trust/02-analytics-plan.md)) — server-side lead events only, no self-hosted tracking CPU burn.

## 8. Quarterly resilience drill (documented, scheduled)
1. Restore DB backup to staging; verify counts + admin login (backup drill).
2. Disable Ably key on staging → verify polling fallback engages < 30s.
3. Kill mail provider creds on staging → verify fallback SMTP + queue retries.
4. Push a deliberate 500 to staging → verify Sentry alert + friendly error page + on-call runbook flow.
Each drill logs to CHANGELOG with findings and fix tickets.

---

Related: [05-security-reliability.md](05-security-reliability.md) · [06-hosting-deployment.md](06-hosting-deployment.md) · [07-queues-scheduling.md](07-queues-scheduling.md) · [10-email.md](10-email.md) · [../08-ai-system/01-ai-architecture.md](../08-ai-system/01-ai-architecture.md)
