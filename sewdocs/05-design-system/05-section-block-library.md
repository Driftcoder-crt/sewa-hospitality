# 05 — The Section & Component Library (47 Premade Blocks)

**Every page is composed from a deep, pre-built library: 47 section/blocks across six categories — including a full promotional/conversion set — all theme-aware, all locale-aware, all SEO-instrumented. Editors assemble pages like a magazine spread; nothing requires code.**

This expands the original 17-block CMS library ([../04-modules/01-cms.md](../04-modules/01-cms.md) §2) — that doc's core block list is now a subset of this catalog. Every block: (a) consumes only theme tokens ([04-theme-engine.md](04-theme-engine.md) §3), (b) accepts `data-theme`, (c) ships mobile-first (390px-first, scales up), (d) is RTL-verified, (e) is content-editable with per-locale variants, (f) passes the contrast + SEO publish gates.

---

## 1. Catalog map

| # | Category | Blocks | Purpose |
|---|---|---|---|
| A | Layout & Structure | 7 | page rhythm, sections that frame other content |
| B | Editorial & Content | 9 | text/image storytelling |
| C | Media & Visual | 8 | imagery, video, galleries, sliders |
| D | Social Proof & Trust | 6 | reviews, logos, testimonials, stats |
| E | Promotional & Conversion | 8 | offers, CTAs, capture, campaign sections |
| F | Interactive & Dynamic | 10 | data-driven sections fed by modules |

## 2. Category A — Layout & Structure

| Block | Key props | Notes / evidence |
|---|---|---|
| **A1 Hero** | headline, sub, CTAs[1–2], media (image/video facade), align, theme, height preset (full/split/compact), overlay scrim, eyebrow label | 3 height presets; eyebrow = tracked uppercase label ([01-brand-guidelines.md](01-brand-guidelines.md) §4) |
| **A2 Split Hero** | media side (left/right), copy side, optional form mini (email+CTA) | wild-ag/nomu-style asymmetric split |
| **A3 Section Wrapper** | inner theme, background media/color, padding density, anchor id, eyebrow + title + intro slots | the framing primitive every other block sits in |
| **A4 Feature Grid** | 2/3/4-col, card style (border/plain/filled), per-cell icon+title+text | cards get 2–6px editorial radius |
| **A5 Bento Grid** | mixed-size tiles (2×2/2×1), each tile = any mini-block (stat, image, CTA) | modern editorial pattern |
| **A6 Step Flow** | 3–6 steps, numbered serif numerals, connector line, per-step media | "how we work" journeys |
| **A7 Marquee Strip** | scrolling text/logo/media ribbon, pause-on-hover, reduced-motion static fallback | kortrijk-style news ribbon; CSS-only animation |
| **A8 Spacer/Divider** | height, ornament (rule/quote/seal motif) | — |

## 3. Category B — Editorial & Content

| Block | Key props | Notes |
|---|---|---|
| **B1 Rich Text** | sanitized wysiwyg with heading-ladder enforcement + pull-quote/callout styles | single-H1 rule enforced |
| **B2 Text + Media** | copy left/right, media (image/gallery single), caption, parallax flag | flagship editorial layout |
| **B3 Chapter Heading** | big serif display title, optional number ("01"), rule line | orionix-style editorial dividers |
| **B4 Accordion** | single-source items, first-open flag, one-at-a-time | replaces reference's duplicated-DOM accordion |
| **B5 Tabs** | deep-linkable `?tab=`, icon+label | reference's office tabs, upgraded |
| **B6 Timeline** | vertical/horizontal, dated milestones, media per node | relocation journey, company story |
| **B7 FAQ** | Q/A items → FAQPage schema, category groupings | AEO gold ([../06-content-seo/05-aeo-llm-presence.md](../06-content-seo/05-aeo-llm-presence.md)) |
| **B8 Comparison Table** | 2–4 columns, row labels, highlight column, check/dash icons | serviced-apartments-vs-hotels, tiers |
| **B9 Story Pillars** | 3–5 tall cards with tall imagery, title, 2-line hook | values, service pillars |

## 4. Category C — Media & Visual

| Block | Key props | Notes |
|---|---|---|
| **C1 Gallery Grid** | 2–4 col, masonry flag, lightbox, captions, aspect presets | real `<img>`+alt ([../03-technical-specs/09-media-pipeline.md](../03-technical-specs/09-media-pipeline.md)) |
| **C2 Carousel** | scroll-snap + arrows + dots, autoplay flag (off default), keyboard | CSS-first, Swiper only if a real need appears |
| **C3 Full-Bleed Media** | image/video facade, caption, scrim, optional quote overlay | cinematic section (deep theme) |
| **C4 Video Feature** | facade poster + play, transcript link, caption | — |
| **C5 Logo Cloud** | grouped logos (memberships/partners/clients), grayscale→color hover, link targets | only badges actually held |
| **C6 Image Duo/Trio** | 2–3 art-directed images, overlapping offset layout, captions | editorial spread feel |
| **C7 Map Block** | office/city pin(s), click-to-load facade, address card, directions link | LocalBusiness schema per pin |
| **C8 Before/After Slider** | two media layers, drag handle, a11y keyboard control | housing renovations, interiors portfolio |

## 5. Category D — Social Proof & Trust

