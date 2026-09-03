# 02 — System Architecture

**The Sewa platform is one Laravel 13 modular monolith, properly divided, deployable on shared hosting, and ready to grow into portals, apps, and venture sites without rewrites.**

---

## 1. Bird's-eye view

```
                        Cloudflare (free: DNS · SSL · CDN · Turnstile · WAF)
                                        │
        ┌───────────────────┬──────────┴──────────┬─────────────────────┐
        ▼                   ▼                     ▼                     ▼
 sewahospitality.com  app.sewa…  admin.sewa…  api.sewa…        media.sewa…
   (public site)      (client     (admin        (versioned         (Spatie media
   Blade + Livewire    portal)     panel)       REST /v1)          conversions,
   marketing CMS,     Sanctum     Sanctum +    Sanctum tokens      Cloudflare
   blog, cities,       sessions    roles        + rate limits      cache-everything)
   services, leads    + Ably      + 2FA
        └───────────────────┴──────────┬──────────┴─────────────────────┘
                                        ▼
                         ONE Laravel 13 app  ·  ONE MySQL database
                         (modular monolith — 14 bounded modules)
                                        │
              ┌─────────────┬───────────┼──────────────┬──────────────┐
              ▼             ▼           ▼              ▼              ▼
         DB queues      Scheduler   AI SDK         Brevo/Resend      Ably Echo
         (cron-driven)  (hPanel     (TokenRouter/  (transactional     (push, with
                        cron)       OpenRouter)    email)             native fallback)
```

## 2. Modular monolith — module boundaries

**Rule:** each module owns its models, policies, events, jobs, Livewire components, routes, and admin screens. Cross-module calls go through **service classes** or **events/listeners**, never direct Eloquent reach-arounds. This is the "proper divide" — real boundaries, one deployable.

```
app/
├── Modules/
│   ├── Cms/              (pages, blocks, menus, settings, redirects)
│   ├── Services/         (service catalog entities + service landing pages)
│   ├── Cities/            (city program entities)
│   ├── Blog/              (posts, categories, tags, authors, news flag)
│   ├── Leads/             (forms → leads → pipeline → SLA timers)
│   ├── Careers/            (job postings + public careers site)
│   ├── Hr/                (employees, docs, leave, authors, team pages)
│   ├── Testimonials/      (testimonials + Google review sync)
│   ├── Csr/               (NGO partners, CSR stories)
│   ├── Portal/            (client portal: auth, dashboard, docs, chat, tracking)
│   ├── Billing/            (quotes, invoices, payments)
│   ├── I18n/               (locales, translations, detection, fallbacks)
│   └── Ai/                (AI SDK wiring, provider config, guardrails)
├── Support/               (shared kernel: components, helpers — small, controlled)
└── (Laravel core dirs)
```

Module interaction rules:
1. A module may listen to another module's events (e.g. Leads listens to `MoveCompleted` from Portal to trigger review requests).
2. A module may inject its entities into another module's **admin** only via defined interfaces (e.g. CMS page builder lists Services as linkable targets).
3. No module touches another module's tables directly; join through service contracts.
4. Public routes: `/` (site), `/blog`, `/cities`, `/careers`, `/housing`, `/csr`, `/legal/*`. Portal: `app.sewa…`. Admin: `admin.sewa…`. API: `api.sewa…/v1/*`.

## 3. Request flows

### 3.1 Public page render
```
Request → Cloudflare (cache HIT for anonymous GETs) → Laravel
  → route → Controller (thin)
  → Eloquent with per-locale scopes (N+1 guarded, eager loads explicit)
  → Blade view (SEO meta service renders title/canonical/hreflang/JSON-LD)
  → Response (public cache headers; ETag for 304s)
```
- Anonymous full-page caching: response cache keyed `path+locale+currency(n/a)`, invalidated on CMS saves (event-driven). Shared-hosting CPU discipline.
- Never cache POST/PUT, authenticated pages, or query-stringed lead form refills.

### 3.2 Lead submission (representative "write" flow)
```
Livewire form → server validation + Turnstile verify + honeypot
  → DB transaction: create lead (idempotency-keyed)
  → dispatch QueuedJobs\NotifyLead (email) + \Modules\Ai jobs (enrichment, optional)
  → event LeadReceived → listeners (alerts, analytics server-event, SLA timer)
  → user sees inline success + /thank-you view (no redirect data loss)
```

### 3.3 Client portal session
Sanctum SPA-session on `app.sewa…` → role-scoped data (client vs relocating employee) → Ably private channels (`portal.user.{id}`) with capability tokens minted server-side; **every realtime UI also works on `wire:poll` fallback** ([11-realtime.md](11-realtime.md)).

### 3.4 API (future mobile app)
`api.sewa…/v1/*` — Sanctum token auth with scopes, throttled, ETag'd, OpenAPI-generated docs. Same controllers as Livewire where possible (form requests shared).

