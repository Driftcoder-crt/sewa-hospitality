# 04 — Growth Roadmap (Marketing)

**The 12-month growth plan: launch → traction → authority — sequenced, budget-aware (free-tier-first), and instrumented, so marketing is a system the team runs, not a set of hopes.**

---

## Phase L — Launch (weeks 0–4)
**Goal:** discoverable, credible, measurable.

| Track | Actions |
|---|---|
| SEO base | sitemap/robots live + GSC verified; schema graph live; wave-1 city pages (7) + launch content set ([../06-content-seo/01-content-strategy.md](../06-content-seo/01-content-strategy.md) §6) published |
| GBP | category fix + services + photos + Q&A + weekly posts begin ([01-google-ecosystem.md](01-google-ecosystem.md) §3) |
| Analytics | GTM + GA4 + Consent Mode + server lead events + dashboards live ([02-analytics-plan.md](02-analytics-plan.md)) |
| Trust | transparency pages (pricing approach, how-we-work, verified checklist, data page), people pages, review engine armed |
| Social | LinkedIn company page (B2B primary), socials claimed with consistent NAP/handles |
| Distribution | brand video on YouTube (facade-embedded), launch post on LinkedIn, newsletter to initial list |
| Ads | brand-campaign only (protect the brand SERP); service ads deferred until 2–4 weeks of funnel data exists |

**Exit criteria:** site indexed (GSC coverage clean), first leads in CRM with sources tagged, SLA timer running, review engine triggered by staging-completed test move.

## Phase T — Traction (months 2–4)
**Goal:** repeatable demand + first proof.

| Track | Actions |
|---|---|
| SEO | city wave 2 (6 cities); 2 pillar explainers/mo; refresh cadence begins; zero-result backlog grooming |
| AEO | AI-probe panel running monthly; llms.txt live; FAQ coverage on money pages ([../06-content-seo/05-aeo-llm-presence.md](../06-content-seo/05-aeo-llm-presence.md)) |
| i18n | ja/ko core + wave-1 city pages live (human-reviewed); localized lead flow verified end-to-end ([../06-content-seo/04-multilingual-content.md](../06-content-seo/04-multilingual-content.md)) |
| Ads (start) | one service cluster (corporate housing or relocation) + city cluster; conversion = lead; weekly CPL review; scale rule: keep only ad groups with CPL ≤ 2× target |
| Reviews | 25–40 reviews target; every review responded; first case story published |
| Partnerships | 5–10 corporate-agent/relocation-network referral intros (channel tracked in CRM with source tags) |
| Ops marketing | newsletter issue #1; GB posts weekly; LinkedIn 2 posts/week (mix: city data, how-we-work, team) |

**Exit criteria:** ≥ 3 organic lead entries/day avg; ja/ko leads present; CPL data per cluster; first reviews visible on service pages.

## Phase A — Authority (months 5–12)
**Goal:** own the city + mobility conversation; inbound compounding.

| Track | Actions |
|---|---|
| SEO | city waves 3–4 (to 25+ cities); data-led studies (rent-band report per hub city, FRRO timeline report) → PR-able assets; refresh program quarterly on all money pages |
| AEO | presence KPI tracked (cited in ≥ 10 core probe questions); glossary + comparison table hub complete |
| i18n | tr/ar core live; original locale guides (ja/ko monthly); consider zh/vi if demand signals |
| Ads | scale winners (service × city clusters); localized ja/ko ad groups; remarketing (consent-gated) at volume |
| Trust | certifications roadmap milestones ([03-trust-authority.md](03-trust-authority.md) §2) hit as ops mature; 100+ reviews at 4.7★+; second/third case stories |
| Events | 1–2 industry presences (EuRA-class or local corporate-mobility meetups) — real-world entity building |
| Channels | partnerships program (agents/corporate travel desks) formalized with tracked referrals |

**Exit criteria (12 months):** 3–5× organic baseline, 20%+ non-EN organic share, 100+ reviews, CPL target met on 2+ ad clusters, presence in AI answers on core queries.

## Budget posture (free-tier-first, per platform doctrine)
| Layer | Cost at launch |
|---|---|
| Analytics/monitoring/email/CDN/realtime | ₹0 (free tiers, per [../03-technical-specs/01-stack-and-dependencies.md](../03-technical-specs/01-stack-and-dependencies.md)) |
| Ads | controlled monthly cap, reviewed weekly against CPL |
| Content production | internal (consultant co-author model) + design phase one-time |
| Certifications (Phase A) | budgeted separately when ops qualify |
Escalation rule: any paid upgrade must cite the free-tier limit being hit (budget guards — [../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) §2.3) and the revenue it unblocks.

## Weekly growth operating rhythm
```
Mon: ops digest review (leads/SLA/queue)        → act on breaches
Tue: leads pipeline stand-up                     → assignments, nurture touches
Wed: content sprint (city/guide)                 → per calendar
Thu: GBP post + LinkedIn; review responses       → always same-day
Fri: numbers (dashboards): CPL, funnel, AEO panel → adjust next week
```

## Dependencies & risks
| Risk | Mitigation |
|---|---|
| Shared-hosting limits meet a growth spike | Cloudflare edge absorbs anonymous traffic; VPS triggers documented ([../09-delivery/02-future-scaling.md](../09-delivery/02-future-scaling.md)) |
| Review velocity slow | service-recovery feedback loop improves experience first; never buy reviews ([03-trust-authority.md](03-trust-authority.md) §8) |
| AI answers suppress click-through | AEO program makes Sewa the cited source; brand SERP campaign protects navigational demand |
| ja/ko review-queue latency | reviewer capacity planned in Phase T hiring ([../04-modules/11-multilingual.md](../04-modules/11-multilingual.md) §4) |

---

Related: [01-google-ecosystem.md](01-google-ecosystem.md) · [02-analytics-plan.md](02-analytics-plan.md) · [03-trust-authority.md](03-trust-authority.md) · [../06-content-seo/01-content-strategy.md](../06-content-seo/01-content-strategy.md) · [../06-content-seo/03-city-content-program.md](../06-content-seo/03-city-content-program.md) · [../09-delivery/01-build-roadmap.md](../09-delivery/01-build-roadmap.md)
