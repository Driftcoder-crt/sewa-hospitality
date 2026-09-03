# 13 — Testing & QA

**Pest-driven test suite + CI gates + release checklist. Commercial-grade means: nothing ships without green tests, audited dependencies, and a 5-minute post-deploy verification.**

---

## 1. Test stack

| Layer | Tool | Scope |
|---|---|---|
| Unit | Pest | services, value objects, guards (circuit breaker, SLA calculator, idempotency store, locale detection, schema builder) |
| Feature (HTTP) | Pest + Laravel HTTP tests | routes render 200, forms validate, auth matrix, API envelopes, throttles, ETags |
| Livewire | Pest + Livewire test helpers | component state, island updates, draft persistence |
| Mail | Pest + fake mailer (Mailpit in staging) | template keys render, idempotency |
| Queue | Pest + `Queue::fake` | dispatch on commit, retry idempotency, fallbacks |
| Browser (smoke) | Playwright (local/CI optional; not required per release) | critical journeys: lead form, application form, portal login, admin login |
| SEO/health | Pest | single-H1, canonical present, sitemap contains entities, hreflang pairs, no accidental noindex |

CI: GitHub Actions (free) — `composer test` (Pest), `composer audit`, `npm audit`, `php -l`, build Vite artifact. **Red = no deploy.**

## 2. The QA gates (definition of done, per release)

1. **Tests green** (unit + feature + mail + queue suites; ≥ 85% coverage of module service classes; coverage target on money paths = 100%: leads, applications, billing, auth).
2. **Dependency audit clean** (no high/critical).
3. **Route health** — every route returns 200/302/401/403 as designed; zero 500s on the route map (the reference ships a live 500 page — this gate exists because of it).
4. **SEO snapshot suite** — for a fixed page set: one H1, unique meta title/description present and non-placeholder (the "metatitle" placeholder bug — gate exists because of it), canonical self-referential, hreflang set complete, JSON-LD parses and matches page type.
5. **Performance budget** ([../05-design-system/03-ux-interactions.md](../05-design-system/03-ux-interactions.md)):
   - Public page: HTML+CSS+JS ≤ 500 KB (before brotli), images per fold ≤ 400 KB, hero ≤ 200 KB
   - LCP ≤ 2.5s (4G throttle, staging), CLS ≤ 0.05, INP ≤ 200 ms
6. **Accessibility gate** — keyboard pass on changed pages, contrast checks on new tokens, alt-text presence on new media, focus-visible present.
7. **Security gate** — headers snapshot (CSP/HSTS/nosniff/referrer) on all surfaces; authz matrix suite green; no `.env`/vendor in artifact.
8. **Migration safety** — destructive migrations require a paired backup note + rollback plan in the PR ([03-database-schema.md](03-database-schema.md) §12).
9. **Post-deploy 5-minute verification** ([06-hosting-deployment.md](06-hosting-deployment.md) runbook) + Sentry release marker.

## 3. Critical-path test scenarios (must always exist)

### Leads (money path)
- Valid lead → 201, lead in DB, events written, ack + notification queued, SLA timer set.
- Idempotency: same `Idempotency-Key` twice → one lead, second returns original.
- Turnstile fail → 422 with friendly message; honeypot hit → soft-queued-to-review (never dropped silently).
- Throttle: 6th write in a minute from one IP → 429 + Retry-After.
- Locale: lead in `ar` → ack email rendered RTL with Arabic subject.
- Draft persistence: validation error → typed data retained (Livewire).

### Careers
- Application with 6 MB resume → 422 (max 5 MB), friendly guidance.
- Valid → application stored, resume in media library `careers/` (EXIF n/a, antivirus hook), ack + recruiter email queued, idempotent.
- Job closing date passed → posting renders "closed" + form disabled.

### Auth & portal
- Wrong password vs unknown email → identical response (no enumeration).
- 2FA required on admin → enforced for super-admin/admin roles.
- Client employee cannot see other org's documents/threads (authz suite × every portal endpoint).
- Password reset token expires at 60 min; single-use enforced.
- Document download URL: signed, 15-min expiry, audit row created, 404 after expiry.

### Billing
- Invoice number sequence concurrency-safe (parallel test ×2 = no duplicate).
- Quote → invoice conversion keeps totals; partial payments update status transitions correctly.

### I18n
- `/ja/…` renders `lang="ja"`, hreflang set includes all published locales, RTL for `ar` (`dir="rtl"`, logical CSS properties).
- Machine-translated content flagged `status=machine` until human-reviewed (renders with a review-state marker in admin, not on public page).
- Fallback: missing translation renders EN content, never a blank section.

### SEO & content
- Every publishable entity blocks publish if meta_description empty (no "metatitle" placeholders — ever).
- `noindex` field must be an explicit admin action; scheduled `seo:audit` flags noindexed pages with organic-traffic history ([07-queues-scheduling.md](07-queues-scheduling.md) §3).
- Sitemap contains: all published services/cities/posts (all locales), excludes noindex + search pages.

## 4. Staging discipline

- Staging = production mirror (same stack, seeded fixtures, basic auth + noindex).
- Feature branches deploy to staging automatically (CI), PR preview URL pattern `/preview/{branch}` where feasible.
- UAT: every module's admin screens get a scripted walkthrough checklist (per module doc in [../04-modules/](../04-modules/)) before release sign-off.

## 5. Bug triage flow

1. Sentry issue → auto-labeled by route/module.
2. Reproduce on staging → failing Pest test written first (the bug becomes a permanent regression test).
3. Fix → gate suite → deploy → Sentry release resolves.
4. SEV-1/2 → incident note in CHANGELOG + prevention ticket where applicable ([05-security-reliability.md](05-security-reliability.md) §6).

## 6. Test data & fixtures

- Factories for all content entities (realistic Sewa-flavored names/cities — no "Test User 1" screenshots leaking to UAT demos).
- Seed sets: `seed:demo` (staging), `seed:prod-minimal` (roles, settings, locales, categories — never fake content in production DB).
- Every fixture media ships alt text (so a11y gate passes on seeded pages too).

---

Related: [01-stack-and-dependencies.md](01-stack-and-dependencies.md) · [05-security-reliability.md](05-security-reliability.md) · [06-hosting-deployment.md](06-hosting-deployment.md) · [../05-design-system/03-ux-interactions.md](../05-design-system/03-ux-interactions.md) · [../09-delivery/01-build-roadmap.md](../09-delivery/01-build-roadmap.md)
