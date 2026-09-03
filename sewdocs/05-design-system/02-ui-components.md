# 02 — UI Component Library

**One Tailwind 4 component library for site, portals, admin, and email. Every component exists once, is accessible by default, RTL-safe, theme-token-driven (renders correctly in all four themes), and replaces the reference's Bootstrap 4 + jQuery stack entirely.**

**Relationship to the section library:** this doc defines the *atomic* components (buttons, fields, cards, modals, toasts…). The 47 **premade page sections/blocks** composed from these atoms live in [05-section-block-library.md](05-section-block-library.md); the smart theme tokens they all consume live in [04-theme-engine.md](04-theme-engine.md).

---

## 1. Component inventory (build order)

### Layout & navigation
| Component | Spec highlights | Replaces (reference) |
|---|---|---|
| `<x-site-header>` | desktop nav bar (always visible — reference is hamburger-only) + off-canvas mobile with focus trap; sticky, shrink-on-scroll | sidenav-only nav |
| `<x-locale-switcher>` | names not flags (EN · हिन्दी · 日本語 · 한국어 · Türkçe · العربية); sets cookie, no silent redirects | Japan-flag button |
| `<x-breadcrumbs>` | visible trail + BreadcrumbList schema (reference has none) | — |
| `<x-footer>` | columns, NAP from settings, socials, memberships strip, ventures strip | Bootstrap footer |
| `<x-cta-band>` | headline + primary button, per-context copy, CMS-driven | "Not sure which solution…" band (kept, made dynamic) |

### Content blocks (CMS renderers — [../04-modules/01-cms.md](../04-modules/01-cms.md) §2)
| Component | Rules |
|---|---|
| `<x-blocks.hero>` | media (focal) + headline/sub/CTAs; overlay strength token; eager load + explicit aspect box; never two H1s (hero headline = H1 only when page has no other; template-enforced) |
| `<x-blocks.stats>` | count-up on intersect, "as of" line, honest values |
| `<x-blocks.accordion>` | **single-source** Alpine accordion; `aria-expanded` correct by default; content from CMS (reference duplicates DOM per breakpoint — structurally impossible here) |
| `<x-blocks.cards-grid>` | cards from any module source; uniform aspect boxes; reveal on intersect |
| `<x-blocks.testimonial-grid>` | quote, name, service+city chips, source badge (Google/direct), link to /reviews |
| `<x-blocks.gallery>` | grid or scroll-snap carousel; real `<img>` + alt + captions; `<dialog>` lightbox with focus trap (replaces Swiper+Fancybox) |
| `<x-blocks.faq>` | accordion items; emits FAQPage JSON-LD; answer-first copy style |
| `<x-blocks.video>` | facade (poster + play) — iframe loads on intent only |
| `<x-blocks.leadership-grid>` | clickable profile cards (bio visible without hover — reference defect) |
| `<x-blocks.offices>` | city tabs deep-linkable via `?city=`; map = click-to-load facade + LocalBusiness schema |
| `<x-blocks.logos-strip>` | memberships with proof links, `rel="noopener"` |

### Forms & feedback
| Component | Rules |
|---|---|
| `<x-form.field>` | label, input, hint, error slot; inline server validation states; drafts persist (Livewire) |
| `<x-button>` | variants (primary teal / secondary outline / ghost / danger), states (hover/active/focus-visible/disabled/loading with spinner) — the full state machine the reference never styles |
| `<x-formturnstile>` | Turnstile widget wrapper (renders challenge) |
| `<x-alert>` | persistent, dismissible, ARIA-announced (replaces 3-second self-destructing portal errors) |
| `<x-toast>` | admin/portal actions |
| `<x-empty-state>` | illustration + guidance + CTA (search, filters, lists — never dead-ends) |

### Site-specific
| Component | Rules |
|---|---|
| `<x-service-card>` | icon (sprite), name, excerpt, arrow link; consistent across hubs/city pages |
| `<x-housing-card>` | tier badge (Sewa Verified with date), from-rate, amenities chips, gallery thumb |
| `<x-city-card>` | name, snapshot line, service links |
| `<x-post-card>` | cover (aspect 3:2), date badge, title, excerpt, author avatar+name, read-time — replaces WP card |
| `<x-review-card>` | Google-linked reviews with stars, date, source |
| `<x-pagination>` | numbered + rel prev/next; styled per tokens (reference keeps this — good) |

### Admin/portal surfaces
| Component | Rules |
|---|---|
| `<x-admin.shell>` | sidebar + topbar + ⌘K palette ([../04-modules/05-admin-panel.md](../04-modules/05-admin-panel.md) §2) |
| `<x-admin.table>` | server-paginated, sortable, saved filters, bulk actions, PII masking hooks |
| `<x-admin.kanban>` | drag with audit (leads pipeline, ATS) |
| `<x-admin.canvas>` | block editor canvas + live preview iframe |
| `<x-portal.timeline>` | move stages + checklist with due states |
| `<x-portal.chat>` | thread view, typing states, offline-safe (poll fallback) |

## 2. Engineering conventions
- **Class discipline:** tokens only (`bg-sand-100`, `text-ink-600`); no magic hex in templates; variants via component props, not copy-pasted classes.
- **RTL-safe by construction:** logical properties (`ps-`, `pe-`, `ms-`, `me-`, `text-start/end`) — never `left/right`; mirrored layouts verified in the ar test suite.
- **A11y defaults:** focus-visible ring token on every interactive element; skip-link on site + admin; hit targets ≥ 44px; form errors linked via `aria-describedby`; dialogs trap focus and restore it on close.
- **Performance contracts:** aspect boxes everywhere (zero CLS); below-fold lazy loading; hero ≤ 200KB; islands keep interactions cheap ([../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md) gate 5 budgets).
- **One Blade component = one place to change the whole platform** (email variants generated from the same tokens).

## 3. Storybook-style preview
- A `/dev/components` route (admin-only, noindex, disabled in production by env) renders every component in every state (default/hover/focus/disabled/loading/error/empty/RTL) — the design review surface and the QA reference for UAT checklists.

## 4. Component → reference defect map (traceability)
Every reference defect fixed by a component rule is listed in [../02-formula-reference/02-components-interactions.md](../02-formula-reference/02-components-interactions.md) §2 — e.g., duplicated accordions (fixed by single-source accordion), hover-only bios (leadership-grid), CSS-background galleries (gallery), 3s error messages (alert), hamburger-only nav (site-header), missing focus states (button state machine).

---

Related: [01-brand-guidelines.md](01-brand-guidelines.md) · [03-ux-interactions.md](03-ux-interactions.md) · [../03-technical-specs/09-media-pipeline.md](../03-technical-specs/09-media-pipeline.md) · [../04-modules/01-cms.md](../04-modules/01-cms.md) · [../04-modules/05-admin-panel.md](../04-modules/05-admin-panel.md)
