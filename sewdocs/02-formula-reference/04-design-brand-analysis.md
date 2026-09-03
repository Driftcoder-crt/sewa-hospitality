# 04 — Reference Design & Brand Analysis

**The reference platform's design system, extracted completely — so Sewa can match its coverage while replacing its identity.**

---

## 1. Design tokens (reference, exact)

| Token | Value | Usage |
|---|---|---|
| Primary red | `#DF1E26` | links, selection, primary buttons, pagination current |
| Red family | `#DA1E26` (27 uses), `#D01E26`, `#E31F26`, `#A81318` (hover), `#ee3b2e`, `#fa183d` | accents, badges |
| Page background | `#FEEBE2` (warm blush) | `body` background sitewide |
| Body text | `#120f10` (near-black) | body color |
| Muted text | `#808080`, `#777`, `#999` | meta, excerpts |
| Greys | `#DDDEDF`, `#dddddd`, `#F1F2F2`, `#F4F4F4`, `#f0e9ff`, `#fbf9ff` | borders, panels |
| White | `#fff` | on-red surfaces, cards |
| Buttons | radius 6px, `#DF1E26` bg, hover `#A81318` | all CTAs |
| Border radius | 6px buttons; cards rounded | sitewide |
| Transitions | 0.3s ease on all `a, button` | global |
| Scrollbar | 6px custom, `#d9d9d9` track | desktop chrome |
| Spacing scale | custom `.mb-1 … .mb-10` (overrides Bootstrap) | sitewide |

**Typography:**

| Role | Font | Notes |
|---|---|---|
| Body | Poppins (ital/wght 100–900 full set) | 14px/500 base; `.p-text` 15px/400 |
| Display/headings | Bebas Neue | hero H1s, section numbers |
| Secondary | Montserrat | loaded alongside; used in portal/login |
| Icons | Font Awesome 5 (73 KB CSS) + 5-icon custom font | socials, meta icons |

**Hero system:** 100vh (min 700px) three-column triptych, absolute-centered uppercase 40px H1s, scroll chevrons, desktop/mobile image swaps, center animated GIF.

**Breakpoints:** Bootstrap 4 grid (576/768/992/1200) + custom overrides at 600, 768, 800, 900, 1100, 1199, 991.98 — i.e., Bootstrap defaults plus ~7 ad-hoc overrides (drift discipline is weak).

**CSS payload:** `styles.css` loader imports bootstrap.min.css (144 KB), custom.css (108 KB), all.css FA (73 KB), aos.css (26 KB), fancybox.css (15 KB), swiper CSS (14 KB), owl.carousel (legacy, unused) — ≈ 918 KB across 8 files. Page rendering depends on ~3 fonts with full weight ranges.

## 2. Brand identity (reference)

| Element | Value |
|---|---|
| Brand | Formula Group (legal: Formula Corporate Solutions India Pvt. Ltd., founded 2004) |
| Product/platform | "MobiRelo" (mobility tech) |
| Tagline | "A Formula For your Relocation" (blog); "human-centric technology platform" (copy) |
| Stats shown | 20000+ happy clients · 25+ cities · 99% satisfaction · 500+ GPS fleet vehicles · 20 Fortune 100 clients · 9.9/10 from 1024 reviews (self-declared Product schema) |
| Sister ventures | 7 external sites (housing, moving, serviced apartments, car rental, travel, sanitization, Japan) |
| Contact identity | +91-9650003642 sitewide (all offices) · enquiry@formulaindia.com · HQ 27, Community Centre, East of Kailash, New Delhi 110065 |
| Socials | Facebook, Twitter, Instagram, LinkedIn, YouTube |
| Trust strip | 20 membership/partner logos in footer |
| Logo system | light/dark/wordmark variants + mobile light variant + footer white version + favicon |

## 3. What Sewa deliberately takes vs. replaces

### Taken (structure, not identity)
- Page anatomy rhythm (hero → intro → blocks → CTA band → footer).
- The stats-band pattern (counters) — with honest, dated, sourced values.
- Services-as-two-families information architecture.
- Testimonial card grammar (quote + service + city).
- Membership strip concept (only badges actually held).
- Office-tabs contact pattern.

