# 04 — Multilingual Content Strategy

**Serving Korean, Japanese, Turkish, and Arabic (plus Hindi and more) clients properly — original localized content for priority markets, AI-assisted translation pipelines for scale, and locale-specific SEO beyond Google.**

---

## 1. Market priorities (who Sewa actually serves in-language)

| Locale | Why | Depth at launch |
|---|---|---|
| `ja` Japanese | The reference literally hires Japan-desk officers (N2/N3) — Japanese corporate relocations are a proven revenue segment. Japanese buyers expect native-language vendor sites. | High: core pages + city wave-1 + original guides |
| `ko` Korean | Korean corporates (auto/electronics/manufacturing) relocate teams to India; Korean clients research vendors in Korean. | High: same scope as ja |
| `ar` Arabic | Saudi/Gulf clients (business + family stays); Arabic = RTL, needs the full logical-property discipline. | Medium: core pages + services + housing |
| `tr` Turkish | Turkish business travel and SME entries into India; Turkish clients ask in Turkish. | Medium |
| `hi` Hindi | Domestic corporate market + India-wide trust signal; Hindi pages also differentiate for Indian HR buyers. | Medium: core + key guides |
| Future (fr, de, ru, zh, vi…) | one settings row + translation pipeline away ([../04-modules/11-multilingual.md](../04-modules/11-multilingual.md) §1) | on demand |

## 2. Two-tier content model
1. **Localized originals (top tier):** content *written for the market* — e.g. "Indian business etiquette for Japanese managers", "Gurugram's Korean community: groceries, schools, interpreters", "Hajj/Umrah-period travel notes for Saudi visitors to India". These are E-E-A-T originals by market-aware authors — not translations. 1/month per priority locale.
2. **Translated core (everything else):** services, cities, housing, key guides through the AI-assisted + human-reviewed pipeline ([../04-modules/11-multilingual.md](../04-modules/11-multilingual.md) §4). Machine output never publishes without review (or configured auto-publish for low-risk types only).

## 3. Register & style per locale (binding for reviewers)
| Locale | Register rules |
|---|---|
| ja | polite/formal (です・ます), honorific client references, no casual contractions; dates 日本語 format; ₹ noted with yen context where helpful |
| ko | 합쇼체 formal; clear 글로벌 모빌리티 terminology; explain India-specific terms on first use |
| ar | Modern Standard Arabic, formal hospitality tone; RTL layout verified; numerals reviewed (Eastern Arabic optional — reviewer decides for consistency) |
| tr | formal "siz"; transparent loanword handling; India-specific terms glossed |
| hi | professional Hindi; English terms kept where industry-standard (मोबिलिटी, एचआर) |

Common rules: explain India-specific concepts (FRRO, PAN, maid culture, society flats) rather than transliterating blindly; CJK/RTL typography checked at 100%; every locale has a named human reviewer before first publish.

## 4. Locale SEO
- hreflang done right: locale paths + alternates + x-default ([02-seo-technical.md](02-seo-technical.md) §2); one sitemap covers all.
- `og:locale` variants; localized titles/descriptions templates per locale (not machine-English leftovers).
- **Beyond Google (market truth):** Japanese buyers often start at Yahoo! Japan; Korean users lean on Naver — the program notes that the *content quality + citability* (AEO: [05-aeo-llm-presence.md](05-aeo-llm-presence.md)) matters more than submitting to those engines; a future decision on Naver-era specifics is tracked in [../09-delivery/02-future-scaling.md](../09-delivery/02-future-scaling.md).
- Localized schema: same JSON-LD types, localized names/descriptions; `inLanguage` per page.

## 5. Detection & conversion wiring (product side, for reference)
Auto-detection (Accept-Language/geo hint) → suggestion banner → cookie ([../04-modules/11-multilingual.md](../04-modules/11-multilingual.md) §3). Forms record locale; ack email in lead language when a reviewed translation exists. Consultant briefing notes the preferred language; the CRM shows it prominently. (The reference's only concession is a Japan flag linking away from the domain.)

## 6. Translation QA bar (what "good" means)
- Side-by-side admin review with terminology glossary per locale (FRRO/FRRO등록/FRRO登録 etc. standardized once).
- 100% of machine output reviewed before publish for: legal/compliance pages, pricing-adjacent copy, anything with numbers (rates, SLAs).
- Auto-publish allowlist (configurable): navigation labels, simple UI strings after initial review rounds stabilize quality.
- Quarterly spot-audit: native speaker reviews random 10 pages per locale; scores logged.

## 7. KPIs
| KPI | Target (12 months) |
|---|---|
| ja/ko core+city coverage | 100% of wave-1 pages live |
| Localized organic sessions | 20%+ of total organic by month 12 |
| Localized lead share | leads with locale≠en ≥ 15% |
| Review queue latency | < 5 business days median |
| ja/ko original guides | 12 each |

---

Related: [../04-modules/11-multilingual.md](../04-modules/11-multilingual.md) (mechanics) · [01-content-strategy.md](01-content-strategy.md) · [02-seo-technical.md](02-seo-technical.md) · [05-aeo-llm-presence.md](05-aeo-llm-presence.md) · [../08-ai-system/02-ai-use-cases.md](../08-ai-system/02-ai-use-cases.md) · [../01-platform-vision/02-brand-sewa-hospitality.md](../01-platform-vision/02-brand-sewa-hospitality.md) §3 (voice)
