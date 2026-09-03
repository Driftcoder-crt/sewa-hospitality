# 06 — Hosting & Deployment (Hostinger Shared Hosting)

**The honest constraint set of shared hosting, the patterns that make Laravel thrive on it, and the exact deploy runbook. No daemons. No Node at runtime. No Redis. And a documented exit path to VPS.**

---

## 1. Environment (confirmed constraints)

| Constraint | Consequence | Our pattern |
|---|---|---|
| PHP 8.3 FPM (Hostinger confirmed) | `^8.3` pinned | composer.json enforces |
| MySQL 8, InnoDB | No Redis, no persistent caches outside DB/FS | DB cache driver; file cache for page fragments |
| No SSH daemon for long-running processes | No `queue:work`, no `schedule:work`, no websockets, no Octane | DB queues drained by cron; single cron runs scheduler; Ably for push |
| No Node at runtime | Can't `npm run build` on server | Build locally; ship `public/build/*` + manifest |
| Cron via hPanel | Min interval 1 minute | `* * * * * php artisan schedule:run` |
| Shared IP behind Cloudflare | Real client IPs need trusted-proxy config | Laravel trustedProxies = Cloudflare ranges |
| Limited entry processes / CPU (shared plan) | Spikes can throttle | Full-page response cache + Cloudflare edge caching + queued everything |

**Verdict honored:** the stack in [01-stack-and-dependencies.md](01-stack-and-dependencies.md) is 100% deployable here — that was a selection criterion, not an afterthought.

## 2. Directory layout on the host

Hostinger document root must point to Laravel's `public/`:

```
/home/uXXXX/sewahospitality/          (app root, above public)
├── app/ bootstrap/ config/ database/ resources/ routes/ storage/ vendor/
├── public/            ← document root sewahospitality.com AND subdomains
│   ├── index.php  .htaccess  build/  media→(storage symlink)  favicon…
├── .env               (0600 perms, never web-readable)
└── artisan  composer.*  …
```

Subdomains (`api.`, `admin.`, `app.`, `media.`) all point to the same `public/`; routing distinguishes by host ([02-architecture.md](02-architecture.md) §4). `media.` routes to `public/media` (symlink → `storage/app/public/media`) with immutable cache headers via `.htaccess`/PHP.

**Storage symlink:** `php artisan storage:link` once at deploy (documented in the runbook).

## 3. Deployment methods (choose per stage)

| Stage | Method | Steps |
|---|---|---|
| MVP → early production | Git + hPanel SSH one-offs | git pull → `composer install --no-dev` (or upload prebuilt vendor) → `npm build` artifacts already committed for the tag → migrate → optimize |
| If SSH is limited on the plan | SFTP/rsync deploy package | Build "release package" locally (code + vendor + build) → upload → extract → swap `current` symlink → run migrate via hPanel cron one-off |

**Recommended discipline (works on both):**
1. Local/CI produces a release artifact: `release-<gitsha>.tar.gz` (app minus `vendor` OR including prebuilt `vendor` if no SSH composer), + `public/build/`.
2. Upload to `/home/uXXXX/releases/release-<sha>/`.
3. Atomic-ish switch: update a `current` symlink → `public` paths under it (or simple folder swap with 10-second maintenance flag).
4. One-off migration via hPanel Cron Jobs (one-shot command run) or SSH if plan allows: `php artisan migrate --force && php artisan optimize`.
5. Never run migrations at peak; announce in `#ops` channel (see [12-monitoring.md](12-monitoring.md)).

## 4. The single cron (the heart of shared-hosting Laravel)

hPanel → Advanced → Cron Jobs:

```
* * * * * cd /home/uXXXX/sewahospitality && /usr/bin/php8.3 artisan schedule:run >> storage/logs/scheduler.log 2>&1
```

`bootstrap/app.php` scheduler then drives everything ([07-queues-scheduling.md](07-queues-scheduling.md)):

```
schedule:run every minute, which fires:
  queue:work --stop-when-empty  (every minute — drains DB queue in bursts)
  sitemap:generate  (nightly 02:00)
  reviews:sync-gbp  (daily 06:00)
  media:prune  (weekly)
  backups:verify  (daily)
  pulse:check  (hourly digest to Pulse)
  retention:anonymize  (monthly)
```

**Overlap safety:** every command wrapped in `withoutOverlapping()` + cache locks ([05-security-reliability.md](05-security-reliability.md) §2.4) — two cron ticks can never double-run a job.

**Queue draining on shared hosting — the working pattern:** a one-minute `queue:work --stop-when-empty` invocation processes jobs in short bursts; emails/AI/syncs complete within 1–2 minutes of submission (perfectly acceptable for all our async features; the user-facing form response is instant regardless).

## 5. Server configuration

