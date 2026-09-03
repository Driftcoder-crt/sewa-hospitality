# AUDIT REPORT — sewdocs v1.0

**Audit date:** 2026-09-01 · **Auditor:** full-suite self-audit (programmatic + manual) · **Scope:** all 56 markdown files in this suite.

---

## Verdict

**PASS — with 11 defects found and fixed during this audit.** The suite is structurally sound (758/758 cross-links resolve, zero orphans, zero placeholders), factually consistent (brand, NAP, stack, KPIs, and policy statements agree across all sections), accurate against the source mirror on every spot-checked claim, and fully covers the 30 explicit requirements in the project brief. One intentional class of items remains open: build-time decisions deliberately deferred (listed in §5) — these are documented choices, not gaps.

---

## 1. Structural integrity — CLEAN

| Check | Result |
|---|---|
| Inventory | 56 files (55 docs + README), 52,741 words, 9 sections |
| Internal links | 758 checked → **0 broken** (4 found pre-audit were fixed; 1 new link added with the Sentry allowlist fix and re-verified) |
| Orphan documents | 0 (every doc is referenced by at least one other doc) |
| Heading structure | Every document has exactly one `# ` H1 |
| Placeholder scan | 0 hits for TBD / TODO / FIXME / Lorem |
| Master index | README lists all 56 files with correct paths; no stale "44-doc" counts remain (found & fixed) |

Least-referenced docs (healthy, not orphaned): `09-csr-module.md` (4 inbound), `02-reference/03-api-and-data-layer.md` (5), `02-reference/04-design-brand-analysis.md` (5).

## 2. Fact consistency — CLEAN after fixes

| Check | Result |
|---|---|
| Company name | "SEWA HOSPITALITY SERVICES PVT. LTD." used consistently in 3 defining docs; brand shorthand in 11 — consistent |
| Competitor-name leakage | "Formula Corporate Solutions" appears **only** inside Section 02 (reference context) — correct |
| Address | MS0228 / DT Mega Mall / DLF Phase 1 consistent in all 3 occurrences |
| Phone | Found **two format variants** (`+91 98732 55531` display vs `+91-9873255531` in brand JSON) → **fixed**: display format locked to `+91 98732 55531`, machine/E.164 format locked to `+919873255531`, rule written into the brand doc NAP section |
| Competitor-phone leakage | `9650003642` appears only in Section 02 — correct |
| Domain | `sewahospitality.com` in 18 docs; `formulaindia.com` appears only in reference/decision context — correct |
| Tracking IDs | Reference IDs (GA4, Clarity, FB Pixel, UA) appear **only** in Section 02 — zero leakage into Sewa specs |
| KPI consistency | 4.3★/11 baseline and 4.7★+/100-in-12-months target stated identically in 8 docs |
| SLA values | contact 2 business hours / quote 4 / callback 2 — consistent across Leads module, copy templates, trust doc |
| AI model | `z-ai/glm-5.3-free` spelled identically in 7 docs; TokenRouter/OpenRouter OpenAI-compatible framing consistent |

## 3. Stack-policy consistency — CLEAN after fixes

| Check | Result |
|---|---|
| "Livewire 3" / "Laravel 12" / "Tailwind 3" stale references | **0** — all docs say Livewire 4 / Laravel 13.x (^13.21) / Tailwind 4.3.x |
| Filament mentions | 5 docs — all are exclusion/rationale statements (correct usage), zero usage specs |
| WordPress/Bootstrap/jQuery/Next.js mentions outside Section 02 | All are replacement/exclusion statements in stack, architecture, CMS, design docs — correct usage |
| Pusher mentions | 3 docs — all in "why Ably instead" decision records — correct |
| Redis/Meilisearch/Typesense mentions | All in "not at launch / Phase-2 / exit-path" contexts — consistent with shared-hosting constraint |

## 4. Accuracy vs. source mirror — VERIFIED

