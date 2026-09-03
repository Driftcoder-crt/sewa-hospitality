# DEPLOY — Hostinger Shared Hosting Runbook

> **Source of truth:** `sewdocs/03-technical-specs/06-hosting-deployment.md` (this file is the operator-facing
> runbook distilled from it, plus `02-architecture.md` §4 for host routing and `09-media-pipeline.md` §5 for
> media caching). Build tooling: `./deploy.sh`. **No daemons. No Redis. No Node at runtime — ever.**

---

## 1. Environments

| Env | URL | Purpose | Notes |
|---|---|---|---|
| local | `https://sewa.test` (Valet/Herd) | development, Vite HMR | `MAIL_MAILER=log`, backups in `storage/app/backups` |
| staging | `https://staging.sewahospitality.com` | UAT, client previews, seeded fixtures | basic auth (§8) + `noindex`; **`composer run seed:demo` only here** |
| production | `sewahospitality.com` + `api.` `admin.` `app.` `media.` subdomains | live | **`composer run seed:prod-minimal` only** |

Config discipline: a separate `.env` per environment (never shared); behaviour differences ride on feature flags
(`AI_ENABLED`, `REALTIME_TRANSPORT`, `NEW_LEADS_ALERTS`, `SEARCH_FACETS`), never code branches.
Start from `.env.example`, then apply the deltas in `.env.staging.example` / `.env.production.example`.

---

## 2. Directory layout on the host

```
/home/uXXXX/sewahospitality/            ← app root (above the document root)
├── .env                                ← canonical env file, chmod 600, NEVER web-readable
├── releases/
│   ├── <sha>/                          ← extracted release artifacts (app/, vendor/, public/build/, …)
│   └── <prev-sha>/                     ← kept for instant rollback
├── current -> releases/<sha>           ← the ONLY thing a deploy switches (ln -sfn)
├── app        -> current/app           ┐
├── bootstrap  -> current/bootstrap     │
├── config     -> current/config        │
├── database   -> current/database      │  one-time symlink farm (§9) — it keeps the
├── public     -> current/public        ├  contract cron line valid: artisan runs at the
├── resources  -> current/resources     │  app root while the code lives in the release
├── routes     -> current/routes        │
├── storage    -> current/storage       │
├── vendor     -> current/vendor        │
└── artisan    -> current/artisan       ┘
```

- **Document root → the release's `public/`** (main domain and all four subdomains point at
  `/home/uXXXX/sewahospitality/current/public`). Apache follows the symlinks (standard on Hostinger).
- **`.env` is `0600`, lives at the app root, survives releases**, and is linked into each release
  (`ln -sfn ../../.env releases/<sha>/.env`). It is excluded from every release artifact by `deploy.sh`.
- **One codebase, four hosts:** `sewahospitality.com` (site) · `app.` (portal) · `admin.` (admin) · `api.`
  (`/v1`) all resolve to the **same `public/`** — routing distinguishes by host
  (`sewdocs/03-technical-specs/02-architecture.md` §4). `media.` serves the same tree and is treated as a
  static-only host (immutable cache headers, §5 below).
- `php artisan storage:link` creates `public/storage → storage/app/public` **inside the release** — run it on
  every deploy (idempotent, safe to re-run).
- Optional hardening: link `storage/framework/sessions` to a shared directory so admin sessions survive a
  release switch; otherwise users simply re-login after a deploy (acceptable at M0 scale).

---

## 3. The single hPanel cron (the heart of shared-hosting Laravel)

hPanel → **Advanced → Cron Jobs** — exactly one entry, every minute (verify the PHP 8.3 binary path in hPanel
if it differs on your plan):

```
* * * * * cd /home/uXXXX/sewahospitality && /usr/bin/php8.3 artisan schedule:run >> storage/logs/scheduler.log 2>&1
```

That single line drives everything in `routes/console.php`:

| Fires | Schedule |
|---|---|
| scheduler heartbeat (feeds `/status` + UptimeRobot) | every minute |
| queue drain bursts ×2/min (`default,emails` + `ai,syncs,exports`, `--stop-when-empty`) | every minute |
| `pulse:check` | every minute |
| `db:backup` | nightly 01:30 |
| `backups:verify` | daily 08:00 |
| `media:prune` | Sundays 03:00 |

