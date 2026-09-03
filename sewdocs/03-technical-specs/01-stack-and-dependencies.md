# 01 — Stack & Dependency Policy

**The locked technology stack for the Sewa platform. Every choice is first-party-first, shared-hosting-true, and deliberately modern. No primitive or problematic dependencies.**

---

## 1. The stack (locked)

| Layer | Technology | Version discipline | Why |
|---|---|---|---|
| Language | PHP | `^8.3` pinned in composer.json | Hostinger-confirmed environment; pin prevents accidental 8.4-only features |
| Framework | Laravel | 13.x (current 13.21), `^13.21` | First-party AI SDK (production-stable since 13.0), modern queue/event/permission tooling, long-term support |
| Frontend runtime | Livewire | 4.x (Jan 2026 major) | Islands architecture (isolated regions update independently = realtime-feel dashboards without websockets), view-based single-file components, `wire:sort`, JS interceptor layer for fine request control |
| Client interactivity | Alpine.js | 3.x | Lightweight islands for dropdowns/accordions/carousels; zero extra build complexity |
| Styling | Tailwind CSS | 4.3.x (`^4.3`) | `@theme` design tokens, expanded logical properties for RTL (Arabic support), Vite plugin |
| Build | Vite | current | Built **locally** (shared hosting has no Node); output = static assets + manifest |
| Database | MySQL | 8.x (Hostinger default) | No reason to fight the host |
| Search | Laravel Scout — `database` driver | latest | MySQL full-text, zero external service; driver-swappable to Typesense Cloud later without app changes |
| Auth | Laravel Sanctum | latest | Web sessions + mobile-app API tokens in one first-party package |
| Permissions | Spatie laravel-permission | latest | Standard, actively maintained, role/permission matrix across admin/HR/CSR modules |
| Media | Spatie laravel-medialibrary | latest | Conversions (thumb/card/hero), local disk (S3-ready later) |
| Queue | Laravel `database` driver | — | Shared hosting has no Redis, no daemon; see [07-queues-scheduling.md](07-queues-scheduling.md) |
| Scheduler | single hPanel cron → `artisan schedule:run` every minute | — | The confirmed working pattern on Hostinger shared hosting |
| Email | Resend or Brevo free tier | — | Deliverability >> raw Hostinger SMTP (SPF/DKIM/DMARC), see [10-email.md](10-email.md) |
| Realtime | Livewire islands + `wire:poll` (native) → Ably free tier via Echo (push) | — | 6M msgs/mo, no daily cap; see [11-realtime.md](11-realtime.md) |
| Captcha | Cloudflare Turnstile | — | Free, privacy-friendly, less friction than reCAPTCHA |
| CDN/DNS/SSL | Cloudflare free plan | — | SSL, DDoS mitigation, caching — the single highest-leverage free addition |
| Monitoring | Laravel Pulse (database driver) + Sentry free + UptimeRobot free | — | See [12-monitoring.md](12-monitoring.md) |
| AI | Laravel first-party AI SDK; providers: TokenRouter / OpenRouter (z-ai/glm-5.3-free) | — | See [../08-ai-system/01-ai-architecture.md](../08-ai-system/01-ai-architecture.md) |
| Testing | Pest | latest | Pest = modern, fast, readable; [13-testing-qa.md](13-testing-qa.md) |

## 2. Dependency policy (binding rules)

1. **First-party first.** If Laravel ships it (Sanctum, Pulse, Scout, broadcasting, the AI SDK), we do not add a composer package for the same job.
2. **No admin frameworks.** Filament is excluded by decision. No Backpack, no Nova, no custom admin package — the admin panel is built with Livewire 4 + Alpine + Tailwind ([../04-modules/05-admin-panel.md](../04-modules/05-admin-panel.md)). Rationale: zero upgrade-lock risk, total design control, no third-party code in the most security-sensitive surface.
3. **Every third-party package must be:** actively maintained (commit activity in last 90 days), widely adopted (rough order-of-magnitude ≥ 1M installs for Laravel packages), no abandoned fork issues, compatible with PHP 8.3 and Laravel 13, and solving a problem the framework genuinely doesn't.
4. **Frontend dependencies:** none beyond Alpine + Livewire. No jQuery, no Bootstrap, no AOS/waypoints/countup equivalents (Alpine `x-intersect`), no moment/luxon unless required by Livewire's own stack, no CSS-in-JS, no component mega-libraries. Carousels via CSS scroll-snap + Alpine unless a real requirement forces Swiper.
5. **Vetted additions allowed when a concrete need appears** (with justification in the PR): `spatie/laravel-sitemap` (or first-party equivalent if shipped by 13.x), `spatie/laravel-honeypot` (spam), `spatie/laravel-responsecache` or first-party cache (shared-hosting CPU discipline) — the full current allowlist is in §3 below.
6. **Lock file is law.** `composer.lock` and `package-lock.json` committed. Upgrades via `composer outdated` review, one PR per minor, never blind.
7. **No runtime npm.** Shared hosting never runs Node. `npm run build` is a local/CI step; output assets + `build-manifest.json` deploy only.

