# 06 — Reference Sites Analysis (10-Site Design Audit)

**Method:** Each site was loaded in a real browser and audited *programmatically* — extracted actual design tokens (CSS custom properties), computed palettes (frequency-weighted colors), typography (font families, sizes, letter-spacing, transforms), component counts, library fingerprints, overlay/popup inventory, and mobile-viewport behavior (390×844). This is exact data from the rendered pages, not visual guesses.

**Why these sites:** agency-luxury (wild-ag, hgd-media, orionix, surinder), e-commerce-modern (nomu, brigadeoverland), event-institutional (kortrijkxpo), Swiss-hospitality (lesaintgeorges), and the two Indian benchmarks most relevant to Sewa — **Taj Hotels** (luxury hospitality) and **DLF** (real estate; Sewa's HQ sits in DLF Phase 1).

---

## 1. Per-site findings

### 1.1 wild-ag.ch — Swiss real-estate developer · the "quiet luxury" benchmark
| Dimension | Extracted fact |
|---|---|
| Palette | `#faf9f4` cream bg · `#f5f1ed` light · `#cbbba0` beige · `#a48a7a` brown · `#6b6561` grey text · `#2b221c` warm near-black |
| Typography | **Inria Serif** headings + **Inter** body · uppercase H1, letter-spacing -0.15px · fluid em-based scale (`--main-size: 1.51vw`, display 7.06em) |
| Tokens | Full CSS-var system incl. `--beige --black --brown --grey --light --white` + fluid font/padding vars |
| Radii | 0px (sharp editorial) with `4.33em` pills reserved for specific elements |
| Stack | Webflow + GSAP + Lottie |
| Components | 15 sections · 61 images · 25 inline SVGs · 2 videos · popup with dedicated close affordance |
| Mobile (390px) | body type scales **up to 18.3px** · H1 40px · **zero horizontal overflow** · sticky header 77px · hamburger · 43/51 tap targets ≥40px |
| **Sewa takes** | Warm-neutral luxury without gold; serif+sans pairing; fluid type; sharp editorial radii; their popup is a well-branded full-screen moment (we do the same for locale/lead prompts) |

### 1.2 nomu.store — merch e-commerce · the modern-token benchmark
| Dimension | Extracted fact |
|---|---|
| Palette | `#fff9f6` warm-white bg · **`#ff7448` coral primary** · `#ff8d69/#ffa88d/#ffc8b7` highlight ramp · `#0d1117/#1b232e` dark surfaces · `#d3e1ff` periwinkle secondary · `#f7f5ee` paper-cream |
| Tokens | **Complete semantic token set** (shadcn-style): `--background --foreground --primary --secondary --accent --muted --card --popover + *-foreground` pairs |
| Typography | Inter body + custom "nomnom" display · H1 60px · radii ramp 24/42/48/50/58px |
| Stack | Next.js + Shopify + Locomotive scroll · oklab()/lab() modern CSS colors |
| Mobile | Tailwind `lg:hidden` mobile menu · H1 25px · zero overflow |
| **Sewa takes** | The semantic token naming architecture (`--x` + `--x-foreground` pairs) — this is the backbone of our smart theme engine; pill-radius CTAs |

### 1.3 kortrijkxpo.com/en — expo center · the bold-brand benchmark
|---|---|
| Palette | **`#d2251f` red** + **`#500404` deep maroon text** + `#141414` near-black on white · `#d6d6d6` greys |
| Typography | **Urbanist** (geometric sans) · fluid H1 (75.65px desktop, computed) · 30px radii |
| Stack | Swiper · Fancybox (light-box with its own `--f-button-*` token block) |
| Notable | Full-screen **search overlay** pattern (`page-overlay` with own header); marquee-style news ribbons; bold color-blocking sections |
| **Sewa takes** | Deep-brand-dark text color (maroon ≈ our deep-teal-ink idea); the full-screen overlay menu pattern; visible, tokenized third-party widget theming |

### 1.4 lesaintgeorges.ch — Swiss hotel & restaurant · the hospitality benchmark
|---|---|
| Palette | White base · **`#472300` warm brown** brand color · `#f4f4f4` light grey · `#222` charcoal |
| Typography | **Lora (serif) headings + Avenir Next (sans) body** · H1 36px · near-zero radii (2px) |
| Stack | WordPress + Swiper + Locomotive |
| Notable | Cookie consent bar (CookieYes); 17 carousel instances; 15 sections; restrained imagery (35 imgs) |
| **Sewa takes** | Serif display in a *hospitality* context works; extreme restraint (2 colors doing all the work); tiny radii for timeless feel |

### 1.5 hgd-media.de — German digital agency · the light/dark-rhythm benchmark
|---|---|
| Palette | `#f5f5f7` light bg · **`#dc5930` terracotta** accent · `#0c0b0b` near-black dark sections |
| Typography | Inter throughout · H1 56px · radii 8–15px |
| Stack | WordPress + GSAP + Swiper + **Lenis** + Lottie |
| **Sewa takes** | The alternating light/terracotta/dark section rhythm (their dark sections are near-black ×18 — text flips white automatically — exactly the smart-theme behavior we formalize); terracotta proves a warm accent survives luxury contexts |

### 1.6 orionix.framer.website — creative studio · the editorial-serif benchmark
|---|---|
| Palette | `#f9f8f6` warm off-white · **pure red accent** · `#141414` near-black · full grey ramp (#111827→#f3f4f6) |
| Typography | **Fraunces Variable (serif) headings** · H1 **72px with letter-spacing -2.98px** (!) · sans body |
| Stack | Framer + Lenis · 3 videos |
| **Sewa takes** | THE display-type formula: large serif + tight negative tracking = instant editorial luxury; generous whitespace; content-reveal on scroll |

### 1.7 surinder.design — AI product designer · the token-modernity benchmark
|---|---|
| Palette | `#fbf9f4` warm white · near-black primary · **green `#47ab19` accent** · olive-white `#e6ebdc` · dark slate `#20212b` |
| Tokens | **Entire token set in `oklch()`** (shadcn-style semantic pairs) — the most modern CSS of all 10 |
| Typography | Inter headings (48px, -0.48px) + Mukta body · 8px radii |
| **Sewa takes** | oklch() as our color space (perceptual uniformity = reliable contrast math for the theme engine); proof that near-black-primary + one accent reads both premium and product-grade |

### 1.8 brigadeoverland.com — premium off-road supplies · the dark-heritage benchmark
|---|---|
| Palette | **Dark-first: `#1a1a1a` charcoal** + `#e7e6dd` bone · **`#b43c32` rust red** accent · `#beb69e` desert tan |
| Typography | **Special Gothic Condensed One** display (uppercase, 66px) + **Geist** body · Tailwind 4 (`--tw-*` runtime vars, `--spacing`) |
| Stack | Shopify + Tailwind CSS 4 |
| **Sewa takes** | Dark-theme done warmly (charcoal not pure black, bone text); Tailwind 4 token confirmation; condensed uppercase display for strong eyebrows/section labels (we use Inter-wide-tracked instead — see [01-brand-guidelines.md](01-brand-guidelines.md)) |

### 1.9 tajhotels.com/en-in — THE Indian luxury-hospitality benchmark
|---|---|
| Palette | White base · **`#45443f` warm near-black text** · **`#ad8b3a` muted gold** accent · `#f6f5f5` light grey panels · `#13130f` deep black |
| Typography | Inter/InterNeue · H1 only **32px** (restraint) · 104 headings, 153 images, 88 SVGs |
| Stack | Next.js + Bootstrap + React/MUI components |
| Mobile (390px) | H1 24px · zero horizontal overflow · **but 243/269 tap targets under 40px and 17 stretched images — enterprise ≠ mobile-perfect** |
| **Sewa takes** | The warm-near-black ink (`#45443f`-family) as text color is the single most "Indian luxury" signal on the web; their restraint (small H1, muted accent) proves luxury is quiet. **We deliberately do NOT take:** the gold accent (per brand directive: luxury ≠ gold) and their small tap targets (we enforce 44px minimum) |

### 1.10 dlf.in — Indian real-estate giant · the Indian-corporate benchmark
|---|---|
| Palette | White base · **`#1f1a17` warm near-black** · `#909090` grey text · `#d0d0d0` borders |
| Typography | **Georgia (serif) headings** + HelveticaNeue-Light body · H1 20px (very corporate-restrained) · 50px hero numerals |
| Stack | Bootstrap + Fancybox |
| Notable | Video banner hero · modal popup on load (the "delay/popup" behavior the brief warned about) |
| **Sewa takes** | Georgia proves system-serif works for Indian corporate gravitas; warm-black-on-white as the corporate tone. **We exceed:** serif display with real type rigor, no modal-on-load interruption pattern |

---

## 2. Cross-site synthesis — the 12 evidence-backed rules

| # | Pattern | Evidence (sites) | Sewa adoption |
|---|---|---|---|
| 1 | **Warm neutrals, never pure white / pure black** | 8/10 use warm-tinted whites (#faf9f4→#fbf9f4) and warm near-blacks (#2b221c, #45443f, #1f1a17, #1a1a1a) | Paper `#FAF7F2` family, ink `#26201A` family — never `#000` on `#fff` |
| 2 | **Serif display + sans body = luxury editorial** | wild-ag (Inria), orionix (Fraunces), lesaintgeorges (Lora), dlf (Georgia), taj print-pairing | **Fraunces Variable** display + **Inter** body ([01-brand-guidelines.md](01-brand-guidelines.md) §4) |
| 3 | **Big display + tight negative tracking** | orionix 72px/-2.98px · surinder -0.48px · wild-ag -0.15px | Display scale to 88px max, tracking -0.02→-0.04em |
| 4 | **Uppercase micro-labels with wide tracking** | wild-ag (uppercase H1s/labels), brigade (condensed uppercase), kortrijk | Eyebrow style: Inter 11–12px, 0.14em tracking, uppercase |
| 5 | **Fluid type scales** (em/vw/clamp) | wild-ag (1.51vw main-size), kortrijk (computed fluid H1) | clamp()-based scale; mobile body 17–18px (wild-ag mobile = 18.3px) |
| 6 | **One accent + one deep brand-dark, sparsely** | every site: red (kortrijk/orionix), coral (nomu), terracotta (hgd), green (surinder), rust (brigade), gold (taj), brown (lesaintgeorges) | Deep teal primary + warm bronze accent (never gradients of "metal") |
| 7 | **Semantic token architecture with foreground pairs** | nomu + surinder (shadcn-style `--x`/`--x-foreground`), oklch() in surinder/nomu | Theme engine tokens in oklch with paper/ink pairs ([04-theme-engine.md](04-theme-engine.md)) |
| 8 | **Dark sections auto-flip text** | hgd (near-black sections ×18), brigade (dark-first), taj (#45443f panels) | Formalized as `data-theme` token-pair inversion + `color-scheme` |
| 9 | **Scroll-reveal + smooth motion is table stakes — but optional-native** | GSAP 3×, Lenis 3×, Locomotive 3×, Lottie 3× | Native scroll + Alpine `x-intersect` reveals; NO scroll-hijacking (CWV/a11y rule); Lenis-style smoothness only if flagged on and reduced-motion-safe |
| 10 | **Editorial radii: near-sharp; pills for CTAs only** | wild-ag/lesaintgeorges/orionix/dlf ≈0–2px · nomu 24–58px · hgd/surinder 8px | 2–6px surfaces + pill CTAs — mixed intentionally per component class |
| 11 | **Popup/consent moments exist on 5/10 — brand them beautifully** | wild-ag (styled popup), dlf (modal), lesaintgeorges (cookie bar), kortrijk (overlay) | Our overlays (locale prompt, cookie consent, exit-lead) are designed brand moments with proper focus management |
| 12 | **Mobile-first proof, not claim** | wild-ag: 18px body, zero overflow, 43/51 good targets · **taj: 243 undersized targets, 17 stretched imgs (fail)** | Sewa enforces: 44px targets, aspect-boxed images, zero-overflow CI gate |

## 3. What we explicitly reject (with reasons)

| Rejected pattern | Seen at | Reason |
|---|---|---|
| Gold/metallic gradients as "luxury" | taj (`#ad8b3a` — tasteful there, but cliché-risk as primary accent) | Brand directive: **luxury ≠ gold**. Sewa's warmth comes from sand/paper tones + bronze accents used at ≤5% surface area |
| Scroll-hijacked smooth-scroll as default | 6/10 sites ship Lenis/Locomotive/GSAP ScrollTrigger | CWV risk, reduced-motion hostility, and shared-hosting JS budget; we achieve motion quality through reveals, not hijacking (flag-gated option documented) |
| Modal/popup interrupting first paint | dlf (modal on load), several cookie walls | Interrupts LCP and UX; our consent/locale prompts are non-blocking, below-fold-anchored or delayed ≥ intent signal |
| Undersized tap targets | taj (243), nomu (25) | WCAG 2.2 target-size + mobile-first directive: 44px minimum enforced in CI |
| Pure white/black palettes | nomu (near), taj panels | Cold and clinical; the warm-neutral rule (#1) is the luxury differentiator |
| Heavy hero videos autoplaying | several | Data cost on Indian mobile networks + LCP discipline; ours are facade-clicked ([03-ux-interactions.md](03-ux-interactions.md)) |

## 4. Direct design decisions output (inputs to the other design docs)

1. **Palette v2** (evidence-refined, in [01-brand-guidelines.md](01-brand-guidelines.md)): warm paper base, warm near-black ink, deep teal brand, warm bronze accent, sand/clay neutrals — oklch-defined.
2. **Type v2**: Fraunces Variable display + Inter body + tracked uppercase eyebrows; fluid clamp scale; mobile-first sizes.
3. **Theme engine**: semantic tokens with paper/ink pairs per theme + admin Theme panel + smart auto-contrast ([04-theme-engine.md](04-theme-engine.md)).
4. **Block library v2**: 17 → 47 sections/blocks incl. promotional set ([05-section-block-library.md](05-section-block-library.md)).
5. **Compliance**: performance/standards gates synthesizing all of the above ([07-compliance-standards.md](07-compliance-standards.md)).

---

Related: [01-brand-guidelines.md](01-brand-guidelines.md) · [04-theme-engine.md](04-theme-engine.md) · [05-section-block-library.md](05-section-block-library.md) · [07-compliance-standards.md](07-compliance-standards.md) · [03-ux-interactions.md](03-ux-interactions.md)
