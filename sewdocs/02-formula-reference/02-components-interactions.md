# 02 — Reference Components & Interactions

**Every interactive element on the reference platform, how it behaves, and how Sewa re-specifies it.**

---

## 1. Interaction inventory

| # | Element | Where | Behavior (reference) | Sewa re-spec |
|---|---|---|---|---|
| 1 | AOS scroll animations | All pages | `data-aos="fade-up"` duration 500 on cards/columns | IntersectionObserver-based reveal (no 26 KB lib); respects `prefers-reduced-motion` |
| 2 | Animated counters | Homepage | jQuery countUp + waypoints on `.counter` spans: 20000+, 25+, 99% | Livewire/Alpine count-up component; values CMS-editable with "as of" date for honesty |
| 3 | Slide-out sidenav | All pages | Hamburger opens 320px sidenav; dropdown buttons expand nested groups | Same pattern (nav is genuinely good) as accessible off-canvas; keyboard + focus-trap; mega-menu on desktop |
| 4 | Hero smooth-scroll chevrons | Home, categories | `.mouse-down`/`.chevron` scroll to `#next` | Keep; native CSS scroll-behavior |
| 5 | Hero triptych + 3R GIF | Homepage | 3-column hero, center animated GIF, separate mobile GIFs | Modern hero: WebP/AVIF stills + CSS/JS animation; no 2× GIF payloads |
| 6 | Video modal | Homepage | Bootstrap modal + YouTube iframe autoplay | Keep concept; facade pattern (loads iframe only on click — performance) |
| 7 | Leadership hover overlays | About | Photo card hover reveals bio overlay | Click/tap + hover (mobile can't hover); bio a11y-text not overlay-only |
| 8 | Bootstrap accordions | Service pages, careers | Content duplicated for desktop+mobile in DOM | Single-source `<details>`-based or Alpine accordion; content editable in CMS |
| 9 | City office tabs | Contact | 9 Bootstrap tabs, each with address + Google Maps iframe | Keep tabs; lazy-load maps on tab activation; add schema.org LocalBusiness per office |
| 10 | Careers gallery | Careers | Swiper: 5/3/2/1 responsive slides, autoplay 5s, Fancybox lightbox, magnify overlay | Swiper-equivalent (tiny JS carousel or CSS scroll-snap); lightbox only on demand |
| 11 | CSR gallery | CSR | 11 CSS-background slides + arrows + pagination | Same as above; alt text enforced (reference uses CSS backgrounds = no alt) |
| 12 | Testimonial grid | Clients Speak | Static 24-card masonry, quote icons | Same layout; content from Testimonials module with review-source links |
| 13 | Apply modal | Careers | Job accordion "Apply Now" opens modal; job title passed via `data` attr → hidden `position` field | Route to dedicated application form per job (`/careers/{slug}`) — better SEO + tracking; modal optional quick-apply |
| 14 | Contact form | Contact | Client validation → fetch POST → redirect | Livewire form, server validation, Turnstile, rate limit, lead saved even on partial; double-submit lock |
| 15 | Blog pagination | Blog | WP paginate_links; styled `.page-numbers`; red current | Keep; rel prev/next; numbered + SEO-friendly |
| 16 | Blog search | Blog sidebar | GET `?s=` | Scout full-text search with typo tolerance (when Typesense added) |
| 17 | Newsletter widget | News + blog sidebar | Form `action="#"` — **does nothing** | Working double-opt-in newsletter (Leads module + queue) |
| 18 | Login / forgot toggle | Portal | JS toggles login↔forgot boxes; inline 3s error messages | Livewire components; persistent validation errors; rate-limited auth |
| 19 | Comment form | Blog posts | WP comments; 0 comments site-wide | Keep low priority; if kept, moderated + Turnstile; or disable (Sewa default: off) |
| 20 | Japan flag button | Header | Links to external .jp site | Replaced by locale switcher (in-code i18n) — [../04-modules/11-multilingual.md](../04-modules/11-multilingual.md) |

## 2. Component library equivalences (reference → Sewa)

| Reference (Bootstrap 4 + jQuery) | Sewa (Tailwind 4 + Alpine/Livewire) |
|---|---|
| `btn btn-primary` red pill | `.btn-primary` Tailwind component (design tokens) |
| `nav-tabs` city tabs | Livewire tab island with query-param deep-linking (`?city=mumbai` → tab open; the reference can't deep-link offices) |
| Bootstrap `collapse` accordions | Alpine accordion; CMS-driven items; `aria-expanded` correct by default |
| Swiper carousels | CSS scroll-snap + Alpine controls (0–2 KB) — Swiper only if edge cases demand |
| Fancybox lightbox | Native `<dialog>` + focus trap |
| Font Awesome icons | Inline SVG sprite (subset ~30 icons; no 73 KB CSS) |
| Bootstrap modal | Native `<dialog>` / Livewire modal component |
| countUp + waypoints | Alpine `x-intersect` counter |
| AOS library | Alpine `x-intersect` reveal |
| Google Maps iframes (8+ embeds) | Click-to-load map facade + `LocalBusiness` JSON-LD per office |

## 3. Global UX patterns observed (kept, fixed, or upgraded)

**Kept (they work):**
- One consistent page anatomy: hero → intro → content blocks → CTA band → ventures → footer.
- CTA band ("Not sure which solution fits…") repeated across pages — good rhythm. Sewa keeps the pattern but makes the copy per-page CMS-editable.
- Services exposed via two families — clear mental model.

**Fixed (defects):**
- **Duplicated accordion DOM** — every service accordion rendered twice (desktop + mobile copies). Sewa: one component, responsive by CSS.
- **Sidenav-only navigation on desktop** — hamburger even on wide screens, no visible nav. Sewa: desktop nav bar + mobile off-canvas.
- **CSS-background galleries** — no alt text (a11y + SEO loss). Sewa: real `<img>` with alt.
- **3-second error messages** in portal — messages vanish before reading. Sewa: persistent, dismissible, ARIA-announced.
- **No focus states / keyboard support** beyond browser defaults in custom components. Sewa: full keyboard + visible focus (WCAG 2.2 AA).
- **jQuery 3.7.1 + jQuery 3.6 + jQuery 1.8.3** all ship across the three systems. Sewa: zero jQuery.
- **Bootstrap + 108 KB custom CSS + 6 plugin CSS files** (~918 KB total). Sewa: one Tailwind build, ~30–60 KB per page group.

**Upgraded (new capability):**
- Every interactive region becomes a Livewire island — updates without full page loads (search filtering, tab switching, form states).
- Forms: optimistic UI + inline validation + auto-save drafts (contact & applications).
- Portals get realtime notifications/chat — the reference portal is form-submit-then-wait only.
- Locale-aware interaction copies (buttons, placeholders, errors) — reference is EN-only.

## 4. Motion & feedback rules for Sewa (design-system binding)

- Animation budget: transform/opacity only; 150–400 ms; stagger ≤ 100 ms; `prefers-reduced-motion` honored globally (reference doesn't).
- Every async action has a visible state: idle → loading (skeleton/spinner) → success (inline confirmation, not alert()) → error (inline, actionable, persistent).
- Counters/stats carry an "as of" microcopy — trust through verifiability.
- No layout-shifting embeds: all media has explicit aspect-ratio boxes (CLS discipline; the reference's GIF heroes have no such contract).

## 5. Reference JS library stack (for the record — all replaced)

| Library | Version (reference) | Sewa replacement |
|---|---|---|
| Next.js / React | 11.1.4 / 17.0.2 | Laravel 13 + Livewire 4 + Alpine 3 |
| jQuery (site) | 3.7.1 (bundled) | none |
| jQuery (portal/blog) | 3.6.0 + 1.8.3 | none |
| Bootstrap JS | 4.x (+Popper) | Alpine components |
| Swiper | bundle | scroll-snap (or Swiper only if needed) |
| Fancybox | UMD | native dialog |
| AOS | 3.x | `x-intersect` |
| countUp + Waypoints | 4.0.1 | `x-intersect` counter |
| jquery-validate | 1.19.3 | Livewire server-side rules + client hints |
| axios (site) | 0.22.0 | HTTP client in Laravel |
| reaptcha (reCAPTCHA wrapper) | — | Cloudflare Turnstile |
| react-js-pagination | — | Livewire paginator |

---

Related: [01-site-map-and-pages.md](01-site-map-and-pages.md) · [03-api-and-data-layer.md](03-api-and-data-layer.md) · [../05-design-system/02-ui-components.md](../05-design-system/02-ui-components.md) · [../05-design-system/03-ux-interactions.md](../05-design-system/03-ux-interactions.md)
