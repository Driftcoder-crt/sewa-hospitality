# 01 — Build Roadmap

**The phased build plan: milestones M0–M7 with acceptance criteria, gated like the QA gates in [../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md). Each milestone ends deployable on staging; production launch = M6.**

---

## Milestone 0 — Foundation (week 1–2)
**Scope:** Laravel 13 project, PHP ^8.3, Livewire 4 + Alpine 3 + Tailwind 4.3 + Vite pipeline; CI (Pest, audits, route health); Hostinger deploy skeleton (staging + production subdomains, single cron, Cloudflare DNS/SSL, Turnstile keys); auth + roles (Spatie matrix seed), Sanctum, 2FA on admin; settings + organization identity (NAP single-source); media pipeline (Spatie, namespaces, conversions, alt-required).
**Accept if:** staging deploys via runbook ([../03-technical-specs/06-hosting-deployment.md](../03-technical-specs/06-hosting-deployment.md) §7) in < 15 minutes; `/v1/health` green under UptimeRobot; admin login + 2FA works; test image upload produces all conversions with enforced alt; backups verify.

## Milestone 1 — CMS + content core (week 3–4)
**Scope:** CMS module ([../04-modules/01-cms.md](../04-modules/01-cms.md)): pages, block library (all 17 blocks), menus, redirects, revisions; admin panel shell (sidebar, ⌘K palette, table/index + editor patterns, toasts); design tokens file + core components (buttons, forms, hero, cards, accordion, alert); `/dev/components` preview route.
**Accept if:** a non-technical editor composes a homepage from blocks with live preview + autosave + revisions; publish gates (SEO, alt, noindex-confirm) block bad publishes; component states render in `/dev/components` (incl. RTL); CI gates 1–4 green.

## Milestone 2 — Services + Cities + Housing (week 5–6)
**Scope:** Services module ([../04-modules/02-services-module.md](../04-modules/02-services-module.md)) full catalog (14 URLs incl. immigration sub-tree); Cities & Housing ([../04-modules/10-cities-content.md](../04-modules/10-cities-content.md)) — city template blocks, housing CRUD + verified standard + inventory browser; search (Scout DB driver) + unified search page; SEO graph (Organization, LocalBusiness, Service, FAQ, Breadcrumb) + sitemap/robots generators.
**Accept if:** all service URLs render with schema + single H1; city page compose-from-blocks works; housing filters + zero-state pass; sitemap contains all published entities (CI test); search returns grouped hits < 150 ms (staging).

## Milestone 3 — Intake: Leads + Careers/HR (week 7–8)
**Scope:** Leads/CRM module ([../04-modules/03-leads-crm.md](../04-modules/03-leads-crm.md)) (forms, Turnstile+honeypot+idempotency, inbox, pipeline, SLA timers, newsletter double-opt-in); Careers/HR ([../04-modules/06-hr-employee-module.md](../04-modules/06-hr-employee-module.md)) — job postings + per-job pages, applications with resume upload, ATS kanban, employees/author profiles, team surfaces; email system ([../03-technical-specs/10-email.md](../03-technical-specs/10-email.md)) — provider + fallback chain, template catalog rendered.
**Accept if:** money-path test suite green ([../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md) §3 Leads/Careers); a real lead traverses form → CRM → ack email → ops digest; SLA breach alert fires (simulated); job page + application e2e works incl. bad-resume rejection.

## Milestone 4 — Blog/News + Testimonials + CSR (week 9–10)
**Scope:** Blog/News ([../04-modules/07-blog-news.md](../04-modules/07-blog-news.md)) — editor, review workflow, calendar, taxonomy, post pages with authorship; Testimonials/Reviews ([../04-modules/08-testimonials-reviews.md](../04-modules/08-testimonials-reviews.md)) — manager, GBP sync, review-request engine, schema-matches-visible rules; CSR ([../04-modules/09-csr-module.md](../04-modules/09-csr-module.md)) — partners, stories, galleries.
**Accept if:** publish gates block "admin-author" and empty metas (tests green); GBP sync populates stats with as-of; review-request invariant (one per move) tested; launch content set drafted in staging ([../06-content-seo/01-content-strategy.md](../06-content-seo/01-content-strategy.md) §6 progress visible in calendar).

