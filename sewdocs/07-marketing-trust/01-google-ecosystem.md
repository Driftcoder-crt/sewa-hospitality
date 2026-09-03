# 01 — Google Ecosystem

**The full Google stack for Sewa: Search Console, GA4 through Tag Manager with Consent Mode, Google Business Profile growth (from 4.3★/11 reviews), Google Ads structure, and content-tooling integration — a managed, measurable presence the reference hardcodes and neglects.**

---

## 1. Search Console
- Verify at launch (DNS TXT); submit sitemap index ([06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md) §3); enable all property types (domain property covers subdomains).
- Cadence: editor reviews monthly (coverage errors, queries/win-loss, CWV); anonymize-if-shared rule for reports.
- Alerts: sitemap ping on publish; manual actions → SEV-1 ([../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) §6).
- Bing Webmaster (import from GSC — one click) for the non-Google slice.

## 2. Tag Manager + GA4 (consent-first)
- **All tags via GTM** — the reference hardcodes gtag/Clarity/FB pixel into every page: unmaintainable. GTM container per environment; publishing gated by consent.
- **Consent Mode v2:** cookie banner (en/hi/ja/ko/tr/ar copies) — analytics/ad defaults denied; consent-gated GA4 + Clarity-equivalent ([../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) §1.4). No FB pixel until a campaign exists; then consent-gated too.
- GA4 events (full map: [02-analytics-plan.md](02-analytics-plan.md)).

## 3. Google Business Profile (the 4.3★/11-review asset)
Baseline: **Sewa Hospitality Services Pvt. Ltd.** — "Condominium rental agency in Haryana" — 4.3★, 11 reviews, address MS0228, 2nd Floor, DT Mega Mall, A Block, DLF Phase 1, Gurugram ([../01-platform-vision/02-brand-sewa-hospitality.md](../01-platform-vision/02-brand-sewa-hospitality.md) §1).

### 3.1 Immediate fixes (week 1)
| Action | Detail |
|---|---|
| Category correction | Primary: *Corporate housing agency / Relocation service* (final taxonomy per GBP); keep "Condominium rental agency" as secondary (history) |
| Services list | Relocation Service, Serviced Apartment, Corporate Housing, Immigration Assistance, Vehicle Rental, Moving Company — with descriptions |
| NAP lock | Match site exactly (phone +91 98732 55531, address, website → sewahospitality.com); consistent across all directories |
| Photos | logo/cover + office exterior/interior + 10 service-context photos (quarterly refresh); "See photos" is a top GBP action — feed it |
| Q&A seeded | Real FAQ (parking, hours, languages spoken: English, Hindi, 日本語, 한국어, العربية, Türkçe) |
| Posts | weekly GBP post tied to city content ([../06-content-seo/03-city-content-program.md](../06-content-seo/03-city-content-program.md)) |

### 3.2 The review engine (target: 4.7★+, 100+ reviews in 12 months)
- Automated post-move review request ([../04-modules/08-testimonials-reviews.md](../04-modules/08-testimonials-reviews.md) §4.3): every completed move = one request + one polite follow-up, ever.
- Ask at peak-happiness moments only (journey stage `settling→complete`); include the Google short-link + private-feedback fallback.
- Service recovery: ≤3★ → 4h SLA outreach (ops alert) before responding publicly; responses written in brand voice, offer resolution + channel.
- Every review answered (templated gratitude + specifics, personal where possible) — response rate itself is a ranking + trust factor.
- Review count/rating synced to site stats ([../03-technical-specs/12-monitoring.md](../03-technical-specs/12-monitoring.md) — honest "as of" display).

### 3.3 Multi-office future
When offices beyond Gurugram open: one GBP per real location (each with LocalBusiness schema on the site — [../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md) §4), same NAP discipline, review engine runs per location. (The reference has 9 offices and one brand presence — we start right-sized and scale correctly.)

## 4. Google Ads (structure, ready when budget is)
| Layer | Setup |
|---|---|
| Brand campaign | exact-match "Sewa Hospitality" variants + typos; sitelinks (services, portal, careers, reviews) |
| Service campaigns | per-service ad groups (Relocation / Corporate housing / Serviced apartments / Fleet / Immigration) → matching service pages; ad copy = SLA + city proof + CTA ("Talk to a consultant — 2h response") |
| City campaigns | wave-1 city + service intent ("serviced apartments Gurugram") → city/housing pages |
| Competitor-adjacent | careful, brand-policy-compliant conquesting — no trademark misuse |
| Audiences | corporate mobility job titles (HR/Mobility/TA), Japanese/Korean language targeting for ja/ko landing pages |
| Negative lists | jobs/Free/Careers-salary queries, "jobs in relocation" etc. |
| Landing pages | real pages with form + SLA (never bare home); UTM discipline ([02-analytics-plan.md](02-analytics-plan.md) §3); conversion = lead (not click) |
| Budget posture | start small (brand + one service cluster), scale on measured CPL by service; weekly automated check + monthly review |
Remarketing (consent-gated, privacy-safe lists) documented, off until volume justifies.

## 5. Other Google surfaces
- **Google Maps embeds:** click-to-load facades only ([../04-modules/03-leads-crm.md](../04-modules/03-leads-crm.md) office tabs) — fast + consent-friendly; place pins accurate per office.
- **Google reviews widget:** synced cards on-site ([../04-modules/08-testimonials-reviews.md](../04-modules/08-testimonials-reviews.md) §3) with source links — trust objects, not just a badge.
- **Merchant Center / product listings:** N/A for services — deliberately out of scope.
- **YouTube:** channel as social proof surface ([../07-marketing-trust/03-trust-authority.md](../07-marketing-trust/03-trust-authority.md)); brand video hosted there, embedded via facade ([../05-design-system/03-ux-interactions.md](../05-design-system/03-ux-interactions.md) §3).

## 6. Governance
- All tags/properties owned by the company account (not personal) — access list documented in ops.
- Monthly "Google stack" review: GSC + GA4 + GBP + Ads (when active) — one agenda, one owner (editor role), logged in CHANGELOG.

---

Related: [02-analytics-plan.md](02-analytics-plan.md) · [03-trust-authority.md](03-trust-authority.md) · [04-growth-roadmap.md](04-growth-roadmap.md) · [../04-modules/08-testimonials-reviews.md](../04-modules/08-testimonials-reviews.md) · [../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md) · Reference baseline: [../02-formula-reference/05-seo-content-analysis.md](../02-formula-reference/05-seo-content-analysis.md) §5
