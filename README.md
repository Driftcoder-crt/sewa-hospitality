# SEWA HOSPITALITY — Platform

The corporate relocation, global mobility & hospitality platform for Sewa Hospitality Services Pvt. Ltd. — a
**Laravel 13 modular monolith** serving the public site, client portal (`app.`), custom admin (`admin.`) and
versioned API (`api.`) from **one codebase**. The frontend is **Livewire 4 + Alpine 3 + Tailwind 4.3 semantic
tokens** (Fraunces + Inter, oklch themes); data lives in **MySQL 8 with ULID primary keys**; queues, cache and
sessions run on the **database** with a **single hPanel cron** on Hostinger shared hosting — no Redis, no
daemons, no Node at runtime. The admin panel is a **custom Livewire build — zero admin packages** (no Filament,
no Nova, no Backpack).

## Requirements

- **PHP ^8.3** + Composer
- **Node 22 — LOCAL/CI ONLY** for Vite builds (the server never runs Node — locked decision)
- **MySQL 8** (the test suite runs on sqlite `:memory:` out of the box)

## Quickstart

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan sewa:setup
php artisan migrate
composer run seed:prod-minimal        # staging instead: composer run seed:demo
php artisan sewa:admin you@example.com
php artisan user:enable-2fa <admin email>
npm install
npm run dev
```

## Commands

| Command | What it does |
|---|---|
| `composer run test` | Pest suite, parallel (sqlite `:memory:` — CI gate 1) |
| `composer run lint` | Pint style check (CI gate) |
| `composer run audit` | `composer audit` — blocking CI gate 2 |
| `composer run seed:prod-minimal` | Roles, locales, organization identity — zero fake content; production-safe |
| `composer run seed:demo` | Staging fixtures — throws in production |
| `php artisan db:backup` | `mysqldump` → gzip into `config('sewa.backups_path')` (`SEWA_BACKUPS_PATH`) |
| `php artisan backups:verify` | Newest dump < 26 h old + size sane — failure = SEV-1 alert |
| `php artisan media:prune` | Orphaned media sweep — dry-run by default, `--force` to delete |
| `php artisan sewa:setup` | Environment doctor: PHP extensions, APP_KEY, storage symlink |
| `php artisan sewa:admin` | Create/promote a super-admin (production requires `--force` on existing) |
| `php artisan user:enable-2fa` | Enrol + confirm 2FA for an admin account (mandatory for super-admin/admin) |

**Scheduler note** — one hPanel cron hits `php artisan schedule:run` every minute; it drives: queue drain
bursts **×2/min** (`default,emails` + `ai,syncs,exports`), `pulse:check`, **`db:backup` 01:30**,
**`backups:verify` 08:00**, **`media:prune` Sun 03:00** — plus the every-minute scheduler heartbeat that feeds
`/status` and UptimeRobot. Full runbook: [`DEPLOY.md`](DEPLOY.md).

## Architecture

Fourteen domain modules live under `app/Modules/{Cms,Services,Cities,Blog,Leads,Careers,Organizations,Testimonials,Csr,Portal,Billing,I18n,Ai,Search}`,
glued by the `app/Support/` kernel (`Locks` — mutex/circuit-breaker/idempotency, `Security`, `Media`, `Audit`, …).
**One codebase, four hosts:** `sewahospitality.com` (site) · `app.` (portal) · `admin.` (admin) · `api.`
(`/v1`) — all resolve to the same `public/`, routing distinguishes by host; `media.` serves storage statically
with immutable cache headers. Full module map, ADRs and error-locks doctrine:
[`sewdocs/03-technical-specs/02-architecture.md`](sewdocs/03-technical-specs/02-architecture.md).

> **⚠️ IMPORTANT — media table ownership**
> The `media` table is owned by **OUR migration**
> [`database/migrations/0001_01_01_000000_create_media_table.php`](database/migrations/0001_01_01_000000_create_media_table.php).
> **NEVER** run `php artisan vendor:publish --tag=media-library-migrations` (or any Spatie media-library
> migration publish command) — the package's migrations would collide with ours and break the pipeline.
> `config/media-library.php` already points at `App\Models\Media` and ships the Spatie columns we need.

## Milestones

| M | Scope | Status |
|---|---|---|
| **M0** | Foundation: scaffold, auth + 2FA, roles, settings, media pipeline, CI, deploy tooling | ✅ **Code complete** |
| **M1** | CMS core | ✅ Code complete — pending suite-green verification |
| **M2** | Services / Cities / Housing | ✅ Code complete — pending suite-green verification |
| **M3** | Leads + Careers | ✅ Code complete — pending suite-green verification |
| **M4** | Blog / Testimonials / CSR | ✅ Code complete — pending suite-green verification |
| **M5** | Portal + Billing | ✅ Code complete — pending suite-green verification |
| **M6** | i18n + AI + hardening | ✅ Code complete — pending suite-green verification |
| **M7** | Launch | Planned — blocked on M1–M6 gates going green |

> **Status key:** "Code complete" = the module's code, tests and seeders exist
> and the acceptance criteria are implemented; "pending suite-green" = the full
> Pest run must pass before the milestone is declared done per the build
> prompt's error lock #8. Milestone sign-off is tracked per
> [`sewdocs/09-delivery/01-build-roadmap.md`](sewdocs/09-delivery/01-build-roadmap.md).

Scope and acceptance criteria per milestone: [`sewdocs/09-delivery/01-build-roadmap.md`](sewdocs/09-delivery/01-build-roadmap.md).

## Docs

- **[`./sewdocs/`](sewdocs/)** is the audited contract suite — **62 docs**; [`sewdocs/09-delivery/03-build-prompt.md`](sewdocs/09-delivery/03-build-prompt.md) governs the build process.
- **CI:** [`.github/workflows/ci.yml`](.github/workflows/ci.yml) — 13-testing-qa §2 gates 1–4 on every push/PR; red = no deploy.
- **Deploy:** [`./deploy.sh`](deploy.sh) builds release artifacts locally/CI (server never runs Node); [`DEPLOY.md`](DEPLOY.md) is the Hostinger runbook.
- **Env deltas:** copy `.env.example` to `.env`, then apply [`.env.staging.example`](.env.staging.example) / [`.env.production.example`](.env.production.example).
