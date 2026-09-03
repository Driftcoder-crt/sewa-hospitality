# 02 — Brand: Sewa Hospitality

**SEWA HOSPITALITY SERVICES PVT. LTD. — Brand Identity & Positioning**

> "Sewa" (सेवा) means *service* — in the sense of selfless, honorable care. The brand promise is in the name: hospitality as an act of genuine care, delivered with corporate-grade reliability.

---

## 1. Legal & contact identity (single source of truth)

These exact values are used platform-wide — in schema.org markup, the footer, contact pages, email templates, the admin panel, and the API. They must never drift.

| Field | Value |
|---|---|
| Legal name | SEWA HOSPITALITY SERVICES PVT. LTD. |
| Short brand name | Sewa Hospitality (never "Sewa" alone in public copy; "Sewa Group" reserved for future venture architecture, see [04-subdomains-ventures.md](04-subdomains-ventures.md)) |
| Address | MS0228, 2nd Floor, DT Mega Mall, A Block, DLF Phase 1, Gurugram, Haryana 122002, India |
| Phone | +91 98732 55531 |
| Primary email | hello@sewahospitality.com (public) · support@sewahospitality.com (portal) · careers@sewahospitality.com (HR) |
| Domain | sewahospitality.com |
| Google Business Profile | Currently: 4.3★, 11 reviews, category "Condominium rental agency in Haryana" — see [Category correction](#5-google-business-profile-strategy) below |
| Registration city | Gurugram, Haryana, India |

**NAP consistency rule:** Name–Address–Phone must be byte-identical everywhere on the internet (site, GBP, directories, socials). This is a ranking factor and a trust factor. The admin panel holds these values in one `Organization` setting record; templates render from it — never typed twice.

## 2. Positioning

### Statement
> Sewa Hospitality is India's human-first corporate relocation and mobility partner — one accountable team for housing, immigration, moving, and fleet services, powered by transparent technology and measurable care.

### Positioning logic
- The reference company sells a "formula" (systematized process). Sewa sells **care with systems behind it** — "human-centric" made literal in the name. The emotional high ground ("hospitality", "sewa") plus the mechanical proof (transparent tracking, dashboards, SLAs).
- **Audiences:** (a) corporate HR / global mobility decision-makers who buy Sewa; (b) the relocating employee who *experiences* Sewa. Winning both is the wedge — procurement buys the SLA, the family remembers the care, the family tells the HR team, the HR team renews.
- **Proof pillars** (used across every page): response-time SLAs (e.g. "2-hour response"), verified inventory (housing/fleet), real named humans (consultant profiles), transparent pricing, and review velocity.

### Tagline system
| Use | Line |
|---|---|
| Primary tagline | **"Care, delivered."** |
| Mobility context | "Relocation, the Sewa way — human first, systems behind it." |
| CTA micro-copy | "Talk to a Sewa consultant" / "Get your move plan" |
| SEO descriptor | "Corporate relocation, housing & mobility services in India" |

## 3. Brand voice

| Attribute | Rule | Example |
|---|---|---|
| Warm-precise | Care language + concrete facts in the same sentence | "Your consultant meets you at the airport — and your move plan is already in your dashboard." |
| Plain English | No jargon without a plain-language expansion; never "synergies" | "FRRO registration (your police/immigration check-in) — we book the appointment for you." |
| Human names | Named consultants, named authors, named reviewers | "Reviewed by Priya, Relocation Lead — Gurugram" |
| Confident, never boastful | Numbers with sources, no "best in India" without rating proof | "98% of moves rated 5★ by clients (internal CSAT, 2026)" |
| Multilingual register | Formal politeness for ja/ko/ar/tr; review every auto-translated string | See [../06-content-seo/04-multilingual-content.md](../06-content-seo/04-multilingual-content.md) |

Voice is enforced in code review for system copy, and in editorial review for content (see [../06-content-seo/01-content-strategy.md](../06-content-seo/01-content-strategy.md)).

## 4. Visual identity

Full specifications live in [../05-design-system/01-brand-guidelines.md](../05-design-system/01-brand-guidelines.md). Summary decisions here because they define the brand:

- **Logo:** Wordmark-led ("SEWA HOSPITALITY") with an optional sewa-knot/hands monogram. Must render at 16px favicon and 2000px billboard. Variants: primary (dark on light), inverse (light on dark), mono.
- **Palette:** Warm hospitality base + one confident accent. Distinct from the reference's red-on-blush — Sewa's palette must not be mistaken for Formula Group. Final tokens in the design-system doc.
- **Typography:** One humanist sans for everything (better than the reference's 3-font load), with display weight for headlines.
- **Photography rule:** Real people in real Indian contexts (families, consultants, homes, drivers) — never cold stock corridors.

## 5. Google Business Profile strategy

Baseline: **4.3★ with 11 reviews**, category "Condominium rental agency in Haryana". This is an asset to grow, not a constraint.

1. **Category correction (immediate):** Add/adjust primary category toward *Corporate housing agency / Relocation service / Serviced accommodation* (Google's taxonomy; keep "Condominium rental agency" as a secondary since it carries the 11 reviews' history). Add services: Relocation Service, Serviced Apartment, Corporate Housing, Immigration Assistance, Car Rental.
2. **Review engine:** Post-service review request (email + WhatsApp template, sent automatically when a move is marked complete in the CRM module). Target 4.7★+/100 reviews in 12 months. Every review is also syndicated as a testimonial entity (module [../04-modules/08-testimonials-reviews.md](../04-modules/08-testimonials-reviews.md)).
3. **Photos/Map:** Weekly GBP posts tied to the city-content program; office photos tied to the real Gurugram location; Map link consistency.
4. **Ownership in admin:** GBP stats (rating, count, top categories) stored and displayed in the admin dashboard via scheduled sync — see [../07-marketing-trust/01-google-ecosystem.md](../07-marketing-trust/01-google-ecosystem.md).

## 6. Brand architecture

| Layer | Now | Later (see [04-subdomains-ventures.md](04-subdomains-ventures.md)) |
|---|---|---|
| Master brand | Sewa Hospitality | Sewa Group (holding) |
| Sub-brand naming | "Sewa Housing", "Sewa Fleet", "Sewa Homes" used **as service line names** under one site | If a line outgrows the site, promote to subdomain site with shared design tokens |

The reference runs 7 sister sites (housing, moving, apartments, car rental, travel, sanitization, Japan). Sewa launches as **one platform with strong service lines**, keeping all SEO equity on one domain — splitting later is a documented, reversible step.

## 7. Trust & authority identity

Sewa must *look* established from day one, honestly:
- Membership roadmap (EuRA, Worldwide ERC, IAM-type bodies) — see [../07-marketing-trust/03-trust-authority.md](../07-marketing-trust/03-trust-authority.md). Only badges actually held are displayed; aspirational ones are on the roadmap doc, never on the site.
- Named leadership (the reference shows 15 leaders with bios; Sewa shows real leadership with photos, bios, LinkedIn links — module [../04-modules/06-hr-employee-module.md](../04-modules/06-hr-employee-module.md) powers this).
- Transparency pages: "How we work", "Our response times", "Pricing approach", "Data & privacy" — the reference has only legal boilerplate; Sewa publishes readable versions.
- Real certifications when earned: ISO 27001 (data handling for corporate clients), IATA (travel line when licensed.

## 8. Brand assets checklist (for the design phase)

- [ ] Logo set: primary/inverse/mono + favicon + social avatars (Square 1080, LinkedIn banner)
- [ ] Palette tokens (Tailwind CSS 4 `@theme` block — see design system)
- [ ] Type scale + display font subsetting
- [ ] Photography art direction + shot list (consultants, families, homes, fleet, office)
- [ ] Iconography style (module icons for 11 services)
- [ ] Email header/footer templates (see [../03-technical-specs/10-email.md](../03-technical-specs/10-email.md))
- [ ] Wordmark translations (ja/ko/tr/ar — Arabic renders in Calligraphy; scripts checked by native reviewers)
- [ ] Naming: confirm handle availability @sewahospitality on LinkedIn, Instagram, X, YouTube, Facebook (see [../07-marketing-trust/02-analytics-plan.md](../07-marketing-trust/02-analytics-plan.md) for UTM/social config)

## 9. Brand constants in JSON (machine-readable, used by schema + templates)

```json
{
  "legalName": "SEWA HOSPITALITY SERVICES PVT. LTD.",
  "brand": "Sewa Hospitality",
  "url": "https://sewahospitality.com",
  "logo": "https://media.sewahospitality.com/brand/sewa-logo-primary.png",
  "telephone": "+919873255531",
  "address": {
    "street": "MS0228, 2nd Floor, DT Mega Mall, A Block, DLF Phase 1",
    "city": "Gurugram", "state": "Haryana", "postalCode": "122002", "country": "IN"
  },
  "geo": { "lat": 28.4949, "lng": 77.0886 },
  "sameAs": [],
  "slogan": "Care, delivered.",
  "foundingDate": "2026"
}
```

This JSON is the canonical input for schema.org Organization/LegalBusiness markup ([../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md)) and every contact/footer template. Phone formats are locked: display everywhere as **+91 98732 55531**; machine contexts (schema.org JSON, `tel:` links) use E.164 **+919873255531**. No other variant is allowed anywhere (NAP rule).

---

Related: [01-executive-summary.md](01-executive-summary.md) · [03-service-catalog.md](03-service-catalog.md) · [../05-design-system/01-brand-guidelines.md](../05-design-system/01-brand-guidelines.md) · [../07-marketing-trust/03-trust-authority.md](../07-marketing-trust/03-trust-authority.md)