Overlap safety is mandatory on shared hosting: every command is wrapped `withoutOverlapping()` with cache
locks — two cron ticks can never double-run a job (error-locks doctrine, `05-security-reliability.md` §2.4).
There is **no** `queue:work` daemon, no `schedule:work`, no websockets, no Octane.

---

## 4. PHP settings (hPanel → PHP configuration)

| Setting | Value |
|---|---|
| `memory_limit` | `256M` |
| `max_execution_time` | `120` |
| `upload_max_filesize` | `10M` |
| `post_max_size` | `12M` |
| `opcache.enable` | `1` |
| `max_input_vars` | `3000` |

Per the spec, `opcache.validate_timestamps = 0` is the end-state (releases land in fresh directories and
`queue:restart` recycles workers); keep it `1` unless you have verified warm-up after a deploy.
PHP version for the domain: **8.3** (pinned by `composer.json`).

---

## 5. Cloudflare (free plan)

- **DNS:** five `A` records → Hostinger IP, **all proxied (orange cloud):**
  `sewahospitality.com` · `api.` · `admin.` · `app.` · `media.`
- **SSL/TLS:** mode **Full (strict)** + origin certificate (Cloudflare origin cert installed in hPanel).
- **Always Use HTTPS** — on. **Brotli** — on. **HTTP/3** — on.
- **Cache Everything ONLY on `media.`** — conversion filenames carry a content hash, so a re-upload produces a
  NEW URL: Cloudflare never serves stale pixels and there is **no purge choreography**
  (`09-media-pipeline.md` §5). HTML respects origin `Cache-Control`; `build/*` assets are hashed (1-year
  immutable) and are *not* purged during deploys — deploys purge **HTML only**.
- **Turnstile:** register one widget per subdomain that renders forms (site, `app.`, `admin.`) and store the
  pairs in the env keys `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY` (`config/sewa.php`).
- **Rate limiting (free tier):** rules on `/v1/leads`, `/v1/applications`, `/admin/login` — the intake paths
  land in **M3**; register the rules when those routes ship. Application-level guards shipped in M3:
  public forms run honeypot + time-trap + 5/min/IP + 20/h/IP + Turnstile + idempotency keys (`config/sewa.php`
  `forms.*`).
- Real client IPs behind the proxy: `TRUSTED_PROXIES` env with Cloudflare ranges (Laravel trusted-proxy config).

### 5.1 Email + intake env keys (M3 — 03-technical-specs/10-email.md)

```dotenv
# From-address identities (one provider domain config)
SEWA_EMAIL_HELLO=hello@sewahospitality.com
SEWA_EMAIL_SUPPORT=support@sewahospitality.com
SEWA_EMAIL_CAREERS=careers@sewahospitality.com
SEWA_EMAIL_NOREPLY=no-reply@sewahospitality.com
SEWA_EMAIL_BILLING=billing@sewahospitality.com
# Ops alert + digest recipients (comma-separated)
SEWA_OPS_EMAILS=ops@sewahospitality.com

# Privacy consent version stamped on every public form (bump on policy change)
SEWA_PRIVACY_VERSION=2026-01
```

M3 scheduler additions (already wired in `routes/console.php`): `sla:calculate` hourly ·
`retention:anonymize` monthly (1st 03:00) · `ops:digest` daily 09:00 — no extra cron entries needed
(the single `schedule:run` minute-cron covers everything).

### 5.2 AI + i18n env keys (M6 — 08-ai-system/01 + 04-modules/11-multilingual)

```dotenv
# AI gateway (OpenAI-compatible providers — config, not code).
# Kill switch: AI_ENABLED=false degrades every feature to its no-AI path.
AI_ENABLED=true
AI_PROVIDER_PRIMARY=tokenrouter
AI_MODEL_PRIMARY=z-ai/glm-5.3-free
AI_BASE_URL_PRIMARY=https://api.tokenrouter.com/v1
AI_KEY_PRIMARY=<TOKENROUTER-KEY>
# Failover chain (optional):
AI_PROVIDER_SECONDARY=openrouter
AI_MODEL_SECONDARY=z-ai/glm-5.3-free
AI_BASE_URL_SECONDARY=https://openrouter.ai/api/v1
AI_KEY_SECONDARY=<OPENROUTER-KEY>
# Monthly budgets (tokens; hard-stop at 100%, ops alert at 80%)
AI_BUDGET_TRANSLATE_TOKENS=2000000
AI_BUDGET_ENRICH_TOKENS=500000

# i18n: launch locales are seeded (en hi ja ko tr ar). No env needed;
# admin toggles live in Settings → Languages & translations.
```