Spot-checks of Section 02 claims against the actual mirror (`C:\ser\formulaz\www.formulaindia.com`):

| Claim in docs | Mirror says | Verdict |
|---|---|---|
| Homepage counters 20000+ / 25+ / 99% | `<span class="counter">20000+`, `25+`, `99%` | ✅ |
| 45 blog posts (1+8+11+2+8+4+6+5) | Per-year dir counts: exactly 1,8,11,2,8,4,6,5 | ✅ |
| 15 categories (12 top-level + 3 nested) | 12 top-level dirs; nested pairs confirmed | ✅ |
| 11 services + broken `wellbeing-support` | employee-mobility has 6 service subdirs + wellbeing-support; business-mobility has 5 | ✅ |
| Reference phone +91-9650003642 sitewide | Present in contact page | ✅ |
| Reference title/keywords/contact meta claims | Verified in contact page head | ✅ |

## 5. Defects found & fixed during this audit (11)

| # | Severity | Location | Defect → Fix |
|---|---|---|---|
| 1 | Medium | `03-technical-specs/01-stack-and-dependencies.md` §2.5 | Drafting artifact `` `beberlei/doctrinemerciful? no` `` inside the vetted-additions rule → removed; sentence rewritten to point at §3 allowlist |
| 2 | Medium | `06-content-seo/02-seo-technical.md` robots.txt block | Draft notes inside the code block (`/cart* (n/a)`, `Allow tab URLs? no — canonical handles`) → replaced with a valid, commented robots.txt |
| 3 | Medium | `03-technical-specs/07-queues-scheduling.md` + `04-modules/03-leads-crm.md` | Command typo `slam:calculate` → `sla:calculate` (both occurrences) |
| 4 | Medium | `04-modules/00-module-system.md` §1 vs `03-technical-specs/02-architecture.md` + exec summary | Module-count contradiction: "13 functional modules" vs "14 bounded modules" vs "Fourteen modules" → unified to **14 modules** everywhere |
| 5 | Medium | `04-modules/00-module-system.md` §6 | Build-order spine (M0–M6) mismatched the roadmap doc's milestones (M0–M7) and placed CMS in M0 / mobile-contract at M6 → realigned to the roadmap's M0–M7 sequence exactly |
| 6 | Low | `01-platform-vision/02-brand-sewa-hospitality.md` §9 | Phone format in machine-readable JSON was `+91-9873255531` (non-standard) → E.164 `+919873255531` + explicit format-lock rule added (display vs machine) |
| 7 | Low | `03-technical-specs/01-stack-and-dependencies.md` §3 allowlist | Sentry (`sentry/sentry-laravel`) was required by `12-monitoring.md` and `05-security-reliability.md` but missing from the composer allowlist → added with justification |
| 8 | Low | `README.md`, exec summary, CHANGELOG | Stale document count "44 documents" vs actual 56 files (55 docs + README) → corrected in all 3 locations |
| 9 | Cosmetic | `01-platform-vision/01-executive-summary.md` §7 | Doc-count phrasing tightened (55 docs + README) |
| 10 | Cosmetic | `09-delivery/CHANGELOG.md` | Same count fix |
| 11 | Cosmetic | 4 broken relative links found in the pre-audit pass | Fixed and re-verified (758/758 clean) |

## 6. Requirements coverage — 30/30 explicit brief items addressed

