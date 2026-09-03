# CHANGELOG — Sewa Hospitality Platform Documentation Suite

All notable changes to this documentation suite are logged here. The suite is the contract between docs and build: if the build deviates, update the doc or fix the build.

Format: `[date] — [section] — change — reason`.

---

## 2026-09-01 — v1.0 — Initial complete suite

**Sections created (55 documents + README index):**

- **README.md** — master index, reading order, locked-decisions register (Laravel 13.x/PHP ^8.3, Livewire 4, Tailwind 4.3, Vite local builds, MySQL, Sanctum, Spatie Permission+Media, Scout-DB, no Filament, Ably+native realtime, Resend/Brevo, Cloudflare stack, sewahospitality.com + subdomains, TokenRouter/OpenRouter AI).
- **01 Platform Vision (4):** executive summary; Sewa brand identity (legal entity, Gurugram HQ, +91 98732 55531, GBP 4.3★/11 baseline); complete 11-service catalog mapped 1:1 from the reference platform (14 URLs incl. immigration sub-tree); subdomain/venture architecture.
- **02 Formula Reference (6):** complete site map & page inventory (all pages incl. 15 leaders, 9 offices, 7 NGOs, 24 testimonials, 6 jobs, 3 news items, 45 blog posts); components/interactions catalog (20 patterns); API & data layer teardown (every write endpoint, payloads, envelope defects); design token extraction (#DF1E26 system, fonts, breakpoints); SEO/content analysis (meta patterns, JSON-LD, taxonomy, tracking IDs); weaknesses→fixes matrix (A/B/C/D tables + 90-day quick wins).
- **03 Technical Specs (13):** stack & dependency policy (allowlist, exclusions with reasons); modular-monolith architecture + ADRs; complete database schema (all modules); versioned /v1 API spec; security/reliability doctrine (error locks: transactions, idempotency, breakers, degradation matrix); Hostinger hosting/deploy runbook (single-cron pattern, backups, rollback); queues/scheduling map; Scout DB search + Typesense path; media pipeline (conversions, alt-required, immutable-hash CDN); email system (catalog, fallback chain, DMARC); layered realtime (native→Ably→Reverb); monitoring (Pulse/Sentry/UptimeRobot/status page); testing & QA gates (money-path suites, 9 release gates).
- **04 Modules (14):** module system (rules, event catalog, permission matrix, build order); CMS (17-block library); Services; Leads/CRM; Client Portal; custom Admin Panel (no Filament); HR/Employees/Careers; Blog/News; Testimonials/Reviews; CSR; Cities/Housing; Multilingual (ko/ja/tr/ar RTL pipeline); Billing; Mobile readiness (frozen v1 event contract).
- **05 Design System (3):** Sewa brand guidelines (teal/saffron/sand palette, Sora+Inter, iconography, photography rules, motion); UI component library (all components, RTL-safe, state machines); UX/interaction spec (journeys, patterns, CWV budgets, WCAG 2.2 AA, honesty map).
- **06 Content & SEO (6):** content strategy (personas, pillars, workflow, KPIs); technical SEO (per-page-type head/JSON-LD, sitemap/robots, hreflang, defect-fix traceability); all-India city program (25-city waves, demand-research method); multilingual content strategy (market priorities, registers, locale SEO); AEO/LLM presence (3-layer strategy, llms.txt, probe panel); copy templates (12 page types).
- **07 Marketing & Trust (4):** Google ecosystem (GSC/GTM-GA4-consent/GBP growth 4.7★+100 target/Ads structure); analytics plan (event map, server-confirmed conversions, funnels, dashboards); trust & authority (credentials roadmap, transparency surfaces, entity consistency, never-do list); 12-month growth roadmap (L/T/A phases, budget posture, weekly rhythm).
- **08 AI System (2):** AI architecture (Laravel AI SDK gateway, TokenRouter/OpenRouter + z-ai/glm-5.3-free as config, budgets/breakers/PII guards); AI use cases (7 launch features with human gates + fallbacks, Phase-2 candidates, usage policy).
- **09 Delivery (3):** build roadmap M0–M7 with acceptance criteria; future scaling (trigger table, VPS exit, component upgrades, constants); this changelog.

**Key locked decisions recorded:** Hostinger shared hosting honored throughout (no daemons — DB queues, single cron); native-first realtime (nothing external can block a feature); no admin frameworks (Filament excluded); Vite built locally; honest-claims policy platform-wide (schema matches visible content; no unsourced ratings); mobile-app contract frozen at v1.

---

## 2026-09-01 — v1.0.1 — Full-suite audit pass

Deep audit of all 56 documents (report: [../AUDIT-REPORT.md](../AUDIT-REPORT.md)) — **verdict: PASS with 11 defects found and fixed in place:**
- Removed drafting artifacts from stack doc (§2.5 beberlei fragment) and robots.txt block (draft notes inside code fence).
- Fixed command typo `slam:calculate` → `sla:calculate` (queues + leads docs).
- Unified module count to **14** everywhere (was "13" in module-system §1).
- Realigned module-system build-order spine to the roadmap's M0–M7 milestones (was an older M0–M6 sequence).
- Locked phone format rule: display `+91 98732 55531` / E.164 `+919873255531` (brand doc §9).
- Added `sentry/sentry-laravel` to the composer allowlist (was required by monitoring/security docs but missing).
- Corrected stale document counts (44 → 55 docs + README) in README, exec summary, CHANGELOG.
- Post-fix state: 758/758 internal links resolve, 0 orphans, 0 placeholders, 0 cross-doc contradictions on brand/NAP/stack/KPI/SLA facts.

---

## 2026-09-01 — v1.1 — Luxury UI/UX deep-audit cycle

**New section-05 documents (4):**
- **06-reference-sites-analysis.md** — programmatic design audit of 10 reference sites (wild-ag.ch, nomu.store, kortrijkxpo.com, lesaintgeorges.ch, hgd-media.de, orionix.framer.website, surinder.design, brigadeoverland.com, tajhotels.com, dlf.in): extracted tokens, palettes, typography, libraries, overlays, component counts, and mobile-viewport behavior (390×844 probes). 12 cross-site rules + explicit reject list (no gold-as-luxury, no scroll-hijacking, no modal-on-load, no undersized tap targets, no pure white/black).
- **04-theme-engine.md** — centralized smart theme control: 4-layer architecture (primitives in oklch → semantic paper/ink pair tokens → per-section `data-theme` light/dark/brand/deep → admin Theme panel with presets, brand customizer, preview & versioned publish). Dark bg → light text is structural (pair inversion), auto-suggestion on media pick, AA contrast enforced at publish.
- **05-section-block-library.md** — 47 premade sections/blocks (6 categories) replacing the 17-block v1 set: +8 promotional/conversion blocks (offer banner, countdown, exit-intent modal, sticky CTA bar, newsletter capture…), +bento/marquee/story patterns, module-fed dynamic blocks; presets, per-locale variants, per-block weight budgets.
- **07-compliance-standards.md** — the binding quality bar: Lighthouse-class budgets (perf ≥90, a11y 100, LCP ≤2.0s, INP ≤200ms, CLS ≤0.05, DOM ≤1500, third-party-0 pre-consent), Google Search Essentials mapping, MDN/Firefox Baseline + progressive-enhancement + logical-property + oklch-fallback rules, cross-browser CI incl. Firefox/WebKit, mobile-first contract (44px targets, 16–18px body, zero-overflow gate).

**Updated docs:** brand guidelines (palette v2 — warm ink #26201A, bronze accent #C9974C, never pure white/black; typography v2 — Fraunces Variable display + Inter body, decided, no longer deferred); ui-components (atomic-vs-section relationship); ux-interactions (mobile-first contract binding + budgets pointer); CMS module (block library cross-ref to the 47-block catalog); README section 05 index.

---

## 2026-09-01 — v1.1.1 — Build-driver prompt added

- **New: [09-delivery/03-build-prompt.md](03-build-prompt.md)** — the master prompt + operating procedure for driving the build milestone-by-milestone with a GLM model on z.ai web chat (paste-first-message, per-milestone attach lists to respect web-chat context, driver commands, drift-correction line). README index updated.

---

## Maintenance rules
1. Every spec change → new entry here (date, section, change, reason).
2. Every build milestone acceptance → note in [01-build-roadmap.md](01-build-roadmap.md).
3. Quarterly scaling review decisions → logged here (see [02-future-scaling.md](02-future-scaling.md) §8).
4. Incident/SEV post-mortems affecting specs → entry + updated doc.