## 3. Composer allowlist (the only third-party packages at launch)

| Package | Purpose | Module |
|---|---|---|
| `spatie/laravel-permission` | Roles & permissions | all |
| `spatie/laravel-medialibrary` | Media conversions | CMS, all content modules |
| `spatie/laravel-honeypot` | Invisible form spam protection | all public forms |
| `spatie/laravel-sitemap` | XML sitemap generation | SEO (unless first-party equivalent ships) |
| `laravel/scout` | Search abstraction (database driver) | Search |
| `laravel/sanctum` | API tokens | Auth + API |
| `ably/laravel-broadcaster` (or first-party Echo-compatible driver) | Realtime push | Realtime |
| `resend/resend-laravel` or `mailersend`/Brevo SDK-free SMTP | Email | Email (SMTP transport preferred = zero SDK) |
| `laravel/pulse` | Monitoring | Ops |
| `sentry/sentry-laravel` (+ browser loader) | Error tracking ([12-monitoring.md](12-monitoring.md)) | Ops |

Everything else ships with Laravel 13. Anything not on this list requires a dependency-review note in the PR.

## 4. Frontend asset rules

- **One Tailwind build per site area** (public site, portal, admin share one config/tokens file, different content roots) — keeps per-page CSS ~30–60 KB.
- Design tokens defined once in `@theme` ([../05-design-system/01-brand-guidelines.md](../05-design-system/01-brand-guidelines.md)) — colors, type scale, spacing, radii, motion, shadows. No magic values in templates.
- Fonts: humanist sans, self-hosted, subset (Latin + Devanagari when Hindi added), `font-display: swap`, preload 2 weights max per page group.
- Icons: single inline SVG sprite (~30 icons) — no icon-font CSS.
- JS: Alpine core + Livewire runtime + Turnstile + Echo (portal only) + one site.js (~< 40 KB combined, brotli).
- Images: WebP/AVIF conversions, width/height attrs, `loading=lazy` below fold, explicit aspect-ratio boxes ([09-media-pipeline.md](09-media-pipeline.md)).

## 5. Version pinning & upgrade cadence

| Concern | Rule |
|---|---|
| PHP | `^8.3` in composer.json; never use 8.4-only syntax until Hostinger confirms 8.4 |
| Laravel | `^13.21`; security patches within 48h; minors monthly in a maintenance window |
| Livewire | `^4.0`; majors reviewed before upgrade (islands API stability) |
| Tailwind | `^4.3`; tokens are config — upgrades are usually safe |
| Alpine | `^3.x`; tiny, stable |
| Database | MySQL 8 (strict mode ON, utf8mb4, InnoDB) |
| Lock files | committed, reviewed in every PR |

## 6. Explicit exclusions (with reasons, for the record)

| Excluded | Reason |
|---|---|
| Filament (any version) | Excluded by directive — admin frameworks create upgrade-lock, dependency sprawl, and design constraints; custom Livewire admin is more robust and stable for our needs |
| WordPress | The reference's blog is WP; a WP install inside the platform would re-create the two-system drift problem — content lives in our CMS with better SEO tooling |
| Inertia/React/Vue SPA | Shared hosting + content site = SSR Blade/Livewire is faster, simpler, and SEO-native; a SPA adds nothing but payload |
| jQuery / Bootstrap | Replaced by Alpine + Tailwind |
| Redis (at launch) | Not available on Hostinger shared; database driver covers queues/cache/Pulse; migration path in [../09-delivery/02-future-scaling.md](../09-delivery/02-future-scaling.md) |
| Meilisearch/Typesense (at launch) | Needs a persistent process — impossible on shared hosting; Scout database driver first, hosted search later |
| Reverb (at launch) | Needs a long-running process; Ably fills the gap; Reverb = Phase 2 on a VPS |
| Octane | Requires process persistence; classic FPM is correct here |
| reCAPTCHA | Turnstile chosen (friction, privacy, cost) |
| Pusher | Ably chosen (6M msgs/mo free, no daily cap, exactly-once delivery, native Echo support) |

## 7. Local build & deploy flow (summary — full runbook in [06-hosting-deployment.md](06-hosting-deployment.md))

```
Local dev:   composer install && npm install && npm run dev      (Vite HMR)
Local build: npm run build   → public/build/*  (hashed assets + manifest)
Deploy:      rsync/SFTP code (vendor/ + public/build prebuilt) → Hostinger
             php artisan migrate --force  (one-off via SSH or hPanel cron runner)
             php artisan optimize         (config/route/view caches)
Scheduler:   hPanel cron: * * * * *  php artisan schedule:run
```

---

Related: [02-architecture.md](02-architecture.md) · [06-hosting-deployment.md](06-hosting-deployment.md) · [11-realtime.md](11-realtime.md) · [../01-platform-vision/04-subdomains-ventures.md](../01-platform-vision/04-subdomains-ventures.md)