---

## 6. Deploy runbook (production — 06-hosting-deployment.md §7)

**PRE-DEPLOY**
- [ ] **CI green** on the exact SHA (`.github/workflows/ci.yml` — 13-testing-qa gates 1–4). **Red = no deploy.**
- [ ] **Backup DB:** `php artisan db:backup` (or hPanel/phpMyAdmin export) → dated file in the backups dir.
- [ ] Build the release artifact **locally/CI**: `./deploy.sh`
      (optionally `TARGET_HOST=user@host TARGET_PATH=/home/uXXXX ./deploy.sh` → artifact + rsync upload).

**DEPLOY** (whole flow target: **< 15 minutes** — M0 acceptance)
1. Upload `artifacts/release-<sha>.tar.gz` → `/home/uXXXX/sewahospitality/releases/` (rsync above, or SFTP).
2. Extract: `cd releases && mkdir -p <sha> && tar -xzf release-<sha>.tar.gz -C <sha>`.
3. Provision the release: `ln -sfn ../../.env <sha>/.env` (storage skeleton ships in the artifact).
4. **Switch:** `ln -sfn releases/<sha> current` — the atomic step; docroot serves `current/public`.
5. `php artisan migrate --force` (SSH; or a one-off hPanel cron job if the plan has no SSH).
6. `php artisan optimize` (config + routes + views + events cache).
7. `php artisan storage:link` (idempotent).
8. `php artisan queue:restart` (cron-drained workers pick up the new code on the next tick).

Never migrate at peak; announce deploys in `#ops` (`12-monitoring.md`).

**POST-DEPLOY — 5-minute gate**
- [ ] `GET https://sewahospitality.com/` → **200 in < 800 ms**.
- [ ] `GET https://api.sewahospitality.com/v1/health` → **`{"status":"ok"}`** (db + cache + queue probes).
- [ ] `GET https://sewahospitality.com/status` → scheduler + queue green (heartbeat < 90 s stale).
- [ ] Submit a test lead → arrives in admin + ack email **(money path — live from M3)**.
- [ ] Sentry: mark release `<sha>`; Pulse shows the deploy marker.
- [ ] Cloudflare: purge **HTML only** (assets are hashed — never purged).

**ROLLBACK** (target **< 5 minutes**)
1. `ln -sfn releases/<previous-sha> current`
2. `php artisan optimize && php artisan queue:restart`
3. If the failed release migrated:
   - **Roll forward** (corrective release) is preferred whenever feasible;
   - `php artisan migrate:rollback` **only** with reviewed, paired `down()` migrations — never blind
     (migration-safety gate, `03-database-schema.md` §12);
   - Data damage: restore the newest **verified** `db:backup` dump (decision tree: `12-monitoring.md` §8).
   - RPO 24 h (nightly dump) · RTO: one release swap + DB restore < 5–60 min.

---

## 7. Backups & recovery

| What | Frequency | Where | Verification |
|---|---|---|---|
| MySQL dump | Nightly **01:30** via scheduler (`db:backup` → gzip) | **`SEWA_BACKUPS_PATH=/home/uXXXX/backups`** — **OUTSIDE the app dir** (+ copy offsite to office storage) | `backups:verify` daily **08:00**: newest dump < 26 h old, size sane — **failure = SEV-1** alert |
| Full account | Weekly hPanel backup | hPanel | spot-check monthly |
| Media (`storage/app/public/media`) | Weekly sync to office storage / later S3 | offsite | part of the restore drill |
| Code | Git, tagged releases | origin | CI |
| `.env` | Encrypted copy in the password manager | 1Password/Vault | — |

**Quarterly restore drill:** restore a verified dump + a release artifact to staging, confirm `/v1/health`,
`/status` and an admin login — documented and drilled, not aspirational (`05-security-reliability.md` §5).

---

## 8. Staging

- **Access control:** basic auth via **Cloudflare Access** (free plan, ≤ 50 users) or hPanel directory
  protection — staging is never publicly indexable or browsable.
