# 06 — Reference Weaknesses & Sewa Opportunities

**Every defect found in the reference platform, each paired with the Sewa fix. This is the master "beat them here" list — nothing ignored, everything actionable.**

---

## A. Architecture & engineering

| # | Reference weakness | Impact | Sewa fix (spec) |
|---|---|---|---|
| A1 | 3 separate systems (Next.js 11 + WordPress 6.8 + CodeIgniter portal) | 3 codebases, 3 deploys, 3 design systems, drift everywhere | One Laravel 13 modular monolith ([../03-technical-specs/02-architecture.md](../03-technical-specs/02-architecture.md)) |
| A2 | Content hardcoded in JS app; edits require engineering | Marketing can't ship anything | Full CMS module ([../04-modules/01-cms.md](../04-modules/01-cms.md)) |
| A3 | 2011-era React 17/Next 11 + Bootstrap 4 + triple jQuery (3.7/3.6/1.8.3) | Security updates, perf, maintainability | Laravel 13 + Livewire 4 + Alpine 3 + Tailwind 4.3 ([../03-technical-specs/01-stack-and-dependencies.md](../03-technical-specs/01-stack-and-dependencies.md)) |
| A4 | 918 KB CSS across 8 files; 3 font families full-range; 2.5 MB hero images; GIF hero | CWV failures, mobile cost | Tailwind token build + media pipeline with WebP/AVIF + budgets enforced in CI ([../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md)) |
| A5 | Accordion content duplicated in DOM per breakpoint | Double content, maintenance hazard, SEO duplication | Single-source components ([../05-design-system/02-ui-components.md](../05-design-system/02-ui-components.md)) |
| A6 | Eager Google Maps iframes ×8–9 on contact page | Slow page, GDPR exposure | Click-to-load map facade + schema LocalBusiness ([../04-modules/03-leads-crm.md](../04-modules/03-leads-crm.md)) |
| A7 | YouTube iframe in modal without facade | JS weight | Facade pattern ([../05-design-system/03-ux-interactions.md](../05-design-system/03-ux-interactions.md)) |
| A8 | `wellbeing-support` page = HTTP 500 in production | Broken customer journey | Route health checks + QA gates; zero-500 policy ([../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md)) |
| A9 | Job detail routes exist but 404; awards route orphaned | Dead links, crawl waste | No orphan routes; every route renders or is redirected (admin redirect manager) |
| A10 | No sitemap.xml, no robots.txt | Crawl/index inefficiency | Auto sitemap + robots + Search Console wiring ([../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md)) |
| A11 | No error tracking/monitoring found | Silent failures | Pulse + Sentry + UptimeRobot ([../03-technical-specs/12-monitoring.md](../03-technical-specs/12-monitoring.md)) |
| A12 | API unversioned, typo'd envelope (`stausCode`), data-as-string, no idempotency | Brittle, unsafe integrations | Versioned /v1, typed envelopes, idempotency keys ([../03-technical-specs/04-api-spec.md](../03-technical-specs/04-api-spec.md)) |
| A13 | Form failures silent (redirect back, data lost) | Lost leads = lost revenue | Livewire forms: inline errors, draft persistence, queue + retries, duplicate detection ([../04-modules/03-leads-crm.md](../04-modules/03-leads-crm.md)) |
| A14 | No queues anywhere visible (sync submits) | Timeout risk on uploads, email | Database queues + single cron scheduler ([../03-technical-specs/07-queues-scheduling.md](../03-technical-specs/07-queues-scheduling.md)) |

## B. Content & SEO

| # | Weakness | Sewa fix |
|---|---|---|
| B1 | Blog author = "admin" everywhere (zero E-E-A-T) | Named authors with profiles + credentials + sameAs ([../04-modules/07-blog-news.md](../04-modules/07-blog-news.md)) |
| B2 | Double H1 on every post | One H1 rule, template-enforced + CI check |
| B3 | Placeholder metas ("metatitle") live on news pages | Meta required at publish; empty = draft blocked |
| B4 | A 2026 post accidentally noindexed | Index state is an explicit, visible admin field with alerts for anomalies |
| B5 | Self-declared 9.9/1024 rating with no visible reviews | Real reviews on-page with sources; schema matches visible content only |
| B6 | No JSON-LD on money pages (services/contact/about) | Full schema graph incl. LocalBusiness per office, Service, FAQ ([../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md)) |
| B7 | `<html lang="">` empty; no real hreflang | Proper lang/dir + hreflang per locale ([../06-content-seo/04-multilingual-content.md](../06-content-seo/04-multilingual-content.md)) |
| B8 | Tag-cloud sidebar links (posts link to unrelated tags) | Per-post terms only; sitewide cloud on tags index |
| B9 | News section = 3 stale items (2021) | Unified blog+news editorial calendar ([../04-modules/07-blog-news.md](../04-modules/07-blog-news.md)) |
| B10 | Newsletter form does nothing (action="#") | Real double-opt-in newsletter ([../04-modules/03-leads-crm.md](../04-modules/03-leads-crm.md)) |
| B11 | Comments: 0 across all posts (dead UI) | Off by default; AEO FAQ + email replies instead |
| B12 | City guides stopped in 2021 (8 cities, one series) | All-India city program with search-intent research ([../06-content-seo/03-city-content-program.md](../06-content-seo/03-city-content-program.md)) |
| B13 | English-only (the .jp site is a separate dead-end) | In-platform multilingual: ko/ja/tr/ar RTL + auto-detect ([../04-modules/11-multilingual.md](../04-modules/11-multilingual.md)) |
| B14 | Legacy meta spam (DC.*, revisit-after) | Clean modern head set ([../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md)) |
| B15 | No AEO/LLM optimization at all | Answer-first content, llms.txt, FAQ schema, citation-friendly structure ([../06-content-seo/05-aeo-llm-presence.md](../06-content-seo/05-aeo-llm-presence.md)) |

