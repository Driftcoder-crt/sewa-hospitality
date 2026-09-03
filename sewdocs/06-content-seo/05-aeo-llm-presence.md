# 05 — AEO / LLM Presence (Generative Engine Optimization)

**Being the answer when people ask ChatGPT, Gemini, Perplexity, or any future AI assistant about relocation to India — a channel the reference platform completely ignores.**

---

## 1. Why this is a real channel
Relocation decisions are question-shaped ("What is FRRO registration?", "How much does corporate housing in Pune cost?", "Best relocation company in India"). AI engines now answer many of these before the user ever sees a search result. The reference optimizes for 2015-era meta tags (revisit-after, DC.*) and has zero AEO surface. Sewa treats AI engines as a first-class audience from day one.

## 2. The three-layer AEO strategy

### Layer 1 — Citable content (on-page)
1. **Answer-first openings:** every post/guide/city page starts with a 2–4 sentence direct answer of its title question ([01-content-strategy.md](01-content-strategy.md) §2) — this is what LLMs quote.
2. **Question-mapped blocks:** FAQ blocks built from actual harvested questions (PAA, AI probes, community threads — [03-city-content-program.md](03-city-content-program.md) §3) with `FAQPage` schema ([02-seo-technical.md](02-seo-technical.md) §4).
3. **Extractable facts:** rates, timelines, document lists, and steps in clean HTML (tables, ordered lists) — not locked in images, PDFs, or JS-rendered accordions (the reference hides service scope inside duplicated accordions — extract it into semantic content).
4. **Entity clarity:** consistent NAP, sameAs socials, named authors, and an unambiguous "what Sewa does" statement in the Organization schema — LLMs disambiguate via entities.
5. **Freshness signals:** `article:modified` on refreshes; dated stats ("as of August 2026") — LLMs and their retrieval layers prefer recent, verifiable sources.

### Layer 2 — Machine access (crawlability)
1. **robots.txt explicitly allows reputable AI crawlers** (GPTBot, ClaudeBot, Google-Extended, PerplexityBot, CCBot as policy allows — a deliberate, documented decision, reviewed with legal per [../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) data posture; blocking = invisible in AI answers).
2. **`/llms.txt`** at the root: a curated, Markdown index of what Sewa is + canonical links to the best answers (services, FRRO guide, city program, housing standards, pricing approach). Maintained by the CMS (auto-generated from a curated block list).
3. **Full-content RSS `/feed`** — AI ingestors love feeds; the reference has none.
4. **Clean semantic HTML + one H1** (AEO and SEO are the same discipline — single source).
5. **Speakable-ready structure:** clearly labeled steps/definitions where voice assistants might pull.

### Layer 3 — Presence building (off-page)
1. **Be the source, not just the site:** publish original data (city cost-of-living tables, FRRO processing timelines, housing rate bands) that journalists/communities cite — citable data earns LLM mentions.
2. **Q&A presence:** answer relevant questions on Quora/Reddit/expat groups linking to the canonical guide (brand-voice rules apply).
3. **Wikipedia-adjacent legitimacy:** organization facts (founded, HQ, services) consistent across LinkedIn, GBP, directories — entity graphs feed LLM ground truth ([../07-marketing-trust/03-trust-authority.md](../07-marketing-trust/03-trust-authority.md)).
4. **Review-entity strength:** structured review presence (GBP, [../04-modules/08-testimonials-reviews.md](../04-modules/08-testimonials-reviews.md)) — "best relocation company" LLM answers weight review signals.

## 3. Content formats built for extraction
| Format | Example | Why |
|---|---|---|
| Definitive explainers | "FRRO registration: steps, documents, timelines" | the canonical answer target |
| Comparison tables | "Serviced apartments vs corporate housing vs hotels" | structured → quoted |
| City data sheets | rent bands, school lists, FRRO office addresses | extractable facts |
| Checklists | "Documents for a foreign national renting in Pune" | list-shaped = AI-legible |
| Glossary | India mobility terms (FRRO, OCI, PAN, society, brokerage) | entity vocabulary control |

## 4. Anti-patterns (never do)
- Publishing AI-raw content to "win AI" — thin, uncited content hurts both rankings and trust ([../08-ai-system/02-ai-use-cases.md](../08-ai-system/02-ai-use-cases.md) usage policy).
- Hiding answers behind login/accordions/menus.
- Fabricated stats for extractability (claims discipline applies — [01-content-strategy.md](01-content-strategy.md) §2).
- Fake FAQ schema that doesn't match visible content (same rule as reviews schema).

## 5. Measurement
- **AI probe panel (monthly, scripted):** fixed set of 25 questions asked to ChatGPT/Gemini/Perplexity (fresh sessions); log cited domains + answer content; track Sewa presence rate over time (KPI in [01-content-strategy.md](01-content-strategy.md) §7).
- **Referral monitoring:** AI-engine referrers (chat.openai.com, perplexity.ai, gemini) in analytics ([../07-marketing-trust/02-analytics-plan.md](../07-marketing-trust/02-analytics-plan.md)) as a distinct channel.
- **Zero-result + unanswered-question backfeed:** questions the probes ask where no good Sewa answer exists → content backlog tickets (the flywheel).

## 6. Phase-2 options (documented, not built)
- Self-hosted "ask Sewa" AI assistant on site (RAG over the city/FAQ corpus) using the same Laravel AI SDK + hosted embeddings — spec sketch in [../08-ai-system/02-ai-use-cases.md](../08-ai-system/02-ai-use-cases.md); build only after organic AEO baseline proves the corpus quality.

---

Related: [02-seo-technical.md](02-seo-technical.md) · [03-city-content-program.md](03-city-content-program.md) · [../07-marketing-trust/03-trust-authority.md](../07-marketing-trust/03-trust-authority.md) · [../07-marketing-trust/02-analytics-plan.md](../07-marketing-trust/02-analytics-plan.md) · [../08-ai-system/02-ai-use-cases.md](../08-ai-system/02-ai-use-cases.md)
