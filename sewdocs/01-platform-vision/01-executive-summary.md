# 01 — Executive Summary

**SEWA HOSPITALITY SERVICES PVT. LTD. — Platform Blueprint v1.0**

---

## 1. What we are building

A single, commercial-grade web platform for Sewa Hospitality that serves **five audiences at once**:

1. **Corporate clients (HR & Global Mobility teams)** — evaluating and hiring Sewa for relocation, immigration, housing, and fleet services in India.
2. **Expatriate employees & families** — relocating into India, needing orientation, housing, schooling, visas, and settling-in support.
3. **International client segments** — Korean, Japanese, Turkish, Saudi/Arabic, and other foreign-nationality clients who must be identified, addressed, and served in their own language automatically.
4. **Sewa's own team** — admin, HR, operations, finance, and leadership running the business through a single admin panel and client portal.
5. **Search engines, AI engines, and LLMs** — Google, Bing, ChatGPT, Gemini, Perplexity, and every future AI assistant that people will use to ask about relocation services in India.

The platform is a **modular monolith** built on **Laravel 13** with a fully managed content core, a REST API that makes it **mobile-app-ready from day one**, layered realtime capability, and AI woven through as a configurable utility — never as a hard dependency.

## 2. Why this platform — the strategic case

The reference platform (Formula Group, formulaindia.com) proves the market: a full-stack relocation company in India serving Fortune-level clients. Our teardown (Section 02) shows exactly what that platform does, page by page, API call by API call. It is a strong business with a technically aging platform.

**Sewa's opportunity is to match the scope — and beat the execution.** The reference platform's stack is Next.js 11 (2021-era) + WordPress 6.8 + a CodeIgniter portal, three separate systems with three separate designs, glued together by a bare REST API. Its weaknesses are concrete and enumerable:

| Reference weakness (from teardown) | Sewa's answer |
|---|---|
| Three systems (Next.js + WordPress + CodeIgniter) | One Laravel 13 modular monolith — one design system, one auth, one deployment |
| Content hardcoded into a JS app; changes need a rebuild | Full CMS: every banner, CTA, counter, testimonial, and service block is editable |
| 500-error page in production (`wellbeing-support`) | Error locks: circuit breakers, health checks, rollback plan, full QA gates |
| No sitemap.xml or robots.txt in production | Sitemap + robots generated automatically, pinged to Search Console |
| Blog author is "admin" everywhere | Real authorship: team members as named authors with profile pages |
| Placeholder meta values on news posts ("metatitle", "keyword") | Meta templates enforced per page type; validation blocks empty placeholders |
| Double H1 on every blog post | Single-H1 rule enforced in templates + automated QA check |
| One post accidentally set to noindex | Noindex decisions surfaced in admin as a visible state, never accidental |
| Newsletter form goes nowhere (action="#") | Working newsletter with double opt-in, queue-driven, deliverability-verified |
| News section with only 3 items | Unified blog + news editorial system with calendar and duties |
| Accordion content duplicated in DOM (desktop + mobile copies) | Single-source components (Livewire/Alpine islands) — no duplication |
| Client portal is a separate CodeIgniter app | First-class client portal inside the platform, with documents, chat, and tracking |
| No multilingual presence at all | Auto-detect → serve Korean/Japanese/Turkish/Arabic (RTL) and more |
| No AEO/LLM optimization | Answer-first content, schema graph, llms.txt, FAQ blocks — built for AI citations |
| 11 reviews, 4.3★ on Google | Review-growth engine: post-service review requests, GBP focus, review widgets |
| No i18n URLs / hreflang | Proper hreflang alternates for every localized page |

That table is the thesis of this entire suite: **nothing about the reference is ignored, and everything is improved.**

## 3. Platform pillars

### Pillar 1 — One platform, properly divided
Fourteen modules (Section 04), each with its own purpose, data model, permissions, screens, error handling, and tests. Divides are real module boundaries in code (service classes, policies, event listeners per module), not just folder names. See [04-modules/00-module-system.md](../04-modules/00-module-system.md).

### Pillar 2 — Commercial-grade reliability ("error locks")
Every external call (AI, Ably, email, Google APIs) goes through a circuit breaker with fallback. Every write path uses DB transactions. Public forms are rate-limited and Turnstile-protected. Failures degrade gracefully — a page never breaks because a third party did. See [03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md).