## C. UX & accessibility

| # | Weakness | Sewa fix |
|---|---|---|
| C1 | Hamburger-only nav even on desktop | Desktop nav + mobile off-canvas ([../05-design-system/02-ui-components.md](../05-design-system/02-ui-components.md)) |
| C2 | Hover-only leader bios | Tap/click + focusable; content in DOM |
| C3 | CSS-background galleries (no alt text) | Real img + alt + lazy loading |
| C4 | 3-second self-destructing error messages | Persistent, dismissible, ARIA-announced errors |
| C5 | No skip-link, no focus-visible, contrast issues on blush | WCAG 2.2 AA baseline ([../05-design-system/03-ux-interactions.md](../05-design-system/03-ux-interactions.md)) |
| C6 | Job applications modal-only; no per-job pages | `/careers/{slug}` pages + quick-apply modal ([../04-modules/06-hr-employee-module.md](../04-modules/06-hr-employee-module.md)) |
| C7 | No search on main site | Scout search sitewide ([../03-technical-specs/08-search.md](../03-technical-specs/08-search.md)) |
| C8 | Services push visitors OUT to 7 sister sites | On-domain coverage; ventures strip becomes a soft cross-sell ([../01-platform-vision/04-subdomains-ventures.md](../01-platform-vision/04-subdomains-ventures.md)) |
| C9 | No chat/WhatsApp contact path (expats expect instant contact) | Realtime chat with async fallback + WhatsApp deep-link (respecting consent) ([../04-modules/04-client-portal.md](../04-modules/04-client-portal.md)) |
| C10 | No breadcrumbs | BreadcrumbList schema + visible trail |

## D. Business/trust gaps

| # | Weakness | Sewa opportunity |
|---|---|---|
| D1 | 24 testimonials with no source/date/verification | Review system with GBP sync + per-service pages ([../04-modules/08-testimonials-reviews.md](../04-modules/08-testimonials-reviews.md)) |
| D2 | GBP: 4.3★/11 reviews, category "Condominium rental agency" | Category correction + review engine → 4.7★/100+ target ([../07-marketing-trust/01-google-ecosystem.md](../07-marketing-trust/01-google-ecosystem.md)) |
| D3 | 20 membership logos incl. certifications with no proof pages | Only badges held, each linking to a verification page ([../07-marketing-trust/03-trust-authority.md](../07-marketing-trust/03-trust-authority.md)) |
| D4 | No pricing transparency anywhere | Published rate ranges + instant quote workflow ([../04-modules/12-billing-finance.md](../04-modules/12-billing-finance.md)) |
| D5 | Client portal = login + (inaccessible) dashboard; no visible features | Real portal: documents, tracking, chat, invoices ([../04-modules/04-client-portal.md](../04-modules/04-client-portal.md)) |
| D6 | Careers: no per-job pages, no applicant tracking | Full ATS-lite: postings→applications→pipeline ([../04-modules/06-hr-employee-module.md](../04-modules/06-hr-employee-module.md)) |
| D7 | No HR/employee/ops tooling visible | HR module (employees, docs, leave) powering team pages + authors |
| D8 | No finance/billing surface | Billing module: quotes→invoices→payments ([../04-modules/12-billing-finance.md](../04-modules/12-billing-finance.md)) |
| D9 | Japan-market = separate .jp site; no ko/tr/ar at all | Auto-detect + locale paths; targeted content for Korean/Japanese/Turkish/Saudi clients ([../06-content-seo/04-multilingual-content.md](../06-content-seo/04-multilingual-content.md)) |
| D10 | Legacy UA tag still firing on blog (dual-tagging) | Single GA4 via GTM + Consent Mode v2 ([../07-marketing-trust/02-analytics-plan.md](../07-marketing-trust/02-analytics-plan.md)) |
| D11 | FB pixel + Clarity without consent gating | Consent-first analytics ([../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md)) |
| D12 | Values shown only as a graphic; leadership = single-bio depth | Values as real content; leadership as profiles with credentials |
| D13 | "Contact us" CTA is the only conversion path | Multiple intents: quote, callback, demo, chat, per-service forms ([../04-modules/03-leads-crm.md](../04-modules/03-leads-crm.md)) |
| D14 | 404 page exists but no search box/help on it | 404 with search + top services + contact |
| D15 | No llms.txt, no AI-era presence | AEO program ([../06-content-seo/05-aeo-llm-presence.md](../06-content-seo/05-aeo-llm-presence.md)) |

## E. Quick-win scoreboard (first 90 days post-launch)

1. Sitemap + robots + Search Console + hreflang (immediate index coverage).
2. Fix-analogues by design: single H1, named authors, per-page metas, no duplicate DOM.
3. GBP category fix + review engine started.
4. City program seeds 10 cities with real search-intent pages.
5. ko/ja/tr/ar locale launch for core pages (auto-detect + human-reviewed).
6. Schema graph incl. LocalBusiness (10 offices day one) + visible reviews.
7. Performance budgets enforced (CI gate) — instant CWV win vs. reference.
8. Newsletter + lead pipeline live (every form feeds CRM with SLA timers).

---

Related: all of Section 02 · [../01-platform-vision/01-executive-summary.md](../01-platform-vision/01-executive-summary.md) (pillar table) · [../09-delivery/01-build-roadmap.md](../09-delivery/01-build-roadmap.md) (phasing)
