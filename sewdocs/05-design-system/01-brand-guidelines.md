# 01 — Brand Guidelines (Sewa Hospitality)

**The visual identity: warm-paper luxury — warm near-black ink, deep teal action color, restrained warm-bronze accent (luxury ≠ gold), Fraunces serif display + Inter body, honest photography, and calm motion — coherent across site, portals, admin, email, and future ventures.**

**Evidence base:** every decision in §§3–4 is derived from the 10-site design audit — see [06-reference-sites-analysis.md](06-reference-sites-analysis.md) for the extracted data. Centralized smart theming (dark bg → light text, admin-controlled, contrast-enforced) is specified in [04-theme-engine.md](04-theme-engine.md).

---

## 1. Brand foundation
- Name usage, tagline system, voice: [../01-platform-vision/02-brand-sewa-hospitality.md](../01-platform-vision/02-brand-sewa-hospitality.md).
- Design principles: **Warm-precise** (care language + concrete facts), **Calm confidence** (generous space, few strong colors), **Honest** (real photos, dated stats, no decorative flourish hiding empty substance), **One system** (every surface renders the same tokens).

## 2. Logo system
| Asset | Spec |
|---|---|
| Primary wordmark | "SEWA HOSPITALITY" — humanist caps, letter-spaced; used on light backgrounds |
| Inverse | for dark/photo backgrounds |
| Monogram | optional sewa-knot/hands mark for avatars, favicons, app icons |
| Clear space | 0.5× the height of "S" on all sides; min sizes: 24px digital, 12mm print |
| Files | SVG source; PNG exports per media pipeline ([../03-technical-specs/09-media-pipeline.md](../03-technical-specs/09-media-pipeline.md) `brand/` namespace) |
| Don'ts | no stretching, recoloring, drop shadows, or placing on low-contrast imagery |

## 3. Color tokens v2 (Tailwind 4 `@theme` + the theme engine — single source of truth)

**Refined by the 10-site evidence audit** ([06-reference-sites-analysis.md](06-reference-sites-analysis.md)): warm neutrals never pure white/black (8/10 sites), warm near-black ink as the Indian-luxury signal (Taj `#45443f`, DLF `#1f1a17`), one restrained accent, oklch as the color space (surinder/nomu). Full pair system + dark/brand/deep themes live in [04-theme-engine.md](04-theme-engine.md); tokens below are the primitives.

| Token | Value | Role |
|---|---|---|
| `--sewa-teal-500` (primary) | **#0E7C66** deep teal (oklch 0.45 0.085 172) | brand actions, links — warm, trustworthy, distinct from every competitor reference |
| `--sewa-teal-600` | #0A5F4F | hover/active |
| `--sewa-teal-400` | #16A085 | on dark surfaces |
| `--bronze-500` (accent) | **#C9974C** warm bronze (oklch 0.68 0.09 75) | rare highlights, verified badges — warm, **never gold gradients** (brand rule) |
| `--bronze-400` | #D4A860 | on dark surfaces |
| `--ink-900` | **#26201A** warm near-black (Taj/DFL-family warm ink; never `#000`) | primary text |
| `--ink-700` | #4A413A | secondary text |
| `--ink-500` | #736960 | muted/meta text |
| `--sand-0` | **#FAF7F2** warm paper (page background — never `#fff` base) | page/surface base |
| `--sand-1` | #F3EDE4 | raised surfaces/cards |
| `--sand-2` | #E9E1D5 | sunken surfaces/borders |
| `--clay-400` | #B8A896 | warm borders, eyebrow labels |
| Semantic | success #2E7D32 · warning #B26A00 · danger #C0392B · info #1F618D | states sitewide |

Rules: teal carries all interactive meaning; bronze is an accent at ≤5% surface area (badges, rules, numerals) — luxury is restraint; text-on-token pairs are defined by the theme engine and **contrast-validated at publish** ([04-theme-engine.md](04-theme-engine.md) §6); full tint ramps (50–900) in the token file; never pure white `#fff` as a page base nor `#000` as text — warm neutrals only ([06-reference-sites-analysis.md](06-reference-sites-analysis.md) rule 1).

