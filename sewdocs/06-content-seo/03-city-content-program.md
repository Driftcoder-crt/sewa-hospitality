# 03 — All-India City Content Program

**The systematized, scalable city program: identify what people actually search and ask per city, publish answer-first city pages, and grow coverage across all major Indian cities — turning the reference's one-off 2021 series into a permanent growth engine.**

---

## 1. Why this program wins
The reference's strongest content asset is its 8-post city-guide series (Mumbai, Bengaluru, Delhi, Ahmedabad, Hyderabad, Chennai, Pune, GIFT City) — published once in 2021 and never refreshed. City queries ("relocation services Pune", "serviced apartments Gurugram", "schools in Bengaluru for expats") are exactly where commercial intent meets long-tail volume. Sewa builds the *machine* for that demand: structured city pages (module: [../04-modules/10-cities-content.md](../04-modules/10-cities-content.md)) + editorial guides + inventory, in priority waves.

## 2. Priority waves (25+ cities, phased)

| Wave | Cities | Why |
|---|---|---|
| 1 (launch) | Gurugram, New Delhi, Mumbai, Bengaluru, Pune, Hyderabad, Chennai | business hubs where Sewa operates; reference already proves demand |
| 2 | Ahmedabad, Kolkata, Noida, Greater Noida, Ghaziabad, Faridabad, Jaipur | NCR/industrial expansion + Ahmedabad (reference covered it — GIFT City adjacency) |
| 3 | Surat, Vadodara, Lucknow, Indore, Chandigarh, Kochi, Coimbatore, Bhubaneswar | emerging GCC/manufacturing destinations |
| 4 | Visakhapatnam, Nagpur, Bhopal, Mysuru, Mangaluru, Thiruvananthapuram, Dehradun | long tail + regional credibility |
| Watchlist | GIFT City (dedicated page — reference's highest-value niche), Shillong/NE, Tier-3 industrial corridors | as demand signals appear |

Each wave: city page + 2–3 guides + inventory (or "coverage with partner inventory" honesty note) + localized metas.

## 3. Demand research method (repeatable, per city)

**Step 1 — Harvest what people actually ask:**
- Google: People-Also-Ask chains, autocomplete expansions ("relocating to pune …"), related searches, "vs" queries
- AI engines: ChatGPT/Gemini/Perplexity probes ("best relocation company in {city}", "FRRO office in {city}", "cost of living in {city} for expats") — record what's cited and what's missing (feeds [05-aeo-llm-presence.md](05-aeo-llm-presence.md))
- Communities: Reddit (r/India, r/expats, r/movingtoindia), Facebook expat groups, Quora — real question phrasing
- Sewa's own data: zero-result searches ([../03-technical-specs/08-search.md](../03-technical-specs/08-search.md) §3), lead notes (CRM), consultant call notes — **the highest-converting source we own**
- Reference/competitor coverage map: what exists is the floor, not the ceiling ([../02-formula-reference/05-seo-content-analysis.md](../02-formula-reference/05-seo-content-analysis.md) §4)

**Step 2 — Cluster into the city template's blocks:**

| Cluster (per city) | Example queries | City-page block |
|---|---|---|
| Overview & verdict | "relocating to {city}", "is {city} good for expats", "{city} vs {city}" | intro + snapshot |
| Housing & neighborhoods | "best areas to live in {city}", "rent in {city} 2BHK", "serviced apartments {city}", "corporate housing {city}" | neighborhoods + housing inventory + market note |
| Serviced/corporate housing | "furnished apartments {city} short stay", "guest house {city}" | inventory cards + verified standard |
| Schools & family | "best international schools {city}", "school admission for expat children {city}" | family block |
| Healthcare & safety | "best hospitals {city}", "is {city} safe" | practical block |
| Transport & fleet | "car rental {city} monthly", "driver service {city}", "traffic/commute {city}" | fleet block + internal links |
| Immigration/FRRO | "FRRO office {city}", "FRRO registration process" (city-specific offices!) | compliance block |
| Cost of living | "cost of living in {city} for expat family" | numbers box (sourced, as-of) |
| Practicalities | "utilities setup {city}", "maid/domestic help {city}", "bank account for foreigners" | checklists |
| Japanese/Korean-specific | "{city} Japanese community", "Korean grocery {city}", "interpreters {city}" | international-community block (unique to Sewa — the reference runs a Japan desk but has zero city content for it) |

**Step 3 — Prioritize by commercial intent:** housing/serviced-apartments/FRRO first (Sewa sells those), then family/schools (drives employee-facing trust), then lifestyle long-tail.

## 4. The city page template (money page)
Structure defined in module spec ([../04-modules/10-cities-content.md](../04-modules/10-cities-content.md) §3); content rules:
- Answer-first intro (2–4 sentences: who the city is for, cost level, Sewa's coverage).
- Every block scannable: tables for costs (with as-of dates), bulleted checklists, neighborhood cards with rent bands.
- **Honesty layer:** claims about inventory/rates carry "as of" and vary-notes; cities where coverage is partner-based say so.
- FAQ block (6–10 questions harvested in Step 1) with FAQPage schema.
- CTA per cluster (housing → availability form; FRRO → consultation).
- Bidirectional links to services + posts + testimonials (the money web — [02-seo-technical.md](02-seo-technical.md) §5).

## 5. Production line (how this scales without a big team)
```
Research sprint (per wave): 1 day/city → cluster sheet → brief per page (AI-assisted
brief compilation [../08-ai-system/02-ai-use-cases.md](../08-ai-system/02-ai-use-cases.md),
human-verified facts) → author draft (named consultant co-author = E-E-A-T + real knowledge)
→ editor review (voice/structure/claims) → publish → 90-day performance review → refresh or deepen
```
- Co-author rule: city pages signed by a Sewa consultant who works that city — real experience is the moat the reference can't copy by AI-generating content.
- Data freshness: rent bands and FRRO details re-checked quarterly (refresh queue in calendar).
- Locale waves: wave-1 city pages get ja/ko originals first (Japanese/Korean corporate relocations concentrate in exactly these cities).

## 6. KPIs
| KPI | Target |
|---|---|
| City pages live | 7 (launch) → 25 (12 months) |
| Top-20 rankings | ≥ 60% of wave-1 pages for their head city queries in 6 months |
| Assisted conversions | city-page → housing/quote form CTR tracked ([../07-marketing-trust/02-analytics-plan.md](../07-marketing-trust/02-analytics-plan.md)) |
| Refresh discipline | 100% of wave-1 pages refreshed quarterly |
| Zero-result gap closure | site-search zero-result rate < 3% of searches by month 9 |

---

Related: [01-content-strategy.md](01-content-strategy.md) · [02-seo-technical.md](02-seo-technical.md) · [04-multilingual-content.md](04-multilingual-content.md) · [05-aeo-llm-presence.md](05-aeo-llm-presence.md) · [../04-modules/10-cities-content.md](../04-modules/10-cities-content.md) · [../07-marketing-trust/02-analytics-plan.md](../07-marketing-trust/02-analytics-plan.md)
