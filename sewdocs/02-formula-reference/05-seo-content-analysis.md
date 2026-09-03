# 05 — Reference SEO & Content Analysis

**Every SEO mechanism and content pattern the reference platform uses — and the complete content inventory.**

---

## 1. Head/meta patterns

### 1.1 Main site (Next.js)
- **Title patterns:** keyword-rich pipes, e.g. `Relocation Companies in India | Domestic Relocation Services in India | Formula Group`; service pages like `Employee Mobility Services in India | Seamless Workplace Transitions`.
- **Description:** unique per page (homepage, services, contact verified).
- **Keywords meta:** present (Google ignores it; harmless).
- **Legacy tag spam:** `DC.title`, `DC.coverage`, `doc-type`, `dcterms.*`, `revisit-after`, `distribution`, `subject`, `googlebot`, `language`, self-referential `<link rel="alternate" hrefLang="EN-US">` — 2021-era meta tag kits that add noise, not rank.
- **OG/Twitter:** complete (type, title, description, url, site_name, image; `summary_large_image`, `@formulaindia`).
- **Canonical:** self-referential per page. Good.
- **Robots:** `follow, index, all` sitewide.

### 1.2 Blog (WordPress + Rank Math)
- Full Rank Math output: title `… - Formula Group`, description, robots with `max-snippet:-1, max-video-preview:-1, max-image-preview:large`, canonical, OG/Twitter complete, `article:tag/section/published/modified`, shortlink, oEmbed alternates.
- **Default author everywhere: WP user `admin`** (Twitter `Written by: admin`; JSON-LD Person = admin with gravatar). Zero E-E-A-T authorship.
- **One post (`monsoon-ready-mobility-support-with-formula-fleet`, 2026) is set `noindex, nofollow, nosnippet`** — an accident (it's a normal fleet post).
- Category pages: title `{Category} - Formula Group`, rel prev/next, `twitter:label1 "Posts"` count.
- `<html lang="">` — empty on WP pages.

### 1.3 News (Next.js from API)
Meta values come from API records — and two of three news items contain literal placeholder strings (`meta_title: "metatitle"`, `meta_keyword: "keyword"`, `meta_desc: "descriptions"`). **No JSON-LD on news pages at all.**

## 2. JSON-LD (exact blocks found)

**Homepage — Product + AggregateRating (self-declared):**
```json
{"@type":"Product","name":"Relocation Services in India","url":"…","image":"…",
 "description":"… provider of integrated corporate mobility services … over 20 Fortune 100 clients …",
 "aggregateRating":{"@type":"AggregateRating","ratingValue":"9.9","bestRating":"10","reviewCount":"1024"}}
```
*Note: 9.9/10 from 1024 reviews is not substantiated anywhere on-site (GBP shows a different, smaller reality). This is a risky pattern — self-referencing review schema without visible reviews can earn a manual action. Sewa will only use `AggregateRating` tied to **real, on-page visible reviews** with source links.*

**Blog (Rank Math `@graph`):** Organization (name, logo) · WebSite (+SearchAction) · CollectionPage (listings/categories) · on posts: ImageObject + WebPage + Person("admin") + **BlogPosting** (headline, dates, articleSection, author, publisher, description).

**Missing everywhere:** LocalBusiness/Organization on the main site, BreadcrumbList, FAQPage, Service schema, sameAs entity links, Organization on non-blog pages. The main site (the money pages) ships **no JSON-LD at all** beyond the homepage Product block.

## 3. Structural SEO findings

| Issue | Detail | Sewa fix |
|---|---|---|
| Double H1 | Every blog post: hero bg-text H1 + content H1 | Exactly one H1 per page (template + CI check) |
| H1 duplication site-wide | Service hub pages reuse the same giant H1 pattern; headings are decorative (Bebas) not semantic | Semantic heading ladder per template type |
| No sitemap.xml | Not found anywhere in production (mirror + crawl log) | Auto-generated sitemap index (pages, services, cities, posts, locales) + Search Console |
| No robots.txt | Referenced in crawl logs but not served | robots.txt + meta directives managed centrally |
| Empty lang | `<html lang="">` on WP; no lang on Next | Proper `lang`/`dir` per locale |
| No hreflang | Only fake self-referential EN-US alternates | Real hreflang set per localized page (x-default EN) |
| Duplicated DOM content | Accordions rendered twice (desktop+mobile) | Single source components |
| Tag-cloud links on all posts | Posts link to all 10 site-wide tags instead of their own | Sidebar shows the post's own terms; sitewide tag cloud lives on tags index |
| Broken internal routes | Job detail URLs 404; wellbeing-support 500; awards page unlinked | Route health check in CI; no orphan routes |
| 46 `?p={id}` shortlink variants | Mirror artifacts; shortlinks exist | Clean canonical discipline; redirect map |
| Reviews not visible with rating schema | Product AggregateRating with no on-page reviews | Reviews rendered on-page with source + matching schema |
| Blog on subpath of same domain | Good practice — keep | Keep path-based content on root domain |
| Author = admin | Zero authorship/E-E-A-T | Named authors with profiles, `sameAs`, credentials |

## 4. Content inventory (complete)

### 4.1 Blog — 45 posts, 2019–2026
- **2019 (1):** India immigration rules/compliance hiring guide.
- **2020 (8):** Indian visa changes · COVID-19 outbreak/impact ×3 · MHA consolidated guidelines · affordable domestic help · expat schooling · India reopening.
- **2021 (11):** COVID resource list + fundraiser + **8-post city-guide series** (Mumbai, Bengaluru, Delhi, Ahmedabad, Hyderabad, Chennai, Pune, GIFT City) — each covering housing, cuisine, healthcare, schooling, finance, transport, safety, immigration.
- **2022 (2):** India Inc relocation policies for talent · language barriers in global mobility.
- **2023 (8):** choosing relocation partner · corporate housing guides (India, Chennai market, Gurugram) · G20 impact on mobility · yoga/mental wellbeing · significance of mobility providers · temporary accommodation.
- **2024 (4):** expat relocation guide · green moving · moving talent seamlessly · what sets the best relocation company apart.
- **2025 (6):** expat's outside view of India · Immigration & Foreigners Bill 2025 · OCI portal launch · Hyderabad corporate housing · corporate housing explainer · 2026 relocation trends.
- **2026 (5):** expat professional networking · brand global-mobility post · FRRO registration guide · Indian roads for expats · monsoon fleet support (this one noindexed).

**Formats:** long-form guides (500–1,400 words), city guides (~700), listicles, policy explainers. **Tone:** professional B2B-global-mobility, service-oriented, keyword-aware. **Every post authored by "admin."** Comments: zero across all posts.

### 4.2 News — 3 items only
Sri Lanka visa-free for Indian tourists (May 2024) · Sri Lanka 5-year residence visas (Dec 2021) · Kuwait visa suspension for Lebanese (Dec 2021). Two carry placeholder metas. (A 3-post "News" section with 2021 content is effectively dead — Sewa merges news+blog into one editorial entity.)

### 4.3 Static marketing copy themes
- **Home:** who-we-are (Fortune-500 clients, human-centric platform, empathy), solutions cards, CTA bands, counters, 4 testimonials.
- **About:** mission, values-as-graphic, leadership bios (15), CSR teaser.
- **Services:** intro paragraphs + accordion service lists (scope of each service fully enumerated).
- **Technology:** MobiRelo 9 features + 3 capability blocks.
- **Careers:** life-at-company copy, 6 openings with skills/responsibilities, gallery.
- **Contact:** 9 office addresses + phone/email.
- **CSR:** 7 NGO partner descriptions.
- **Clients Speak:** 24 quotes across 5 services/7 cities.
- **Legal:** privacy+cookies, T&C+cancellation+shipping, disclaimer+copyright.

### 4.4 Keyword themes observed (their targets)
relocation company/company in India · corporate relocation · relocation services India (+ city names: Delhi, Gurgaon, Pune, Chennai, Ahmedabad, Mumbai, Bangalore) · employee mobility services · corporate housing (+ city) · serviced apartments · FRRO registration · OCI · Indian visas · pet relocation · car rental SEO cluster (long-term/daily/self-drive) · G20 global mobility · city expat guides (housing/schooling/healthcare/finance/transport/safety).

**Sewa keyword architecture:** [../06-content-seo/03-city-content-program.md](../06-content-seo/03-city-content-program.md) + per-page templates in [../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md) + copy templates [../06-content-seo/06-copy-templates.md](../06-content-seo/06-copy-templates.md).

## 5. Tracking & analytics (reference)

| Service | ID | Placement |
|---|---|---|
| Google Analytics 4 | `G-2ECB1V02XX` | all pages (gtag in head) |
| Legacy UA | `UA-68699340-1` | blog only (dual-tagged with GA4) |
| MS Clarity | `o4miv2tl4h` | all pages |
| Facebook Pixel | `662923848289156` | all pages (`PageView` + noscript) |
| reCAPTCHA | reaptcha wrapper | contact form |
| Google Maps | 8–9 embeds | contact page |
| YouTube embeds | video modal + blog overlay playlist | home + blog |

No GTM (hardcoded tags — hard to maintain), no consent management (GDPR/DPDP exposure with the FB pixel), no LinkedIn insight tag, no chat widget, no WhatsApp widget.

**Sewa plan:** GTM-managed GA4 + Consent Mode v2 + event map ([../07-marketing-trust/02-analytics-plan.md](../07-marketing-trust/02-analytics-plan.md)), self-hosted or gated Clarity-equivalent, no third-party pixels before consent, Turnstile.

## 6. What the reference's SEO gets right (kept)

- Clean URL slugs; path-based blog on the main domain.
- Canonicals on every page.
- Full OG/Twitter coverage.
- Per-locale-scale content depth (45 posts, real guides, city series).
- A visible review widget concept (testimonials) — just not tied to real reviews.
- Alt text present on most main-site imagery (the API records carry `alt_tag`).

## 7. Sewa SEO program (where this all goes)

- Technical: [../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md) — sitemap/robots, hreflang, single H1, schema graph (Organization, LocalBusiness per office, Service, FAQPage, BreadcrumbList, Article with real authors, Review tied to visible testimonials), CWV budgets.
- Content: [../06-content-seo/01-content-strategy.md](../06-content-seo/01-content-strategy.md) + [../06-content-seo/03-city-content-program.md](../06-content-seo/03-city-content-program.md) (all-India, what-people-search driven) + [../06-content-seo/06-copy-templates.md](../06-content-seo/06-copy-templates.md).
- AEO/LLM: [../06-content-seo/05-aeo-llm-presence.md](../06-content-seo/05-aeo-llm-presence.md).
- Multilingual: [../06-content-seo/04-multilingual-content.md](../06-content-seo/04-multilingual-content.md) (hreflang × ko/ja/tr/ar).
- Google ecosystem + GBP: [../07-marketing-trust/01-google-ecosystem.md](../07-marketing-trust/01-google-ecosystem.md).

---

Related: [01-site-map-and-pages.md](01-site-map-and-pages.md) · [04-design-brand-analysis.md](04-design-brand-analysis.md) · [06-weaknesses-opportunities.md](06-weaknesses-opportunities.md)