| Block | Key props | Notes |
|---|---|---|
| **D1 Testimonial Grid** | source filter (home/service/city), layout (cards/quote-wall/masonry), source badges | schema-matches-visible rule |
| **D2 Review Highlights** | live GBP rating + 3 recent reviews, "as of" date, link to Google | honest rating display ([../04-modules/08-testimonials-reviews.md](../04-modules/08-testimonials-reviews.md)) |
| **D3 Stats Band** | 3–5 counters, "as of" line, serif numerals | honest values, count-up on intersect |
| **D4 Trust Checklist** | checklist items with icons (e.g. Sewa-Verified standards) | links to /housing/verified |
| **D5 Case Story** | anonymized client scenario: challenge→approach→outcome + metrics | enterprise-buyer proof |
| **D6 Team Grid** | people cards (photo, name, role, languages, LinkedIn), tap-to-bio page | replaces hover-only bios |

## 6. Category E — Promotional & Conversion (the "marketing kit")

| Block | Key props | Notes |
|---|---|---|
| **E1 CTA Band** | headline, copy, 1–2 buttons, theme (brand/deep), layout (centered/split) | the reference's recurring band, made dynamic |
| **E2 Lead Form Section** | form type (contact/quote/callback), fields config, benefits list beside form, privacy note | Turnstile+idempotency wired ([../04-modules/03-leads-crm.md](../04-modules/03-leads-crm.md)) |
| **E3 Offer Banner** | timed/evergreen offer strip, code chip, dismissible, themable | campaign promos |
| **E4 Newsletter Capture** | inline + modal variants, locale-aware copy, double-opt-in | **actually works** (reference's didn't) |
| **E5 Promo Card Grid** | 2–4 offer cards (title, terms, badge, CTA, validity) | housing seasonal rates, fleet offers |
| **E6 Countdown Promo** | event/offer deadline, timezone-correct, expires gracefully | expo-style urgency (kortrijk evidence) |
| **E7 Exit-Intent Modal** | trigger rules (exit intent/scroll %/time), frequency cap (1/7d), form or message | branded, focus-managed, never on first paint |
| **E8 Sticky CTA Bar** | mobile bottom bar (call/WhatsApp/chat), desktop rail, dismissible with memory | expat mobile behavior: phone-first contact |

## 7. Category F — Interactive & Dynamic (module-fed)

| Block | Source module | Key props |
|---|---|---|
| **F1 Services Grid** | Services | family filter, icon+image cards, auto from catalog |
| **F2 Service Detail Accordion** | Services | per-service scope accordions (content from module) |
| **F3 Housing Inventory Grid** | Cities/Housing | filters (city/tier/beds/price), verified badges, availability CTA |
| **F4 City Coverage Strip** | Cities | hub cities, hover cards, "we operate in…" links |
| **F5 Posts Feed** | Blog | type filter (blog/news), category/tag filter, card count, featured flag |
| **F6 Category Cloud** | Blog | category cards with counts + editable intros |
| **F7 Job Listings** | Careers | department/city filter, "open roles" list with apply CTAs |
| **F8 Leadership Grid** | HR | leadership profile cards (is_public=1) |
| **F9 Ventures Strip** | CMS | Sewa service-line cross-links (on-domain) |
| **F10 Search Widget** | Scout | inline site-search box with grouped preview results |

## 8. Cross-block platform features (all 47 get these)

1. **Theme-aware:** `data-theme` slot + all styles via tokens — any block works in any theme ([04-theme-engine.md](04-theme-engine.md)).
2. **Presets:** any configured block can be **saved as a preset** and reused across pages ("Sewa Standard Hero", "Pune Housing Grid") — presets are entities; edit the preset, pages update on republish.
3. **Drafts + scheduling:** block changes save with the page's revision trail.
4. **Per-locale content:** every text/media slot has locale variants ([../04-modules/11-multilingual.md](../04-modules/11-multilingual.md)).
5. **SEO slots:** blocks that can lead a page expose meta overrides; block-level FAQ/People/Review schema only when matching visible content.
6. **A11y contract:** keyboard/contrast/focus/alt rules baked into the block, not the editor's memory.
7. **Perf contract:** every block ships with a declared weight (DOM nodes + media budget); page composer sums budgets and warns past the page-type budget ([07-compliance-standards.md](07-compliance-standards.md) §3).
8. **Custom HTML escape hatch:** admin-only, sanitized, audited — for the rare embedding need (used sparingly by design).

## 9. Page-level customization (every page, no exceptions)

Per page ([../04-modules/01-cms.md](../04-modules/01-cms.md) §4–5): default section theme · header/footer variant (standard/transparent-over-hero/hidden) · spacing density · hero style default · block presets · custom class allowlist · custom schema overrides · per-page noindex with confirm · and the full theme panel applies underneath everything ([04-theme-engine.md](04-theme-engine.md) §5).

## 10. Build order note
Blocks ship in waves with the build roadmap: M1 = A1–A4, B1–B4, E1 (page-composition core) → M2 = C1–C5, D1–D3, B5–B9 → M3 = E2–E8, D4–D6 → M4 = F1–F10, C6–C8, A5–A8. Every wave lands with its Pest + visual-state coverage in `/dev/components` ([02-ui-components.md](02-ui-components.md) §3).

## 11. Tests
Per-block render suite × 4 themes × {LTR, RTL(ar)}; block-weight budget assertions; preset save/apply; module-fed blocks handle empty sources gracefully (zero-state, never broken); E-blocks: frequency caps, dismissal memory, consent-gating of any tracking; all A-block nesting combos contrast-safe.

---

Related: [04-theme-engine.md](04-theme-engine.md) · [02-ui-components.md](02-ui-components.md) · [03-ux-interactions.md](03-ux-interactions.md) · [../04-modules/01-cms.md](../04-modules/01-cms.md) · [06-reference-sites-analysis.md](06-reference-sites-analysis.md)