### Replaced (identity + craft)
- **Palette:** Sewa's own palette — warm hospitality base + confident accent, deliberately distinct from red-on-blush (avoid any visual echo of the competitor; final tokens in [../05-design-system/01-brand-guidelines.md](../05-design-system/01-brand-guidelines.md)).
- **Fonts:** one humanist type system, subset, self-hosted (3-font Poppins/Bebas/Montserrat load replaced with ~2 files).
- **Red-pill button idiom:** Sewa's component library defines its own button scale + states (hover/active/focus/disabled/loading) — the reference has no focus-visible styles.
- **Icons:** inline SVG subset replaces FA5 73 KB CSS.
- **Photography:** authentic India-context people photography (the reference mixes stock + real inconsistently).
- **The 3R animated banner:** replaced by Sewa's own motion identity (see [../05-design-system/03-ux-interactions.md](../05-design-system/03-ux-interactions.md)).

### Fixed (craft debt)
- ~918 KB CSS → one Tailwind build (~30–60 KB/page group).
- 3 fonts full-range → one family, subset, `font-display: swap`.
- Ad-hoc breakpoint overrides → Tailwind's default scale only.
- No design-token layer → Tailwind 4 `@theme` tokens as the single source of truth (color/type/spacing/radius/motion), consumed by HTML templates, email templates, and future sister sites alike.

## 4. Per-page design anatomy notes (reference)

- **Home:** the triptych is distinctive but heavy (GIF + 2 large JPEGs + video poster); no headline hierarchy above the fold for search intent ("Employee MOBILITY"/"Business MOBILITY" are the only texts).
- **Service leaf pages:** hero image + giant H1 + intro + accordion grid; CTA band. Dense but unremarkable; accordions duplicated in DOM.
- **About:** alternating image/text rows + leader grid with hover bios — strong content, weak a11y (bio only visible on hover).
- **Careers:** accordion job list + modal apply + big gallery — good energy; no per-job pages (404s).
- **Contact:** tabs of 9 offices; maps iframes eager-load (8 embeds fire on one page — performance + privacy issue).
- **Clients Speak:** static masonry of quote cards; no sources or dates.
- **Blog (WP theme):** shares the portal's design (Bootstrap 4 + same fonts) but diverges from the Next.js site's components — **two design systems drift visibly** between main site and blog.
- **Login:** red login box over photo hero; matches the blog theme more than the main site.

**Sewa rule:** one design system renders marketing site, blog, portals, and emails. Any component exists once (see [../05-design-system/02-ui-components.md](../05-design-system/02-ui-components.md)).

## 5. Accessibility & performance audit summary (reference)

- No skip-link, no focus-visible styles, hover-only content, CSS-background imagery without alt, empty `<html lang>` on WP pages, color-contrast issues on blush backgrounds — **fails WCAG 2.2 AA on multiple counts.**
- Performance: 918 KB CSS, 2.5 MB hero PNG on About, eager YouTube embed, eager Maps iframes ×8, 3 font families full-range, AOS/waypoints/countUp jQuery stack, GIF animation, ~179 CDN images with no WebP — **no Core Web Vitals discipline.**
- Sewa targets: WCAG 2.2 AA + performance budgets per page type ([../05-design-system/03-ux-interactions.md](../05-design-system/03-ux-interactions.md) + [../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md) gates).

## 6. Brand asset checklist the reference implies (Sewa must produce equivalents)

| Reference asset | Sewa equivalent (owner: design phase) |
|---|---|
| Logo set (5 files + favicon) | Full logo system incl. inverse/mono/favicon/social crops |
| 3R animated banner (GIF) | Sewa motion mark (Lottie/webm, poster fallback) |
| Per-page banner photos | Art-directed photo set per page type |
| 20 membership logos | Only-what-we-hold strip (start: none or incorporator badges; grow via [../07-marketing-trust/03-trust-authority.md](../07-marketing-trust/03-trust-authority.md)) |
| 11 service hero images | Sewa service imagery set (photography shot list) |
| MobiRelo 9 icons | Portal feature iconography (inline SVG) |
| Japan flag button | Locale switcher (flags optional; names preferred) |
| 39 career-gallery photos | Real Sewa team/culture photography (phased) |

---

Related: [01-site-map-and-pages.md](01-site-map-and-pages.md) · [02-components-interactions.md](02-components-interactions.md) · [05-seo-content-analysis.md](05-seo-content-analysis.md) · Sewa system: [../05-design-system/01-brand-guidelines.md](../05-design-system/01-brand-guidelines.md)
