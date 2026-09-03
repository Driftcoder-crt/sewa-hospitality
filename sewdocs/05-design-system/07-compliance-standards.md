# 07 — Compliance & Standards (Performance · Google · Web Platform · A11y)

**The binding quality bar: Google's Core Web Vitals + SEO guidelines, MDN/Firefox web-platform standards, Lighthouse-class performance ("lightspeed"), and WCAG 2.2 AA accessibility — all enforced as CI gates, not aspirations. Every rule below is checkable by machine.**

"Following lightspeed, google, seo, firefox levels of all guidelines" is interpreted as four concrete standards bodies, each mapped to gates in [../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md) §2:

| Standard | Authoritative source | Our gate |
|---|---|---|
| Performance & CWV | Google web.dev (Core Web Vitals, Lighthouse budgets) | §3 budgets + CI gate 5 |
| SEO guidelines | Google Search Central (Search Essentials, structured data, hreflang, sitemaps) | §5 + publish gates + CI gate 4 |
| Web platform & cross-browser | MDN Web Docs / Firefox compatibility standards (Baseline, progressive enhancement) | §4 + CI gate 6 |
| Accessibility | WCAG 2.2 AA (+ Google's a11y guidance) | §6 + CI gate + theme-engine contrast matrix |

---

## 1. The "lightspeed" budget stack (Lighthouse-class, hard numbers)

**Lighthouse targets (production URLs, mobile preset, 4G throttle, p75):**

| Metric | Target | Hard fail |
|---|---|---|
| Performance score | ≥ 90 | < 85 |
| Accessibility score | = 100 | < 100 |
| Best Practices | = 100 | < 95 |
| SEO (Lighthouse) | = 100 | < 95 |
| LCP | ≤ 2.0s target / 2.5s max (CWV "good") | > 4.0s |
| INP | ≤ 200ms | > 500ms |
| CLS | ≤ 0.05 | > 0.1 |
| TTFB (edge) | ≤ 200ms cached (Cloudflare) | > 600ms |

**Resource budgets (public pages, uncompressed → brotli):**
| Resource | Budget |
|---|---|
| HTML document | ≤ 60 KB |
| CSS (one Tailwind build) | ≤ 60 KB brotli |
| JS (Alpine + Livewire runtime + site.js) | ≤ 120 KB brotli (public); ≤ 200 KB (portal/admin) |
| Fonts | 2 families max, 4 files max, subset, ≤ 160 KB total, `font-display: swap`, ≤ 2 preloaded |
| Hero media | ≤ 200 KB (AVIF/WebP via pipeline) |
| Above-fold media total | ≤ 400 KB |
| Third-party JS on public pages | **0 pre-consent** (GTM gated; Turnstile only on form pages, lazy-injected) |
| DOM nodes per page | ≤ 1,500 (block composer warns at 1,200) |
| Images total per page (lazy) | ≤ 2.5 MB |
| Server: DB queries per render | ≤ 30 (strict mode logs exceedances to Pulse) |

**Mobile-first device floor:** tested at 390×844 (iPhone-class), 360×800 (budget Android), 768 (tablet) — see §7.

## 2. How we actually hit those numbers (mechanisms, not hopes)

| Technique | Where specified |
|---|---|
| Edge-cached anonymous HTML (Cloudflare + response cache, tag-purged) | [../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) §2.5 |
| AVIF/WebP conversions with immutable hash URLs | [../03-technical-specs/09-media-pipeline.md](../03-technical-specs/09-media-pipeline.md) |
| Zero-JS theming (CSS tokens; no flash guard inline) | [04-theme-engine.md](04-theme-engine.md) §6 |
| Islands interactivity (no SPA hydration tax) | [../03-technical-specs/11-realtime.md](../03-technical-specs/11-realtime.md) §4 |
| Video/map/lightbox facades (media on intent) | [03-ux-interactions.md](03-ux-interactions.md) §3 |
| Content-visibility: auto on long sections | this doc §4 |
| Font subsetting + preload discipline | [01-brand-guidelines.md](01-brand-guidelines.md) §4 |
| ETag/304 on API + page responses | [../03-technical-specs/04-api-spec.md](../03-technical-specs/04-api-spec.md) §1 |
| Budget accounting in the block composer (sums block weights) | [05-section-block-library.md](05-section-block-library.md) §8 |

## 3. Google Search Essentials (SEO compliance, the official rulebook)

| Google requirement | Sewa implementation | Enforcement |
|---|---|---|
| Unique titles/descriptions per URL | per-page-type templates + entity overrides | publish gate + CI gate 4 |
| One H1, semantic headings | template rule | seo:audit daily + CI |
| Canonicals, clean, self-referential | Seo\Meta service | CI snapshot |
| Sitemap: complete, <50k URLs/file, dated, clean | sitemap_index + child maps ([../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md) §3) | nightly generator + membership test |
| robots.txt correct, no accidental blocking | managed centrally (§robots.txt block) | seo:audit |
| Structured data matches visible content (no rating schema without visible reviews) | Schema\Graph generator + honest-reviews policy | golden tests |
| hreflang bidirectional + x-default | locale paths, alternates generator | I18n suite |
| No cloaking/sneaky redirects | N/A by design | — |
| CWV as ranking input | §1 budgets | field monitoring (CrUX + RUM below) |
| Mobile-friendliness | §7 mobile-first contract | CI |
| Multilingual | locale paths (not params/cookies) — Google's stated preference | I18n suite |

**Field monitoring:** CrUX API checked monthly for real-user p75 (LCP/INP/CLS); plus a tiny consent-gated RUM beacon (LCP/INP/CLS) feeding GA4 events — lab (Lighthouse) for regression, field (CrUX/RUM) for truth.

## 4. MDN / Firefox web-platform standards (Baseline discipline)

| Standard | Rule |
|---|---|
| **Baseline "Newly available" ceiling** | Features used must be Baseline "Widely available" (supported in last 2 versions of all evergreen engines: Chrome, Edge, Firefox, Safari). Newer features (e.g. `:has()` pre-2023) require @supports progressive fallback |
| **Progressive enhancement core** | HTML works without JS (forms submit server-side, nav is real links, content is SSR) — Livewire/Alpine enhance, never gate |
| **CSS logical properties everywhere** | `ps-/pe-/ms-/me-/start/end` — never physical L/R (RTL contract with [../04-modules/11-multilingual.md](../04-modules/11-multilingual.md)) |
| **oklch + sRGB fallback** | `@supports (color: oklch(0 0 0))` branches ship sRGB hex fallbacks (Firefox/older Safari) — automated by the token compiler |
| **Native dialogs & popovers** | `<dialog>`, `popover` API where Baseline-OK; focus-trap always |
| **CSS containment/`content-visibility`** | `content-visibility: auto` + `contain-intrinsic-size` on below-fold sections (render cost without CLS) |
| **No vendor prefixes** | Except `-webkit-line-clamp` class utilities until Baseline (wrapped in one utility class) |
| **prefers-reduced-motion** | Global media-query kill-switch for all animation ([03-ux-interactions.md](03-ux-interactions.md) §3) |
| **View Transitions API (optional)** | Off by default; only for locale/theme transitions with reduced-motion + Firefox fallback (graceful fade) |
| **Cross-browser CI matrix** | Playwright smoke suite: Chromium + **Firefox** + WebKit at 390/768/1280 |

## 5. Compliance gates wired into CI ([../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md) §2)

1. **Gate 4 (SEO):** titles/meta present + non-placeholder, single H1, canonical, hreflang set, JSON-LD parses + matches page type, sitemap membership for all published entities.
2. **Gate 5 (Performance):** budgets §1; Lighthouse CI on 4 canonical URLs per surface (home, service, city, post) × mobile preset; failing thresholds block the release.
3. **Gate 6 (Standards):** token lint (semantic-only classes), logical-property lint, Baseline feature whitelist scan, @supports fallback presence check.
4. **Gate 7 (A11y):** axe-core on the same 4 URLs × 4 themes × 2 directions; zero critical violations.
5. **Post-deploy:** CWV field check via CrUX within 48h of launch + monthly.

## 6. Accessibility contract (WCAG 2.2 AA summary)

Full detail: [03-ux-interactions.md](03-ux-interactions.md) §5. Binding extras from this audit cycle: 44px minimum targets (the anti-Taj lesson — 243 undersized targets is the cautionary tale), contrast enforced by the theme engine ([04-theme-engine.md](04-theme-engine.md) §6), focus-visible on every interactive element, keyboard-complete blocks, reduced-motion global.

## 7. Mobile-first & ultra-responsive contract (the anti-regression rules)

**Design order: 390px first, enhance upward.** Every block is specced mobile-first then adapted at 768/1024/1280+ (not shrunk down).

| Rule | Value |
|---|---|
| Breakpoints (Tailwind defaults only — no ad-hoc) | 640 / 768 / 1024 / 1280 / 1536 |
| Zero horizontal overflow | any element causing scrollWidth > viewport fails CI (wild-ag proves it's possible with media-heavy pages) |
| Tap targets | ≥ 44×44px hit areas; links inside text get padded hit zones |
| Body type on mobile | 16px minimum; 17–18px for long-form (wild-ag's 18.3px mobile body is the evidence) |
| Fluid type | clamp() per role; display 40–88px; H1 ≥ 40px on mobile for hero, ≥ 24px for page titles |
| Stretched images | prohibited — object-cover with aspect boxes (17 stretched imgs at Taj = the cautionary example); CI counts `object-fit: fill` on sized media |
| Sticky/mobile nav | ≤ 77px compact header (evidence: wild-ag 77px); hamburger + bottom CTA bar optional |
| Images per viewport | ≤ 3 above fold; lazy below; explicit sizes/srcset from the media pipeline |
| No hover-gated content | tap/click/focus equivalents always (banners, accordions, dropdowns) |
| Form fields | 16px+ font (iOS zoom-guard), input mode hints (tel/email), autocomplete tokens |
| Safe areas | env(safe-area-inset-*) honored by sticky bars |
| Orientation | layouts valid 360→1920, portrait + landscape mobile |

## 8. Luxury-feel performance techniques (quality ≠ heavy)

The 10-site audit shows luxury = restraint + motion-taste, not payload. Sewa's luxury mechanisms at zero/budget cost: generous whitespace (padding scale), serif display type with tight tracking, editorial near-sharp radii, subtle scroll-reveals (transform/opacity only, ≤ 400ms), quiet hover states, cinematic full-bleed AVIF imagery (one per page, not five), number-driven serif stats. **None of these add a single KB of JS.**

## 9. Reporting cadence
Weekly: Lighthouse CI summary in ops digest. Monthly: CrUX p75 + budget exceptions review. Quarterly: full §1–§7 re-audit + Cross-browser matrix re-run; findings → CHANGELOG.

---

Related: [03-ux-interactions.md](03-ux-interactions.md) · [04-theme-engine.md](04-theme-engine.md) · [01-brand-guidelines.md](01-brand-guidelines.md) · [../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md) · [../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md) · [06-reference-sites-analysis.md](06-reference-sites-analysis.md)
