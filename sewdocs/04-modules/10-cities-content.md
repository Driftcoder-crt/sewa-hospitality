# 10 — Cities & Housing Module

**The all-India city program (the reference's 2021 city-guide series, systematized and grown) plus the on-domain housing inventory (serviced apartments + corporate housing) that keeps visitors on sewahospitality.com instead of shipping them to sister sites.**

---

## 1. Purpose
Own the city-level search demand — "relocation services Pune", "serviced apartments Gurugram", "schools in Bengaluru for expats" — with structured, updateable city pages, and present Sewa's housing inventory with a verified standard. This is the SEO growth engine (strategy: [../06-content-seo/03-city-content-program.md](../06-content-seo/03-city-content-program.md)).

## 2. Data model
`cities`, `city_services`, `housing_units` ([../03-technical-specs/03-database-schema.md](../03-technical-specs/03-database-schema.md) §3) — `housing_units.tier` (essential/professional/executive), `verified_at/verified_by` for the Sewa-Verified standard.

## 3. Public surface

| Route | Spec |
|---|---|
| /cities | hub: map-style grid of covered cities (hub cities first), each with 1-line relocation snapshot + links |
| /cities/{slug} | city page (the money template): hero → "Relocating to {City}" intro → service coverage strip (linked) → housing inventory cards (tier, from-rate, amenities) → neighborhoods guide → schools/healthcare/transport summary (answer-first blocks) → FRRO/immigration local notes → testimonials for the city → FAQ (schema) → CTA |
| /housing | national inventory browser: filters (city, type, tier, bedrooms, price band) — Scout-driven |
| /housing/{unit} | unit detail: gallery, amenities, included services, from-rate, availability enquiry (feeds Leads with `housing.*` tag) |

**On-domain rule (ADR-010):** housing browses on Sewa's domain. The reference deep-links serviced apartments to a sister site — losing the visitor, the session, and the SEO. Sewa's inventory UI lives here; future venture split is a documented 301 path ([../01-platform-vision/04-subdomains-ventures.md](../01-platform-vision/04-subdomains-ventures.md) §4).

## 4. Admin surface
1. **Cities** — CRUD with the block canvas (same block library), coverage editor (services × city with local notes: "Fleet: 40 vehicles"), SEO drawer, locale groups, status; ordering (hub flag).
2. **Housing units** — CRUD: city, type, tier, name/locality/area, bedrooms, amenities checklist, rate (from_, unit night/month), photo gallery, status; **verification action** (sets verified_at/by — drives the "Sewa Verified" badge and its published checklist).
3. **Verification queue** — units nearing re-verification age (6 months) listed; one-click re-verify (new date) or expire (badge drops).
4. **City content backlog** — links to the content program doc's coverage matrix; zero-result search queries per city surface here as editorial tickets ([../03-technical-specs/08-search.md](../03-technical-specs/08-search.md) §3).

## 5. Behavior & rules
- **Honest rates:** "from ₹X/night" ranges only, with "rates vary by season/term — request exact quote" — no fake precision, no bait (brand voice rule).
- **Verified standard published:** /housing/verified page lists the inspection checklist behind the badge (transparency play the reference only implies).
- **Coverage truth:** a service listed on a city page must actually operate there (city_services rows) — no optimistic coverage.
- **Hub cities** get deeper content first (Gurugram, Mumbai, Bengaluru, Delhi, Pune, Hyderabad, Chennai — matching both business reality and the reference's city guide series).
- **City page SEO:** template metas per the technical doc; city pages interlink bidirectionally with services ([02-services-module.md](02-services-module.md) §3).

## 6. Error handling
- Housing filter with zero results → friendly empty state with city-level alternative suggestions (never dead-end).
- Rate staleness: any unit's rate older than 90 days → badge "confirm current rate" until updated.
- City page with unpublished locale → serves EN + lists only published locales in hreflang.

## 7. Events & integrations
`CityPublished` → sitemap/search/cache; `HousingUnitVerified` → cache purge for its city's grid. Integrations: Leads (availability enquiry with unit ref), Testimonials (per-city block), Services (coverage strip), Analytics (`view_city`, `view_housing`, `filter_housing` events).

## 8. Tests
Filter matrix (city×type×tier×bedrooms×price) incl. zero-result state; verified badge logic incl. expiry; coverage strip renders only active city_services; rate staleness flag; enquiry carries unit ref into Leads; publish gates; locale fallback.

---

Related: [00-module-system.md](00-module-system.md) · [02-services-module.md](02-services-module.md) · [../06-content-seo/03-city-content-program.md](../06-content-seo/03-city-content-program.md) (the editorial engine) · [../03-technical-specs/08-search.md](../03-technical-specs/08-search.md) · Reference: [../02-formula-reference/05-seo-content-analysis.md](../02-formula-reference/05-seo-content-analysis.md) §4.1