### PHP settings (hPanel PHP config)
```
memory_limit = 256M
max_execution_time = 120
upload_max_filesize = 10M   post_max_size = 12M
opcache.enable = 1  opcache.validate_timestamps = 0 (release resets it)
max_input_vars = 3000
```

### .htaccess (public/)
- Force HTTPS + www→non-www (or chosen canonical)
- Static asset caching: immutable hashed `build/*` 1 year; `media/*` 1 year immutable
- Gzip/Brotli (Hostinger provides; verify)
- Block dotfiles, block `vendor` access (belt & braces; it's outside public anyway)
- Optional Cloudflare real-IP restore

### Cloudflare (free) settings
```
DNS: A sewahospitality.com + api/app/admin/media → host IP (proxied)
SSL/TLS: Full (strict) + origin cert from Hostinger (or Cloudflare origin cert)
Caching: Cache Everything for media.sewa… (immutable), standard caching for HTML (respect origin Cache-Control)
Turnstile: site keys registered for all subdomains
Rate-limiting (free): on /v1/leads, /v1/applications, admin login path
Always Use HTTPS, Brotli, HTTP/3
```

## 6. Environments

| Env | URL | Purpose |
|---|---|---|
| local | `sewa.test` (Valet/Herd on Windows dev via WSL or Herd Windows) | development, hot reload |
| staging | `staging.sewahospitality.com` (password-protect via Cloudflare Access free or basic auth) | UAT, client previews, seeded fixtures |
| production | sewahospitality.com + subdomains | live |

Config discipline: separate `.env` per env; feature flags (`AI_ENABLED`, `REALTIME_TRANSPORT`, `NEW_LEADS_ALERTS`) rather than code branches; staging disables indexation (basic auth + `noindex` header).

## 7. Deploy runbook (production)

```
PRE-DEPLOY
  [ ] Backup: DB dump (hPanel or phpMyAdmin export or artisan spatie/backup if added) → dated file in /backups
  [ ] CI green: tests, dependency audit, route health (13-testing-qa.md)
  [ ] Release artifact built locally: npm run build; composer install --no-dev; package
DEPLOY
  [ ] Upload/extract release → releases/<sha>
  [ ] Point current symlink (or swap dirs)
  [ ] One-off: php artisan migrate --force
  [ ] php artisan optimize  (config+routes+views+events cache)
  [ ] php artisan storage:link (idempotent, safe re-run)
  [ ] queue:restart  (so in-flight workers pick new code)
POST-DEPLOY (5-minute gate)
  [ ] GET / 200 < 800ms; GET /sitemap.xml 200; GET /v1/health {ok}
  [ ] Submit test lead (staging token) → arrives in admin + email
  [ ] Sentry release marked; Pulse shows deploy marker
  [ ] Cloudflare cache: purge HTML only (assets hashed anyway)
ROLLBACK (target < 5 minutes)
  [ ] Re-point symlink to previous release
  [ ] php artisan optimize; queue:restart
  [ ] If migration ran: php artisan migrate:rollback only with reviewed pairs; otherwise restore DB backup (decision tree in 12-monitoring.md)
```

## 8. Backups & recovery

| What | Frequency | Where | Verification |
|---|---|---|---|
| MySQL dump | Nightly via cron (`spatie/laravel-backup` optional — allowlist §2 of stack doc) + weekly hPanel full backup | `/backups` (off-app dir) + download to office storage | `backups:verify` daily: latest file < 26h old, size sane; quarterly restore drill to staging ([05-security-reliability.md](05-security-reliability.md) §5) |
| Media (storage/app/public/media) | Weekly sync to office storage / later S3 | offsite | part of restore drill |
| Code | Git (tagged releases) | origin | CI |
| .env | Encrypted copy in password manager | 1Password/Vault | — |

**RPO 24h (nightly) with hourly binlog-style option if host supports; RTO: one release-swap + DB restore < 5–60 min.** Documented and drilled, not aspirational.

## 9. Maintenance mode & status page

- `php artisan down --secret=…` for planned windows (bypass cookie for team).
- Static `503` page with brand + status message; Cloudflare serves cached pages anyway during maintenance.
- Public status note: simple `/status` route reporting app + queue + last cron tick age (green/amber/red) — honest transparency, UptimeRobot watches it.

## 10. VPS exit triggers (when to leave shared hosting)

Documented in [../09-delivery/02-future-scaling.md](../09-delivery/02-future-scaling.md): CPU throttling visible in Pulse + slow queue drain (> 15 min backlog) + need for Reverb/Redis/object storage + > ~50k sessions/day. The migration is a re-deploy + env swap, not a rewrite.

---

Related: [01-stack-and-dependencies.md](01-stack-and-dependencies.md) · [07-queues-scheduling.md](07-queues-scheduling.md) · [11-realtime.md](11-realtime.md) · [12-monitoring.md](12-monitoring.md) · [13-testing-qa.md](13-testing-qa.md)