## 4. Subdomain routing map

| Subdomain | Laravel routing | Middleware |
|---|---|---|
| `sewahospitality.com` | routes/web.php (site area) | locale-detect, response-cache, turnstile-on-forms |
| `app.` | routes/portal.php | auth:sanctum, portal.role, ably-token, no-index |
| `admin.` | routes/admin.php | auth, role, 2FA (super-admin/admin), no-index, audit-logging |
| `api.` | routes/api.php (`/v1`) | auth:sanctum + scope, throttle:api |
| `media.` | static storage (media library conversions) | immutable cache headers |

One codebase, one deploy; the host resolves all four subdomains to the same Laravel `public/`.

## 5. Data ownership map

| Data | Owner module | Consumers |
|---|---|---|
| Pages, banners, CTA bands, stats, menus, redirects | Cms | every public page |
| Services tree + landing content | Services | CMS links, Leads tagging, Search |
| Cities + city pages | Cities | Services cross-links, Blog |
| Posts/categories/tags/authors (blog + news) | Blog | Search, Sitemap, AEO |
| Leads + newsletter | Leads | Billing (conversion), Analytics |
| Jobs + applications | Careers + Hr | ATS pipeline |
| Employees/team | Hr | About page, authors, consultants |
| Testimonials + GBP reviews | Testimonials | Service pages, Home |
| NGO partners + CSR stories | Csr | CSR page |
| Quotes/invoices/payments | Billing | Portal, admin |
| Users/roles | Laravel + Spatie | Admin, Portal |
| Media | Spatie on all content models | media.sewa… |
| Locales + translations | I18n | all render paths |

## 6. Cross-cutting services (the shared kernel, deliberately small)

| Service | Responsibility |
|---|---|
| `Seo\Meta` | per-page title/description/canonical/hreflang/robots from templates + per-entity overrides |
| `Schema\Graph` | one JSON-LD graph per page (Organization, LocalBusiness, Service, Article, FAQ, Breadcrumb, Review — only when matching visible content) |
| `Locale\Context` | detection (header/geo/cookie), resolution, hreflang generation, RTL flag |
| `Realtime\Transport` | transport chooser (native poll vs Ably) behind one interface |
| `Ai\Gateway` | AI SDK facade: providers, fallback chain, budget guards ([../08-ai-system/01-ai-architecture.md](../08-ai-system/01-ai-architecture.md)) |
| `Locks` | named mutexes (lead dedupe, invoice numbering, cron overlap prevention) |
| `Audit` | activity log for admin/portal writes |

## 7. Failure philosophy (the "error locks" doctrine)

1. **No silent failure:** every external call is wrapped by the circuit breaker; failures surface in Pulse/Sentry and to users as a safe, actionable message.
2. **Degrade, never break:** AI unavailable → fall back to cached/native behavior (e.g., machine translations cache, forms work without enrichment). Ably unavailable → polling fallback automatically. Email provider down → queue retries + alert ops (forms never blocked).
3. **Writes are transactional:** lead/application/invoice writes are single-transaction with idempotency keys; a retried network call cannot double-submit.
4. **Cron overlap-safe:** scheduler commands use cache-based locks; long jobs go to queues, not the schedule.
5. **Budget guards:** AI and email providers have monthly/daily counters with alerting before limits, and hard-stop → fallback behavior after.

## 8. Architecture decision records (summary)

| ADR | Decision | Rationale (one line) |
|---|---|---|
| ADR-001 | Modular monolith, not microservices | One shared host, one team, one deploy; boundaries by convention+enforcement |
| ADR-002 | Blade+Livewire, not Inertia SPA | SSR-first SEO, shared-hosting simplicity, smaller JS |
| ADR-003 | Locale path prefixes | hreflang correctness + one domain's equity |
| ADR-004 | Media on `media.` with immutable URLs | Max CDN caching; no cache invalidation problem |
| ADR-005 | Blog in-platform, not WordPress | Kill two-system drift; better SEO tooling; one design system |
| ADR-006 | Custom admin, no admin package | Control, security surface, no upgrade lock (Filament excluded) |
| ADR-007 | DB queues + single cron | Only reliable worker pattern on shared hosting |
| ADR-008 | Ably now, Reverb later | True push without a server; protocol-compatible swap |
| ADR-009 | Scout database driver | Zero-ops search; swap to Typesense Cloud without app changes |
| ADR-010 | Sanitization/"venture" content stays on-domain | SEO equity; split later only with proof (see [../01-platform-vision/04-subdomains-ventures.md](../01-platform-vision/04-subdomains-ventures.md)) |

---

Related: [01-stack-and-dependencies.md](01-stack-and-dependencies.md) · [03-database-schema.md](03-database-schema.md) · [04-api-spec.md](04-api-spec.md) · [05-security-reliability.md](05-security-reliability.md) · [../09-delivery/02-future-scaling.md](../09-delivery/02-future-scaling.md)
