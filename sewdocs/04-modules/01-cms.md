# 01 — CMS Module

**Every page, banner, CTA, stat, menu, and setting on the public site becomes editable — the single biggest capability gap vs. the reference (which hardcodes all content in a JS app).**

---

## 1. Purpose
Give non-technical editors total control of the marketing site — compose pages from typed blocks, manage navigation and sitewide settings, manage redirects — with publish workflows, SEO enforcement, locale variants, and zero engineering dependency. Content lives in `pages`/`settings` per [../03-technical-specs/03-database-schema.md](../03-technical-specs/03-database-schema.md) §2.

## 2. The block library (the page "Lego")

**The complete, current catalog is [../05-design-system/05-section-block-library.md](../05-design-system/05-section-block-library.md) — 47 premade sections/blocks across six categories (Layout & Structure, Editorial & Content, Media & Visual, Social Proof & Trust, Promotional & Conversion, Interactive & Dynamic), including the full promotional/conversion kit (offer banners, countdowns, exit-intent, sticky CTA bars, newsletter capture).** Every block is theme-token-driven with a `data-theme` slot ([../05-design-system/04-theme-engine.md](../05-design-system/04-theme-engine.md)), mobile-first, RTL-verified, preset-saveable, and budget-accounted. The JSON block schema below is the storage format for all of them.

| Block | Data (editor-facing fields) | Used on (seed templates) |
|---|---|---|
| `hero` | headline, sub, media (focal), CTAs[], alignment, overlay strength | every page top |
| `stats` | items [{value, suffix, label, as_of}] — honest counters w/ "as of" | home, about |
| `rich_text` | wysiwyg (sanitized whitelist), with heading ladder enforcement | about, legal |
| `accordion` | items [{title, body_html}] — single-source (no DOM duplication) | service pages |
| `tabs` | items [{label, content}] — deep-linkable via ?tab= | contact offices |
| `cards_grid` | manual cards OR source (services/cities/posts/testimonials) + filters | hubs |
| `testimonial_grid` | source filter (home/service/city), limit, style | home, services, reviews page |
| `logos_strip` | group (memberships/partners) — only badges actually held ([../07-marketing-trust/03-trust-authority.md](../07-marketing-trust/03-trust-authority.md)) | global above footer |
| `ventures_strip` | auto (future-ready) or curated cross-links | global |
| `gallery` | media_ids, layout grid/carousel, lightbox | CSR, careers, about |
| `video` | youtube_id or media file, poster | home, about |
| `leadership_grid` | department filter, layout | about |
| `faq` | items [{q,a}] — renders FAQPage schema | services, cities |
| `cta_band` | headline, button | all |
| `form_embed` | which form (contact/quote/callback), fields prefill | contact, services |
| `offices` | office list w/ map facades | contact |
| `custom_html` | admin-only escape hatch (audited, sanitized) | rare |

Every block: `data-aos` style reveal optional (respecting reduced-motion), per-block spacing control (section rhythm), and preview rendering identical to public.

## 3. Public surface

| Route | Source |
|---|---|
| `/` | pages(slug=home) with blocks |
| `/about` · `/contact` · `/legal/{page}` · `/reviews` | pages with type=standard/legal |
| any `/p/{slug}` landing | pages(type=landing) for campaigns |
| 404/500/503 | system templates with search + top services (never dead-end) |
| `/thank-you` | form success page (lead_ref echo, next-steps content) |

Render path: page → locale resolution → blocks → each block renders via its Blade component (`<x-blocks.hero …/>`) with media pipeline ([../03-technical-specs/09-media-pipeline.md](../03-technical-specs/09-media-pipeline.md)) and SEO meta service ([../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md)). Anonymous full-page cache keyed path+locale; invalidation tagged by page + its content sources.

## 4. Admin surface (CMS screens)

1. **Pages** — table (title, slug, status, locale, updated_by, updated_at) + filters; row actions: edit, duplicate to locale, view, unpublish.
2. **Page editor** — settings drawer (slug, template, publish/schedule, SEO panel with templates + character counters, noindex toggle with confirm, locale selector) + **live block canvas**: add/reorder/delete blocks, edit inline, live preview iframe (actual mobile/tablet/desktop rendering), autosave drafts every 10s with revision history (last 20 revisions, diff view, restore).
3. **Menus** — drag-drop tree per location + locale; nav item types (page/service/city/custom) with future-safe slug validation.
4. **Settings** — grouped tabs: Brand identity (organization JSON from [../01-platform-vision/02-brand-sewa-hospitality.md](../01-platform-vision/02-brand-sewa-hospitality.md) §9), Contact/NAP, Socials, Offices, Stats, Membership badges (each badge: name, logo, link-to-proof, "held since"), Integrations (GA id, Turnstile keys ref, etc. — values via env indirection where secret), Legal texts (privacy/cookies).
5. **Redirects** — from/to/code/hits/notes; import from CSV (migration tool); auto-suggest on slug change ("old slug → new slug, add 301?").
6. **Media library** — folder tree by namespace, grid with alt/credit editing, focal point picker, replacement-in-place (new hash URL per [../03-technical-specs/09-media-pipeline.md](../03-technical-specs/09-media-pipeline.md) §5).
7. **Revisions & audit** — every save → revision row + activity_log entry.

Permissions: `editor`+ for pages/menus/settings-content; settings-integrations and redirects = `admin`+.

## 5. Behavior & rules
- **Publish states:** draft → scheduled → published → archived. Scheduled publishes via queued job at scheduled_at (cron-driven).
- **SEO enforcement:** publish blocked with field-level errors if meta_title (≤ 60 chars guidance) or meta_description (≤ 160) empty; noindex requires typed confirmation + reason (logged) — the accidental-noindex defect is structurally impossible.
- **Slug rules:** lowercase-hyphen; collision-checked across pages + services + cities; changing a published slug auto-offers the 301.
- **Revision safety:** restoring an old revision is itself a new revision (never destructive).
- **Menu integrity:** deleting a linked entity auto-flags the menu item for review (never silently dead links).
- **Block contracts:** each block validates its data shape on save (no orphan media refs; broken source filter = visible warning in editor).

## 6. Error handling
- Editor autosave failure → visible "unsaved changes" banner with retry (never silent loss — the reference's exact failure mode for its API-edited content).
- Preview render errors on a block → block-level error card in canvas (page keeps editing); public page never publishes a broken block (publish runs a render probe).
- Cache invalidation failures → logged + Pulse; stale page self-heals on TTL (max 10-min staleness).

## 7. Events & integrations
`PagePublished/PageUnpublished` → sitemap regeneration queue, cache tag purge, Search index upsert. `SettingsUpdated` → cache purge + Pulse audit entry. Blocks pull other modules **read-only** via service interfaces (e.g. testimonial_grid → Testimonials service).

## 8. Tests
- Block render suite × every block type × {en, ar} (RTL) — golden templates.
- Publish-blocked-on-missing-SEO test; noindex confirmation flow; slug collision; scheduled publish fires via `travel()`; revision restore; cache invalidation on publish; autosave failure UX state.

---

Related: [00-module-system.md](00-module-system.md) · [../03-technical-specs/03-database-schema.md](../03-technical-specs/03-database-schema.md) · [../03-technical-specs/09-media-pipeline.md](../03-technical-specs/09-media-pipeline.md) · [../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md) · [05-admin-panel.md](05-admin-panel.md)