Verified every requirement from the project brief maps to at least one spec section: deep teardown of every page/file/functionality (Section 02); brand identity (01/05); features (03/04); UI/UX/interactions (05); how data is written (02-03 + API spec); how content is written and where (06); all SEO (06-02); complete platform documentation (all); "same but much better" as Sewa Hospitality (weaknesses→fixes matrix); Laravel latest with all possible features (03-01); Tailwind throughout (01/03/05); shared-hosting limits honored (03-06); commercial grade with error locks and proper divides (03-05, 04-00); highly documented (the suite); no primitive/problematic dependencies, Filament excluded (03-01 §6); realtime (03-11); Vite local builds (03-01/03-06); proper subdomains (01-04); advanced/commercial SEO (06-02); "think bigger" — platform through marketing/HR/CSR/Google-trust/LLM presence (01/04/06/07/08); identify everything possible (roadmaps + Phase-2 option registers); mobile-app future-proofing (04-13, frozen v1 contract); Sewa company details embedded (01-02); all-India city content with what-people-search/AI-ask research (06-03, 06-05); Korean/Japanese/Turkish/Saudi+ auto-detection and multilingual serving (04-11, 06-04, 08-02); z-ai/glm-5.3-free via TokenRouter/OpenRouter OpenAI-compatible endpoint (08-01 §2, quoting the exact endpoint shape); multi-provider model flexibility (08-01 provider-agnostic config); AI where needed with fallbacks (08-02); Google ecosystem incl. tags/tools/Search Console/Ads (07-01); finance/billing (04-12).

## 7. Intentionally open decisions (documented, not defects)

These are deliberate build-time choices, each already flagged in its doc with a trigger/decision owner:

1. **Resend vs Brevo** — both specced side-by-side; pick one at M0 setup ([10-email.md](03-technical-specs/10-email.md) §2).
2. **Display font Sora vs Inter Tight** — ~~final pick at design phase~~ **RESOLVED in v1.1**: Fraunces Variable serif display + Inter body, decided from 10-site evidence ([05-design-system/01-brand-guidelines.md](05-design-system/01-brand-guidelines.md) §4).
3. **Membership badges at launch** — start with none unless already held ([07/03](07-marketing-trust/03-trust-authority.md) §2 hard rule).
4. **Comments system** — off by default; enable is a config decision ([04/07](04-modules/07-blog-news.md) §5).
5. **Typesense Cloud vs Meilisearch Cloud** — decided only when search-scale triggers fire ([03/08](03-technical-specs/08-search.md) §5).
6. **AI auto-publish allowlist scope** — configurable, expands only after translation quality stabilizes ([04/11](04-modules/11-multilingual.md) §4).
7. **GBP primary category** — final Google-taxonomy label confirmed at the GBP edit itself ([07/01](07-marketing-trust/01-google-ecosystem.md) §3.1).

## 8. Residual limitations & risks (honest notes)

1. **Mirror staleness:** Section 02 describes the reference site as of the 2026-08-31 mirror snapshot. The live site may have changed since; teardown claims were verified against the mirror only. Impact on the build: none (the rebuild targets the Sewa spec, not the mirror).
2. **Version pins inherited from brief:** Laravel 13.21 / Livewire 4 (Jan 2026) / Tailwind 4.3.x / Ably's 6M-message free tier / Hostinger's no-Redis state were supplied as current facts in the project brief. The docs pin these as caret ranges (`^13.21`, `^4.0`, `^4.3`) precisely so minor upstream drift cannot break the spec — but re-verify the four provider/hosting facts at M0 before provisioning.
3. **Single-auditor bias:** this audit was performed by the same agent that wrote the suite. The programmatic checks (links, counts, leakage greps) are objective; the coverage judgment (§6) is a self-assessment and should be sanity-checked by a second reader before build start.
4. **No visual review yet:** design-system docs (palette, type, components) are spec-only; the true test is the `/dev/components` preview at M1 — the QA gates ([13-testing-qa.md](03-technical-specs/13-testing-qa.md)) will enforce them there.

## 9. Post-audit state

- Links: **758/758 clean** · placeholders: **0** · artifacts: **0** · contradictions found: **all resolved**
- CHANGELOG updated (v1.0 entry notes the audit and fixes).

---

*Audit method: programmatic link-graph validation (Python), section-scoped grep sweeps for leakage/consistency/artifacts, fact spot-checks against the source mirror, manual read-through of cross-cutting claims, and requirements-coverage matrix against the project brief.*