## Milestone 5 — Client Portal + Billing (week 11–13)
**Scope:** Portal ([../04-modules/04-client-portal.md](../04-modules/04-client-portal.md)) — auth/invite, dashboard, moves timeline, checklists, documents (signed URLs), threads, notifications, invoices view; Realtime ([../03-technical-specs/11-realtime.md](../03-technical-specs/11-realtime.md)) — native islands everywhere + Ably push with auto-fallback; Billing ([../04-modules/12-billing-finance.md](../04-modules/12-billing-finance.md)) — quotes/invoices/payments, numbering locks, PDFs, GST classes.
**Accept if:** tenant-isolation suite 100% green; realtime failover drill passes (Ably disabled → poll takes over < 30s); invoice numbering concurrency test green; quote→accept→invoice→portal-visible chain e2e.

## Milestone 6 — i18n + AI + launch hardening (week 14–16)
**Scope:** I18n ([../04-modules/11-multilingual.md](../04-modules/11-multilingual.md)) — locale paths, detection, switcher, hreflang, RTL, translation queue; AI gateway ([../08-ai-system/01-ai-architecture.md](../08-ai-system/01-ai-architecture.md)) — providers (TokenRouter/OpenRouter + z-ai/glm-5.3-free), budgets, breakers, prompts; SEO final pass (hreflang sets, llms.txt, feeds); analytics stack (GTM, GA4, Consent Mode, server events); monitoring final (Pulse cards, Sentry releases, status page); content: wave-1 cities + launch set published; GBP fixed ([../07-marketing-trust/01-google-ecosystem.md](../07-marketing-trust/01-google-ecosystem.md) §3.1).
**Accept if:** /ja/ page renders correct lang/dir + hreflang set; translation pipeline produces review-queued machine drafts (never auto-published); AI kill-switch degrades gracefully (tested); consent gate verified (no tags pre-consent); launch checklist ([../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md) §2 gates 1–8) fully green.

## Milestone 7 — Launch + stabilization (week 17–18, then ongoing)
**Actions:** production deploy per runbook; post-deploy 5-minute gate; GSC sitemap submit; UptimeRobot live checks; ops rhythm starts ([../07-marketing-trust/04-growth-roadmap.md](../07-marketing-trust/04-growth-roadmap.md) weekly cadence); 2-week hypercare (daily digest review, fast-fix cycle); first quarterly resilience drill scheduled ([../03-technical-specs/12-monitoring.md](../03-technical-specs/12-monitoring.md) §8).
**Accept if:** first organic leads with sources tagged; zero SEV-1 in week 1; CWV p75 within budgets on real traffic; review engine triggers on first completed move.

## Dependency spine
```
M0 ─→ M1 ─→ M2 ─→ M3 ─→ M4 ─→ M5 ─→ M6 ─→ M7
     CMS     content intake  social  portal  i18n/AI  launch
```
(Team can parallelize content drafting from M1 onward — copy templates [../06-content-seo/06-copy-templates.md](../06-content-seo/06-copy-templates.md) exist precisely so writers never wait on engineering.)

## Out of scope at launch (tracked, not built)
Mobile app (contract only — [../04-modules/13-mobile-readiness.md](../04-modules/13-mobile-readiness.md)), Reverb VPS, Typesense Cloud, webhooks-out, HR leave/appraisal internals, site AI assistant, venture subdomains ([../09-delivery/02-future-scaling.md](02-future-scaling.md)).

---

Related: [02-future-scaling.md](02-future-scaling.md) · [CHANGELOG.md](CHANGELOG.md) · [../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md) · [../03-technical-specs/06-hosting-deployment.md](../03-technical-specs/06-hosting-deployment.md) · [../07-marketing-trust/04-growth-roadmap.md](../07-marketing-trust/04-growth-roadmap.md)