### Pillar 3 — Native-first, with an escape hatch
The rule: **our own native capability always works, even if every third party disappears.** Realtime is Livewire islands + wire:poll natively; Ably accelerates it; polling/SSE is the fallback. Email has Brevo/Resend plus a native SMTP path. The platform must never be blocked by an external free tier. See [03-technical-specs/11-realtime.md](../03-technical-specs/11-realtime.md).

### Pillar 4 — AI as a configurable utility
The first-party Laravel AI SDK abstracts providers. TokenRouter/OpenRouter with `z-ai/glm-5.3-free` is a config entry, not code. When a better or cheaper model appears, we swap a config value. AI features always have non-AI fallbacks (cached translations, human review queues). See [08-ai-system/01-ai-architecture.md](../08-ai-system/01-ai-architecture.md).

### Pillar 5 — Built to be found (by humans and by AIs)
Technical SEO fixed from day one (sitemap, robots, canonical, hreflang, single H1, schema graph), an all-India city content program (Section 06), and an AEO layer that makes Sewa the answer AI engines cite. See [06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md) and [06-content-seo/05-aeo-llm-presence.md](../06-content-seo/05-aeo-llm-presence.md).

### Pillar 6 — Future-proof by contract
Mobile app readiness is an API contract (Section 04/13), not an afterthought: Sanctum tokens, versioned `/v1` endpoints, event names reserved for push. VPS migration is a documented trigger-based path (Reverb, Redis, object storage) — not a rewrite. See [09-delivery/02-future-scaling.md](../09-delivery/02-future-scaling.md).

## 4. Scope — the complete business surface

Sewa's service scope matches the reference exactly (11 services across employee mobility and business mobility), documented in [03-service-catalog.md](03-service-catalog.md):

- **Employee Mobility:** Relocation (orientation, home search, school search, tenancy management, departure program) · Immigration (inbound, outbound, ancillary — FRRO registration, visas, PAN) · Serviced Apartments · Moving Services (local/domestic/international, pet relocation, workplace moves) · Corporate Housing · Fleet Services (long-term, daily, self-drive).
- **Business Mobility:** Travel (IATA-grade) · Business Space (office, industrial land, warehouses, factory) · Recruitment (executive search, RPO, HR policies) · Interior Designing · Sanitization.

Plus everything the reference has and more: careers/HR, CSR program, testimonials, city content, multilingual serving, billing/finance module, and a client portal that outclasses the reference's.

## 5. What "much better" means — measurable targets

| Dimension | Reference baseline | Sewa target |
|---|---|---|
| Systems | 3 (Next.js + WP + CodeIgniter) | 1 |
| CMS | None (hardcoded content) | Full, non-technical-editor friendly |
| Page weight | ~918 KB CSS alone in 8 files; 2.5 MB hero PNG | Budget: < 150 KB CSS/JS per page, WebP/AVIF heroes |
| Sitemap/robots | Missing | Auto-generated, Search Console verified |
| H1 per page | Often 2 | Exactly 1 |
| Languages | 1 (EN) | EN + ko/ja/tr/ar (+ extensible) with RTL |
| AEO | None | Schema graph, llms.txt, FAQ, answer-first |
| Google reviews | 11 (4.3★) | 100+ (4.7★+) in 12 months |
| Blog authors | "admin" | Named team authors |
| Mobile app | Separate legacy app | Same platform, API-first |
| Admin | None found (content via API edits) | Full admin panel, role-based |
| Error handling | 500 page live in production | Zero-500 policy, QA gates, rollbacks |

## 6. What this documentation suite is

55 documents across 9 sections (plus this suite's README master index): vision & brand, complete reference teardown, technical specs (13), module specs (14), design system, content/SEO/AEO program, marketing & trust, AI system, and delivery. Together they are the **contract for the build**: every feature named in this suite has a spec, an owner module, an error-handling rule, and a test.

## 7. Document relationships

- **Section 01** defines *what* Sewa is.
- **Section 02** documents *everything the reference does* — the complete inventory, nothing ignored — so nothing is missed in the rebuild.
- **Sections 03–05** define *how* the platform is engineered and designed.
- **Section 04** defines each functional area in build-ready detail.
- **Sections 06–08** define the growth, content, and intelligence layers on top.
- **Section 09** sequences the build.

Every doc cross-links; the README is the master index.

---

Related: [02-brand-sewa-hospitality.md](02-brand-sewa-hospitality.md) · [03-service-catalog.md](03-service-catalog.md) · [04-subdomains-ventures.md](04-subdomains-ventures.md) · Full teardown: [../02-formula-reference/01-site-map-and-pages.md](../02-formula-reference/01-site-map-and-pages.md)
