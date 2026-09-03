# 03 — Service Catalog

**The complete service surface — mapped 1:1 from the reference platform, renamed and re-specified for Sewa Hospitality.**

The reference company's site (teardown in [../02-formula-reference/01-site-map-and-pages.md](../02-formula-reference/01-site-map-and-pages.md)) sells 11 services in two families: Employee Mobility (6) and Business Mobility (5), with a separate Immigration sub-tree. Sewa launches with the identical business surface — every service the reference offers — under Sewa naming and structure, each with an upgraded page spec.

---

## 1. Catalog tree (URL structure)

```
sewahospitality.com/services
├── /services/employee-mobility                    (family page)
│   ├── /services/employee-mobility/relocation
│   ├── /services/employee-mobility/immigration     (hub → 3 children below)
│   │   ├── /services/immigration/inbound-immigration
│   │   ├── /services/immigration/outbound-immigration
│   │   └── /services/immigration/ancillary-services
│   ├── /services/employee-mobility/serviced-apartments
│   ├── /services/employee-mobility/moving
│   ├── /services/employee-mobility/corporate-housing
│   └── /services/employee-mobility/fleet
└── /services/business-mobility                    (family page)
    ├── /services/business-mobility/travel
    ├── /services/business-mobility/business-space
    ├── /services/business-mobility/recruitment
    ├── /services/business-mobility/interior-design
    └── /services/business-mobility/sanitization
```

Two families mirror the reference's proven structure (Employee / Business mobility). Immigration keeps its own sub-tree because it has three child pages of real depth (the reference does this too and it ranks for FRRO/visa intent).

**Slug rule:** lowercase, hyphenated, no dates, stable forever. Redirects are permanent if a slug ever changes (managed in admin, see [../04-modules/02-services-module.md](../04-modules/02-services-module.md)).

## 2. Service line specs

