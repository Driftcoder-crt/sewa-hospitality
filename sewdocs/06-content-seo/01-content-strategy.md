# 01 — Content Strategy

**The editorial engine: what Sewa publishes, for whom, in what voice, on what cadence — so content builds topical authority for the keyword clusters that convert, instead of drifting (the reference published 8 city guides in 2021 and stopped).**

---

## 1. Audiences & intent map

| Persona | Reads | Primary intent | Conversion |
|---|---|---|---|
| Corporate HR / Global Mobility manager | service pages, corporate housing guides, compliance explainers, case content | vendor evaluation, risk mitigation, SLA evidence | quote request / demo |
| Relocating employee & family | city guides, FRRO/visa explainers, housing/apartment content, settling-in | anxiety reduction, practical steps | portal adoption, service trust |
| Procurement/finance | pricing approach, billing transparency, governance/CSR | vendor legitimacy | RFP entry |
| Korean/Japanese/Turkish/Saudi clients | same content in their language + market-specific notes | trust in their language | localized quote request |
| AI engines & search | all of it (answer-first structured) | citability | [05-aeo-llm-presence.md](05-aeo-llm-presence.md) |

## 2. Voice & standards
- Brand voice rules (warm-precise, plain English, human names, confident-not-boastful): [../01-platform-vision/02-brand-sewa-hospitality.md](../01-platform-vision/02-brand-sewa-hospitality.md) §3.
- **Authorship:** every post has a named human author with credentials ([../04-modules/07-blog-news.md](../04-modules/07-blog-news.md)) — the E-E-A-T spine the reference lacks ("admin" everywhere).
- **Claims discipline:** every number carries a source and "as of" date; every standard (Sewa Verified) links to its published checklist; no self-declared ratings.
- **Answer-first format:** each post opens with a 2–4 sentence direct answer (the snippet/AI-citation target), then depth.
- **Structure:** one H1, logical H2/H3 ladder, short paragraphs (≤ 3–4 lines), lists/tables where scannable, key-facts box at top for long guides.

## 3. Content pillars & clusters

| Pillar | Clusters (examples) | Formats | Cadence |
|---|---|---|---|
| **City & relocation** | city guides (the program: [03-city-content-program.md](03-city-content-program.md)) — housing markets, neighborhoods, schooling, cost of living | city page + guides + market updates | 2/week during program build, then 1/week |
| **Immigration & compliance** | FRRO registration, visa types, OCI, PAN for foreigners, Immigration & Foreigners Bill explainers | explainers + checklists + FAQs | 2/month |
| **Corporate housing & serviced apartments** | what-is corporate housing, city inventory notes, tenant guides, verified standards | guides + inventory content | 2/month |
| **Moving & fleet** | pet relocation, international moves, green moving, monsoon/road-safety notes, duty of care | guides + service content | 1/month |
| **Global mobility thought leadership** | trends, policy design, measuring mobility ROI, G20-type macro events | POV posts by leadership | 1/month |
| **Company & CSR** | Sewa news, milestone stories, NGO partner features, culture/careers | news + stories | as-happens |
| **Multilingual originals** (not just translations) | market-specific guides: Japanese/Korean corporate relocation to India, Turkish business travel, Saudi/Gulf family moves | localized originals | 1/month per priority locale |

## 4. Editorial workflow
```
Backlog → brief → draft (author) → review (editor: voice/structure/SEO) →
legal/accuracy check (immigration/compliance topics only, checklist'd) →
schedule (calendar) → publish → promote (newsletter, GBP post, social) →
refresh cycle (quarterly: top pages reviewed, stats/claims re-dated)
```
- **Backlog sources:** zero-result site searches ([../03-technical-specs/08-search.md](../03-technical-specs/08-search.md) §3), People-Also-Ask/AI-query research ([05-aeo-llm-presence.md](05-aeo-llm-presence.md)), lead questions (from CRM notes — the best source), competitor gap scans, city coverage matrix.
- **Editorial calendar** is a first-class admin surface ([../04-modules/07-blog-news.md](../04-modules/07-blog-news.md) §4.5) — cadence is visible, not aspirational.
- **Refresh > churn:** the reference's city-guide series is good content left to rot (2021). Sewa's rule: every money page and top-20 post gets a quarterly review; refresh dates update `article:modified` — ranks recover without new URLs.

## 5. What we do NOT publish
- No thin tag/category archive pages with no intro (all archives get editable intros or stay noindexed).
- No comments sections (reference shows 0 comments for 6 years — dead UI signals decay).
- No AI-raw drafts: AI may assist briefs/first drafts but a human author owns, reviews, and signs every published piece ([../08-ai-system/02-ai-use-cases.md](../08-ai-system/02-ai-use-cases.md) usage policy).
- No competitor mention posts; no keyword-stuffed anything (publish gates block empty/dupe metas anyway).

## 6. Launch content set (minimum viable authority)
Before launch (M5 gate — [../09-delivery/01-build-roadmap.md](../09-delivery/01-build-roadmap.md)):
- 5 pillar explainers (FRRO guide; corporate housing explainer; relocation-services-in-India overview; serviced apartments vs hotels; visa-types-for-foreign-workers)
- 7 hub-city pages (city program tier 1)
- 3 thought-leadership posts (authored by leadership)
- Legal/transparency pages written in plain language (privacy, cookies, pricing approach, how we work)
- Core service pages complete per copy templates ([06-copy-templates.md](06-copy-templates.md))
- 1 original per priority locale (ja, ko) to seed localized authority

## 7. KPIs (content that must prove itself)
| KPI | Target (12 months) |
|---|---|
| Organic sessions | baseline → 3–5× growth (quarterly reviewed) |
| Money-page assisted conversions | contact/quote form entries from organic |
| City program pages ranking | 25+ pages in top 20 for their city queries |
| AEO presence | cited/quoted in AI answers for 10+ core queries ([05-aeo-llm-presence.md](05-aeo-llm-presence.md) §7) |
| Newsletter | 1,000 confirmed subscribers |
| Refresh rate | 100% of top-20 pages refreshed quarterly |

---

Related: [02-seo-technical.md](02-seo-technical.md) · [03-city-content-program.md](03-city-content-program.md) · [04-multilingual-content.md](04-multilingual-content.md) · [05-aeo-llm-presence.md](05-aeo-llm-presence.md) · [06-copy-templates.md](06-copy-templates.md) · [../04-modules/07-blog-news.md](../04-modules/07-blog-news.md) · Reference audit: [../02-formula-reference/05-seo-content-analysis.md](../02-formula-reference/05-seo-content-analysis.md)
