# 08 — Search

**Laravel Scout with the database driver (MySQL FULLTEXT) at launch — zero external services on shared hosting — with a one-config swap to Typesense Cloud when scale demands it.**

---

## 1. What is searchable (and who searches it)

| Surface | Entity | Fields indexed | Notes |
|---|---|---|---|
| Public site search | services | name, short_desc, intro (stripped) | hub-level, fast |
| | cities | name, state, description | city program growth |
| | posts | title, excerpt, body, author names, tags | blog + news |
| | housing_units | name, locality, area, amenities | filterable search |
| | job_postings | title, location_text, skills | only open ones |
| | pages | title, meta blocks text | legal/about |
| Admin search | leads, applications, organizations, invoices, users | per-module fields | scoped by role permissions |
| Portal search | my documents, my moves, threads | role-scoped | never cross-tenant |
| API `/v1/search` | grouped hit sets of the public entities above | — | grouped + capped per group |

## 2. Database driver configuration

```php
// config/scout.php — 'driver' => env('SCOUT_DRIVER', 'database')
// Model trait + searchable():
#[SearchUsingFullText(['title', 'body'])]
class Post extends Model { use Searchable; … }
```

- FULLTEXT indexes declared in migrations: posts(title, body), cities(name, description), housing_units(name, locality, amenities), services(name, short_desc), job_postings(title, skills, location_text).
- MySQL 8 FULLTEXT default min word size (innodb_ft_min_token_size=3) documented; if Hostinger's server differs, fall back to `LIKE`-prefix + FULLTEXT hybrid (Scout database driver handles like-based fallback natively for short queries).
- ngram parser enabled for CJK where available (`WITH PARSER ngram`) — checked at install; if unavailable, CJK search falls back to LIKE (Japanese/Korean queries are still supported, just less fuzzy).

## 3. Search UX

```
sewahospitality.com/search?q=relocation+pune
  Grouped tabs: Services (3) · City guides (7) · Housing (24) · Blog (31) · Careers (2)
  Each hit: icon, title, breadcrumb context, snippet with <mark>, locale badge if translated
  Zero-state: suggested links + top services (never a dead "no results")
```
- Livewire island, debounced 250ms, min 2 chars, cached 10min per query (shared-hosting CPU discipline).
- Query logged (anonymous, hashed IP) into `search_queries` (term, count, locale, zero-results flag) → feeds the content backlog: zero-result queries become editorial tickets ([../06-content-seo/01-content-strategy.md](../06-content-seo/01-content-strategy.md)).
- Filters preserved as query params (SEO + shareable): `?q=…&type=housing&city=pune&tier=executive`.

## 4. Admin search

Global admin command palette (`⌘K`): jump to any lead (by name/phone/email), application, post, service, city, invoice, user — role-scoped results, keyboard navigable, ≤ 150ms on shared hosting (FULLTEXT + small tables).

## 5. The Typesense Cloud upgrade path (when, not if)

**Trigger criteria (any two):**
1. Public search > ~5k searches/day with CPU throttling visible in Pulse.
2. Typo-tolerance demand (transliteration: "Bangalore ↔ Bengaluru ↔ ಬೆಂಗಳೂರು", "Gurgaon ↔ Gurugram") that FULLTEXT can't honor.
3. Faceted housing search at scale (city + tier + bedrooms + amenities + price bands).

**Why the swap is painless:** Scout abstracts the driver. Steps:
1. Provision Typesense Cloud (free tier first) or Meilisearch Cloud.
2. `.env`: `SCOUT_DRIVER=typesense` + host/api key.
3. `php artisan scout:import` for each searchable model (queued, chunked).
4. Feature flags: `SEARCH_FACETS=true` unlocks faceted housing UI; typo-tolerance config in the Typesense collection schema.
No application code changes — the contract (searchable models + query API) is identical. This is exactly why the reference's "can't run search processes on shared hosting" ceiling does not cap Sewa.

## 6. SEO & search integration
- `/search` results pages: `noindex, follow` (avoid search-page index spam) but crawlable links out.
- Tag/category archive pages are NOT search — they're CMS routes, indexable with curated content ([../04-modules/07-blog-news.md](../04-modules/07-blog-news.md)).
- Site search terms (zero-result log) also feed the AI FAQ-drafting queue as "what people ask" seed data ([../06-content-seo/05-aeo-llm-presence.md](../06-content-seo/05-aeo-llm-presence.md)).

---

Related: [03-database-schema.md](03-database-schema.md) (index map) · [07-queues-scheduling.md](07-queues-scheduling.md) (import jobs) · [../04-modules/01-cms.md](../04-modules/01-cms.md) · [../09-delivery/02-future-scaling.md](../09-delivery/02-future-scaling.md)