## 4. Typography v2 (evidence-decided — final, not deferred)
| Role | Face | Weights | Notes |
|---|---|---|---|
| Display + headings | **Fraunces Variable** (serif, opsz axis) | 400/500/600 | the luxury editorial move proven at 5/10 reference sites (Inria/Fraunces/Lora/Georgia); tight tracking -0.02→-0.04em; orionix's 72px/-3px formula is the ceiling reference |
| Body + UI | **Inter** | 400/500/600 | the consensus body face (6/10 sites); full i18n pairing quality |
| Eyebrow/labels | Inter uppercase | 500 | 11–12px, letter-spacing 0.14em — the editorial label pattern |
| ja/ko native render | system stack (Hiragino/Noto Sans JP/KR) | 400/500 | native-script pages use system stacks for quality |

- Two families, 2 files each max (variable), self-hosted, subset, `font-display: swap`, 2 preloaded weights per surface.
- Fluid scale (clamp, mobile-first): 12 / 14 (UI) / **17–18 body mobile** (wild-ag's 18.3px mobile body is the evidence) / 20 / 24 / 32 / 48 / 64 / **88 max display**; line-heights 1.6 body, 1.05–1.1 display.
- Display serif + tight negative tracking on large sizes; wide tracking only on uppercase eyebrows. **Never 3 font families** (the reference loads Poppins + Bebas + Montserrat).
- Numerals: Fraunces lining numerals for stats (the "quiet luxury" detail wild-ag/orionix both use).

## 5. Iconography
- Single inline SVG sprite (~30 core icons: services ×11, contact, doc, chat, move, check, verified, locale, socials ×5, ui states).
- Style: 1.5px stroke, 24px grid, rounded joins, matches Inter's roundness; stroke = currentColor (theme-aware, no recolored duplicates).
- No icon fonts, no emoji-as-UI (FA5's 73 KB CSS dies here).

## 6. Photography & imagery
| Do | Don't |
|---|---|
| Real people in real Indian contexts: families in new homes, consultants at work, drivers, cityscapes with warmth | cold stock corridors, generic handshake stock |
| Natural light, warm grading consistent with sand/bronze palette | teal-cast filters, HDR city porn |
| Documentary texture for CSR/careers; clean editorial for services/markets | mixing visual registers randomly |
| Alt text discipline per media pipeline ([../03-technical-specs/09-media-pipeline.md](../03-technical-specs/09-media-pipeline.md) §4) | CSS-background images with no alt |

Shot list (for the photography phase): hero triptych equivalents, 11 service heroes, hub-city imagery (7), housing interiors ×3 tiers, consultants/portraits, fleet/duty-of-care detail, CSR moments, office (DT Mega Mall) exterior + interior for GBP.

## 7. Motion identity
- Motion principles: calm, purposeful, 150–400ms, transform/opacity only, stagger ≤ 100ms, `prefers-reduced-motion` honored globally.
- Brand motion mark (replaces the reference's 3R GIF): a subtle "S-curve draw" (SVG path animation ≤ 300KB poster fallback; Lottie/webm candidates) — used sparingly: home hero + preloader only.
- Counters: honest values with "as of" microcopy, ease-out count-up on intersect.

## 8. Email & document branding
- Email templates use the same tokens (inlined, table-based fallback) — [../03-technical-specs/10-email.md](../03-technical-specs/10-email.md).
- Invoice/quote PDF templates carry the same identity (Billing module) — one brand on every artifact a client touches.

## 9. Token file (source of truth)
`resources/css/tokens.css` — the `@theme` block consumed by every surface (site/portal/admin/email via a build step). Any change here is a design review + visual regression snapshot ([../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md) §2 gate 6). Sister/venture sites, if ever promoted ([../01-platform-vision/04-subdomains-ventures.md](../01-platform-vision/04-subdomains-ventures.md) §4), consume the same file — coherence by construction.

---

Related: [02-ui-components.md](02-ui-components.md) · [03-ux-interactions.md](03-ux-interactions.md) · [../01-platform-vision/02-brand-sewa-hospitality.md](../01-platform-vision/02-brand-sewa-hospitality.md) · Reference analysis: [../02-formula-reference/04-design-brand-analysis.md](../02-formula-reference/04-design-brand-analysis.md)
