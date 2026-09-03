# 07 — Blog & News Module

**One editorial system for blog + news (replacing the reference's WordPress + a dead 3-post news section): named authors, review workflow, scheduling, taxonomy, i18n, and SEO enforced at publish — with the design of the main site, not a second theme.**

---

## 1. Purpose
The content engine for topical authority ([../06-content-seo/01-content-strategy.md](../06-content-seo/01-content-strategy.md)): migration/FRRO explainers, city guides, housing market notes, corporate-mobility thought leadership, and company news. Same design system as the site (kills the reference's visible blog/main-site drift), same auth, same admin.

## 2. Data model
`posts` (type blog|news), `categories` (nested), `tags`, `category_post`, `tag_post`, `author_profiles` ([../03-technical-specs/03-database-schema.md](../03-technical-specs/03-database-schema.md) §4). URL pattern: `/blog/{yyyy}/{mm}/{slug}` (dates preserved from the reference — clean, sortable, matches archive UX); news uses `/news/{slug}`.

## 3. Public surface

| Route | Spec |
|---|---|
| /blog | hero + post cards (cover, date badge, title, excerpt, author, read-time), pagination (rel prev/next), sidebar: search, recent posts, category list with counts, **this-post's** tags (never sitewide cloud — reference defect), newsletter box |
| /blog/category/{slug} (+ nested, +/page/N) | category archive: intro paragraph (editable, indexable) + cards + description meta |
| /blog/tag/{slug} | tag archive (per-post tags only appear on their own posts) |
| /blog/{yyyy}/{mm}/{slug} | post: **one H1** (title), byline block (author avatar, name, role, link to profile — replaces "admin"), date, reading time, cover (og), body (sanitized rich text), categories/tags, prev/next, related 3, FAQ/CTA blocks, share links; JSON-LD Article with author Person |
| /news · /news/{slug} | news cards + articles (type=newn) — real metas required (no "metatitle" placeholders — reference defect) |
| Author pages /team/{author} or /blog/author/{slug} | all posts by author + credentials (E-E-A-T surface) |
| RSS /feed (optional) | full-content feed — AI crawlers love it ([../06-content-seo/05-aeo-llm-presence.md](../06-content-seo/05-aeo-llm-presence.md)) |

## 4. Admin surface
1. **Posts table** — filters (type, status, author, category, locale, date), status chips (draft/review/scheduled/published), translation-completeness badges.
2. **Post editor** — title (auto-slug w/ editable), body (rich text with heading-ladder enforcement + table/quote/callout styles), cover with focal/alt, excerpt (chars counter), category/tree picker, tags (per-post), author picker (required), type toggle, related picker (auto-suggest), blocks (FAQ, CTA, gallery inserts), SEO drawer (title/description templates per type + counters + SERP preview), publish/schedule, locale groups + translation status.
3. **Review workflow** — authors submit to `review`; editors approve (comments thread); approval required for publish (role-gated); "needs review" list.
4. **Categories/Tags** — CRUD, nested parent, editable archive intro, merge tags (with 301 mapping on archive URLs).
5. **Editorial calendar** — month grid of scheduled/published posts + drafts with dates, drag to reschedule, campaign overlays ([../06-content-seo/01-content-strategy.md](../06-content-seo/01-content-strategy.md) cadence).
6. **Translation queue** — see I18n module ([11-multilingual.md](11-multilingual.md)): machine-translated drafts awaiting human review, one-click approve/edit.

Permissions: author (own drafts + submit), editor (approve/publish all, taxonomy), admin.

## 5. Behavior & rules
- **Publish gates:** human author, category, cover with alt, excerpt, metas (non-empty, length-guided) — all enforced; scheduled publishes via queue.
- **Canonical/alt handling:** pagination canonicals to page-1; archives noindex,follow if thin (< 3 posts) — configurable rule; tag archives noindex when only 1 post.
- **Comments:** off by default (the reference shows 0 comments on every post for years). If ever enabled: moderated + Turnstile; default stays off.
- **Related logic:** same category first, then tags overlap, backfill recent — deterministic and explainable.
- **Reading time + word count** computed on save (progressive disclosure in cards).
- **News vs blog:** identical machinery, different presentation (cards) + taxonomy default; news items can be promoted to blog explains.

## 6. Error handling
- Broken embeds/images in body → editor lints dead media refs on save (no published broken media).
- Scheduled publish failure → ops alert + retry; missed slot flagged in calendar.
- Translation half-states impossible: locale publish independent; hreflang only includes published locales.

## 7. Events & integrations
`PostPublished/Scheduled` → sitemap, cache purge, search upsert, newsletter pull (latest-posts block), `TranslationReadyForReview` (I18n). Blog block available to CMS (`cards_grid source=posts`). Analytics: `view_post` server event; AEO: full-text feeds, answer-first templates ([../06-content-seo/05-aeo-llm-presence.md](../06-content-seo/05-aeo-llm-presence.md)).

## 8. Tests
Single-H1 rule; publish gates (author required — the "admin" defect becomes a failing test); category/tree URLs incl. nested; pagination canonicals + prev/next; archives thin-content noindex rule; translation group invariants; related determinism; scheduling fires (travel()); review workflow permissions; body sanitization (script/iframe injection rejected).

---

Related: [00-module-system.md](00-module-system.md) · [01-cms.md](01-cms.md) · [11-multilingual.md](11-multilingual.md) · [../06-content-seo/01-content-strategy.md](../06-content-seo/01-content-strategy.md) · [../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md) · Reference analysis: [../02-formula-reference/05-seo-content-analysis.md](../02-formula-reference/05-seo-content-analysis.md)