- **`noindex` is enforced by the app** (middleware), not only by robots.txt — belt and braces.
- **Data:** staging is a production mirror on the same stack; seed with **`composer run seed:demo` ONLY**
  (the seeder hard-fails in production). Production uses `composer run seed:prod-minimal` — zero fake content
  in the production DB (`13-testing-qa.md` §6).
- Feature branches deploy to staging via the same runbook; Sentry/Pulse enabled with staging env keys.

---

## 9. First-deploy checklist (one-time)

**Infrastructure**
1. hPanel: PHP **8.3** for the domain; settings per §4; MySQL 8 database created (utf8mb4/InnoDB).
2. Cloudflare per §5: five proxied A records, Full (strict) + origin cert, Always Use HTTPS.
3. Upload + extract the first artifact into `releases/<sha>/`; create `.env` from
   `.env.production.example`, `chmod 600 .env`, `php artisan key:generate`.
4. Create the one-time symlink farm (§2) and `ln -sfn releases/<sha> current`.
5. Point the document root of the main domain **and** `api.` `admin.` `app.` `media.` to
   `/home/uXXXX/sewahospitality/current/public`.
6. Register the cron (§3) — exactly one line.
7. `php artisan migrate --force` → `php artisan storage:link` → `php artisan optimize` → `php artisan queue:restart`.

**Application bootstrap (exact order)**
1. `composer run seed:prod-minimal`
2. `php artisan sewa:admin you@sewahospitality.com`
3. `php artisan user:enable-2fa <admin email>`
4. **UptimeRobot monitors** (free plan):
   - `https://sewahospitality.com/` — **keyword monitor, keyword `Sewa`**
   - `https://api.sewahospitality.com/v1/health` — expect 200 + `{"status":"ok"}`
   - `https://sewahospitality.com/status` — scheduler/queue heartbeat (catches a dead cron)
   - `https://admin.sewahospitality.com/login`
   - `https://app.sewahospitality.com/login`
   - SSL monitor on `sewahospitality.com` — **warning < 14 days** to expiry

---

## 10. Launch day + hypercare (M7 — 09-delivery/01-build-roadmap.md §7)

**Launch morning (after §9 bootstrap):**
1. `php artisan sewa:launch-verify --url=https://sewahospitality.com --production` — the 5-minute
   post-deploy gate automated (13-testing-qa §2 gate 9). **Exit 1 = fix or roll back** before
   announcing; WARN lines are noted, never blocking.
2. `sitemap:generate` (or wait for the 02:00 run) → verify `public/llms.txt` + `sitemap_index.xml`.
3. **GSC (Google Search Console)**: verify the domain, submit
   `https://sewahospitality.com/sitemap_index.xml`, request indexing for `/`,
   `/services`, `/cities`, `/careers`.
4. **GA4 + GTM**: put the ids into `.env` (`GA4_MEASUREMENT_ID`, `GTM_CONTAINER_ID`,
   `GA4_API_SECRET`) only when the analytics partial is to go live — the consent banner
   ships dark (no tags fire before an explicit choice, 02-analytics-plan §1.1) and
   server conversions (`generate_lead`) start flowing once configured.
5. Announce. Watch `/status` + the UptimeRobot keyword monitor through day one.

**2-week hypercare cadence (07-marketing-trust/04-growth-roadmap):**
- Daily 09:00 `ops:digest` (leads, SLA, failed jobs, AI failures + budget, translation
  review depth, invoice aging) — the email IS the ops queue. Review it every morning.
- Fast-fix cycle: any SEV-1 → roll back per §6; any SEV-2 → hotfix branch → CI green →
  redeploy → re-run `sewa:launch-verify`.
- Week-1 acceptance (roadmap §7): first organic leads with sources tagged · zero SEV-1 ·
  CWV p75 within budget on real traffic · review request fires on the first completed move.
- Schedule the first quarterly resilience drill (12-monitoring §8) before hypercare ends.

---

Related: `sewdocs/03-technical-specs/06-hosting-deployment.md` · `sewdocs/03-technical-specs/07-queues-scheduling.md` ·
`sewdocs/03-technical-specs/12-monitoring.md` · `sewdocs/03-technical-specs/13-testing-qa.md` · `./deploy.sh`
