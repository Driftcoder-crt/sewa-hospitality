# 02 — Services Module

**The 11-service catalog (employee + business mobility + immigration sub-tree) as a managed, SEO-complete, locale-ready content tree — the money pages of the platform.**

---

## 1. Purpose
Model the entire service catalog (defined in [../01-platform-vision/03-service-catalog.md](../01-platform-vision/03-service-catalog.md)) as CMS-editable entities with a self-referencing family tree, per-service content blocks, FAQs, related-service linking, and lead-tagging — so every service page is both a marketing surface and a lead intake point.

## 2. Data model
`services` (self-referencing parent/family), `service_related` pivot, `service_offices` coverage pivot, `city_services` (Cities module) — full columns in [../03-technical-specs/03-database-schema.md](../03-technical-specs/03-database-schema.md) §3. Key behaviors:
- **Family tree:** employee-mobility / business-mobility hubs + standalone `immigration` sub-tree (3 children) — mirrors the proven reference IA.
- **lead_tag** (e.g. `housing.corporate`, `immigration.inbound`) — every form submission from the page carries it into Leads.
- **Content:** uses the same CMS block library (hero, accordion, faq, cards_grid, cta_band, rich_text) — editors compose, no engineering.
- **i18n:** locale groups; publishing a service in ja/ko/tr/ar is a translation task, not a rebuild.

## 3. Public surface

| Route | Page spec |
|---|---|
| `/services` | Hub: intro, 11 service cards (icon, image, excerpt, arrow-link), CTA band |
| `/services/{family}` | Family page: intro + child cards + family CTA |
| `/services/{family}/{service}` | Leaf: hero, intro, accordion blocks (scope), optional city coverage strip, named consultant, FAQ (schema), service-specific form |
| `/services/immigration` + 3 children | Sub-tree per reference (inbound/outbound/ancillary) with compliance timeline + checklist generator |

Per-service page additions over the reference: named consultant (HR module), city coverage links (Cities module), related services, visible reviews for that service (Testimonials module), answer-first FAQ for AEO ([../06-content-seo/05-aeo-llm-presence.md](../06-content-seo/05-aeo-llm-presence.md)).

**On-domain rule:** Serviced Apartments and Fleet link to Sewa's own `/housing` and `/fleet` surfaces — never out to sister sites (reference loses the visitor; we keep them — ADR-010 in [../03-technical-specs/02-architecture.md](../03-technical-specs/02-architecture.md)).

## 4. Admin surface
1. **Services tree** — visual tree (families, children, ordering drag), status chips, per-locale completeness badges.
2. **Service editor** — same live block canvas as CMS pages ([01-cms.md](01-cms.md) §4) + service-specific drawer: family/parent, icon picker, lead_tag, coverage offices, related services (searchable picker), SEO panel, locale selector, FAQ repeater (renders FAQPage schema).
3. **Reordering** — drag within sibling sets; ordering affects hub card order.
4. **Coverage editor** — mark service availability per city (drives "Available in Pune" strips + city page cross-links).

Permissions: `editor` (content), `admin` (publish + SEO + coverage).

## 5. Behavior & rules
- Service cannot be deleted if leads reference it; archive instead (page 301s to family hub via redirect manager).
- Publish requires: meta, intro, hero media (with alt), ≥1 content block — enforced like CMS SEO rules.
- Family pages auto-compose from children (never manually duplicated text — the reference duplicates WHO WE ARE copy across home + about; our blocks reference single sources).
- URL slugs locked to the catalog doc; slug change = same 301 flow as CMS.
- `lead_tag` changes require admin (analytics continuity).

## 6. Error handling
- A service page with missing media → publish probe blocks (no broken heroes live).
- Coverage-strip queries cached (60s tag) — no N+1 on hub pages.
- If a locale variant is machine-pending, the page still serves EN + hreflang only lists published locales (no half-published states).

## 7. Events & integrations
- `ServicePublished/Updated` → sitemap, cache tags, search upsert, Pulse.
- Blocks consume: Testimonials (per-service reviews), Cities (coverage), HR (consultant profile), Leads (form_embed with pre-set lead_tag).
- Emits to Analytics: `view_service` server event ([../07-marketing-trust/02-analytics-plan.md](../07-marketing-trust/02-analytics-plan.md)).

## 8. Tests
- Tree integrity (orphan parent, cycle prevention); publish-gate rules; hub auto-composition; coverage strip renders only published cities; FAQ schema output matches FAQPage; per-service form carries lead_tag end-to-end into Leads; locale fallback; slug redirect flow.

---

Related: [../01-platform-vision/03-service-catalog.md](../01-platform-vision/03-service-catalog.md) (source of truth) · [01-cms.md](01-cms.md) · [03-leads-crm.md](03-leads-crm.md) · [10-cities-content.md](10-cities-content.md) · [../06-content-seo/06-copy-templates.md](../06-content-seo/06-copy-templates.md)