Each service: Sewa name · reference name it maps to · offer scope (from the reference's accordion contents) · Sewa page content blocks · lead intents to capture.

---

### 2.1 Relocation Services (Employee Mobility)
*Maps: reference "Relocation Services" (orientation, home search, school search, tenancy management, departure program).*

- **Scope:** Orientation tours · Home search · School search · Tenancy management (lease, renewals, repairs) · Settling-in (utilities, registrations) · Departure programs.
- **Sewa page blocks:** Hero + intro · "Your move, step by step" timeline (arrival → 90 days) · Service accordions (single-source components, not duplicated DOM like the reference) · City coverage strip (links to city pages) · Consultant-for-this-service (named human) · FAQ (schema-marked) · CTA: request a move plan.
- **Lead intents:** "corporate relocation India", "relocation services Gurugram/Mumbai/…", "home search assistance", "school search expat India".

### 2.2 Immigration Services (Employee Mobility hub → sub-tree)
*Maps: reference "Immigration Services" with INBOUND / OUTBOUND / ANCILLARY children.*

- **Inbound scope:** FRRO/FRO registration · Resident permits · Visa/resident permit extensions · Change of address/passport/jurisdiction · Visa conversion · Exit permits · OCI card assistance.
- **Outbound scope:** Employment & dependent visas · Consular visas · Work permits · Document legalization.
- **Ancillary scope:** PAN card issuance · (Sewa adds: Aadhaar-enrollment guidance, bank account onboarding, driver's license conversion — real expat pain points the reference doesn't cover).
- **Sewa page blocks:** Compliance timeline (what's due, when) · Document checklist generator (interactive, better than reference) · Fee transparency table · Immigration tracker teaser (portal feature) · FAQ with schema.
- **Lead intents:** "FRRO registration", "visa extension India", "OCI card", "PAN card for foreigners".

### 2.3 Serviced Apartments (Employee Mobility)
*Maps: reference "Serviced Apartments" with city deep-links to its sister site.*

- **Scope:** Furnished, serviced stays across budgets, with centralized booking and single invoicing (the reference's verified-inventory promise, but Sewa keeps this **on-domain**: /housing/serviced-apartments/city pages instead of shipping users to a sister site — keeping the SEO).
- **Sewa page blocks:** What's included (housekeeping, WiFi, utilities) · City inventory grids (Gurugram, Mumbai, Bengaluru, Delhi, Pune, Hyderabad, Chennai — grown by the city-content program) · "Sewa Verified" standard (inspection checklist published — trust play the reference only implies) · Enquiry form with dates + city.
- **Lead intents:** "serviced apartments Gurugram", "furnished apartment Mumbai short stay", "corporate guest house".

### 2.4 Moving Services (Employee Mobility)
*Maps: reference "Moving Services" (local/domestic/international household goods, pet relocation, workplace moves).*

- **Scope:** Local & domestic household moves · International HHG (partner networks) · Pet relocation (domestic & international) · Office/workplace moves · Industrial/warehouse/lab equipment moves.
- **Sewa page blocks:** Move-type selector · Packing standards (materials, insurance) · Insurance & valuation explained plainly · Pet relocation care note · Tracking teaser · CTA.
- **Lead intents:** "pet relocation India", "international movers India", "office relocation Gurugram".

### 2.5 Corporate Housing (Employee Mobility)
*Maps: reference "Corporate Housing" (options across budgets, centralized bookings, verified, easy payment management).*

- **Scope:** Multi-city corporate housing programs · Centralized booking & billing · Inventory across budget tiers · Payment consolidation.
- **Sewa page blocks:** Program model (one contract, any city) · Inventory quality tiers (Essential / Professional / Executive — names the reference's "budgets" implicitly) · Client-portal billing teaser · Case cards (industries served) · CTA: request inventory proposal.
- **Lead intents:** "corporate housing India", "executive apartments Gurugram", "employee housing vendor India".

### 2.6 Fleet Services (Employee Mobility)
*Maps: reference "Fleet Services" (long-term, daily, self-drive rentals; 24×7 helpline; GPS fleet; SEO links to a sister car-rental site).*

- **Scope:** Long-term corporate leases · Daily rentals · Self-drive · Airport transfers · Event fleet · Dedicated chauffeurs · GPS-tracked vehicles, 24×7 helpline, consolidated billing.
- **Sewa page blocks:** Fleet tiers & example models (transparent) · Coverage cities · Duty-of-care standards (driver vetting, GPS, SOS protocol — corporate HR buyers care) · Rate card approach (range transparency) · Booking enquiry.
- **Lead intents:** "corporate car rental India", "employee transportation vendor", "monthly car rental Gurugram".

### 2.7 Travel (Business Mobility)
*Maps: reference "Travel" (IATA-approved travel division).*

- **Scope:** Corporate travel desk · Visa/travel documentation · Group movements · MICE-lite support. (IATA badge displays only when Sewa is licensed — see brand trust rules.)
- **Sewa page blocks:** Desk services · Duty of care & policy compliance · Reporting teaser · CTA.

### 2.8 Business Space (Business Mobility)
*Maps: reference "Business Space" (office spaces, industrial land, warehouses, factory spaces).*

- **Scope:** Office leasing advisory · Industrial land · Warehouses · Factory spaces · Site tours · Lease negotiation.
- **Sewa page blocks:** Space-type grid · City market notes (from city-content program) · Process · CTA.

### 2.9 Recruitment (Business Mobility)
*Maps: reference "Recruitment" (its slug was literally "requirement")*

- **Scope:** Permanent recruitment · Executive search & selection · Contract hiring · RPO · HR policies & systems drafting (offer letters, HR manuals).
- **Sewa page blocks:** Functional coverage (sales, marketing, HR, finance, legal, admin, production, quality) · Capability list (JD design, research/mapping, head-hunting, assessments, salary negotiation) · Search methodology phases · CTA.
- **Lead intents:** "recruitment agency India", "executive search Gurugram".

### 2.10 Interior Design (Business Mobility)
*Maps: reference "Interior Designing".*

- **Scope:** Corporate interiors · Turnkey fit-outs · Space planning · Furniture packages for housing inventory.
- **Sewa page blocks:** Portfolio gallery (CMS-managed) · Process timeline · Materials/spec standards · CTA.

### 2.11 Sanitization & Facility Care (Business Mobility)
*Maps: reference "Sanitization" (home/office/fleet sanitization, PPE-suited staff).*

- **Scope:** Home & office sanitization · Fleet sanitization · Deep-cleaning programs · (Sewa extends to ongoing facility care, tying to housing inventory upkeep).
- **Sewa page blocks:** Service tiers · Standards/chemicals transparency · Scheduling · CTA.

## 3. Cross-service platform features (the "much better" layer)

Every service page gets (specs in [../04-modules/02-services-module.md](../04-modules/02-services-module.md)):

1. **Editable everything** — hero, intro, accordions, FAQs, images via CMS (reference hardcodes all of this).
2. **Single enquiry pipeline** — every form feeds the Leads/CRM module with the service pre-tagged ([../04-modules/03-leads-crm.md](../04-modules/03-leads-crm.md)).
3. **Per-service SEO pack** — title/description/schema templates per page type ([../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md)).
4. **Multilingual variants** — Korean/Japanese/Turkish/Arabic versions auto-generated, human-reviewed ([../04-modules/11-multilingual.md](../04-modules/11-multilingual.md)).
5. **FAQ + answer-first content** for AEO ([../06-content-seo/05-aeo-llm-presence.md](../06-content-seo/05-aeo-llm-presence.md)).
6. **City cross-linking** — each service links to its city-coverage pages and vice versa (the reference never connects services to cities on-domain).

## 4. What Sewa deliberately adds beyond the reference

| Addition | Why | Module |
|---|---|---|
| Interactive document checklists (immigration) | Real expat pain; AEO gold | Services module |
| Named consultant per service | Trust; the reference hides its humans behind bios only on About | CMS + HR module |
| Sewa Verified housing standard (published checklist) | Differentiation vs. "trust us" | Services + Cities |
| Transparent rate *ranges* | Kills the biggest expat complaint (opaque pricing) — legal-approved ranges only | Services + Billing |
| Response-time SLA published per service | Corporate buyers' #1 evaluation criterion | Trust |
| Review syndication per service page | The reference shows quotes; Sewa shows live, linked Google reviews | Testimonials module |

## 5. Service data model (summary — full spec in [../03-technical-specs/03-database-schema.md](../03-technical-specs/03-database-schema.md))

Each service is a CMS entity: `slug, family (employee|business), name, short_desc, hero_media, intro, content_blocks (ordered, typed), faqs[], related_services[], seo overrides, status, ordering` — plus per-locale translations. Accordions, timelines, and grids are content *blocks* an editor composes, matching the reference's page shapes without hardcoding.

## 6. Service-to-page-to-lead map (complete, nothing missed)

| # | Service (Sewa) | Page | Primary CTA | Lead tag |
|---|---|---|---|---|
| 1 | Relocation | /services/employee-mobility/relocation | Request a move plan | relocation |
| 2 | Immigration hub | /services/employee-mobility/immigration | Book consultation | immigration |
| 3 | Inbound immigration | /services/immigration/inbound-immigration | Start registration | immigration.inbound |
| 4 | Outbound immigration | /services/immigration/outbound-immigration | Consult | immigration.outbound |
| 5 | Ancillary (PAN etc.) | /services/immigration/ancillary-services | Request service | immigration.ancillary |
| 6 | Serviced Apartments | /services/employee-mobility/serviced-apartments | Check availability | housing.serviced |
| 7 | Moving | /services/employee-mobility/moving | Get a move quote | moving |
| 8 | Corporate Housing | /services/employee-mobility/corporate-housing | Request proposal | housing.corporate |
| 9 | Fleet | /services/employee-mobility/fleet | Book / request fleet | fleet |
| 10 | Travel | /services/business-mobility/travel | Talk to travel desk | travel |
| 11 | Business Space | /services/business-mobility/business-space | Enquire | space |
| 12 | Recruitment | /services/business-mobility/recruitment | Send requirement | recruitment |
| 13 | Interior Design | /services/business-mobility/interior-design | Book walkthrough | interiors |
| 14 | Sanitization | /services/business-mobility/sanitization | Schedule | facilities |

(14 rows = 11 services, with Immigration's 3 child pages counted separately.)

---

Related: [01-executive-summary.md](01-executive-summary.md) · [02-brand-sewa-hospitality.md](02-brand-sewa-hospitality.md) · [04-subdomains-ventures.md](04-subdomains-ventures.md) · Reference details: [../02-formula-reference/01-site-map-and-pages.md](../02-formula-reference/01-site-map-and-pages.md)
