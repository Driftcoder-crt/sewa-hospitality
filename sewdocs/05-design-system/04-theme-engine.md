# 04 — The Theme Engine (Centralized Smart Theme Control)

**One theme system controls every pixel of every surface — marketing site, portals, admin, email, PDFs. Smart by construction: a dark background automatically gets light text; every combination is validated for contrast before it can publish. Editors change the brand look from one admin panel without touching code.**

---

## 1. Architecture — four layers

```
Layer 1  PRIMITIVES        raw palette values (oklch), never used directly in templates
Layer 2  SEMANTIC TOKENS   --paper/--ink + role tokens (primary, accent, muted…) per theme
Layer 3  SECTION THEMES    data-theme="light|dark|brand|deep" on every block/section
Layer 4  PRESETS & CONTROL admin Theme panel: presets, brand color customization, preview,
                           custom themes, per-page overrides — all persisted & versioned
```

**The smart rule, formalized:** no component ever says "color: white." Every component consumes *pairs*. When a block sets `data-theme="dark"`, the pair inverts (`--paper` darkens, `--ink` lightens) and **everything inside — headings, body text, buttons, borders, muted text, form fields, icons — flips automatically** because they all read the pair tokens. This is the exact mechanism observed at hgd-media (near-black sections with auto-white text) and nomu (shadcn `--x`/`--x-foreground` pairs), but defined once, centrally, and validated by machine.

## 2. Layer 1 — primitives (oklch, the evidence-backed color space)

surinder.design runs its entire system in `oklch()` — perceptually uniform, which makes contrast math reliable. Sewa adopts the same space with sRGB fallbacks (see [07-compliance-standards.md](07-compliance-standards.md) §4 for the fallback discipline).

```css
@theme {
  /* primitives — never used directly in markup */
  --sewa-teal-700: oklch(0.32 0.065 172);
  --sewa-teal-600: oklch(0.38 0.075 172);
  --sewa-teal-500: oklch(0.45 0.085 172);   /* brand action color */
  --sewa-teal-400: oklch(0.55 0.09 172);
  --bronze-500:    oklch(0.68 0.09 75);     /* accent — restrained, never gold gradients */
  --bronze-400:    oklch(0.74 0.09 78);
  --sand-0:        oklch(0.988 0.004 85);    /* #FAF7F2 paper */
  --sand-1:        oklch(0.965 0.008 85);
  --sand-2:        oklch(0.925 0.012 85);
  --clay-400:      oklch(0.72 0.03 70);     /* warm muted borders/labels */
  --ink-900:       oklch(0.21 0.012 70);     /* #26201A warm near-black — never #000 */
  --ink-700:       oklch(0.30 0.012 70);
  --ink-500:       oklch(0.45 0.010 70);
  --success-500:   oklch(0.62 0.15 150);
  --warning-500:   oklch(0.72 0.14 70);
  --danger-500:    oklch(0.55 0.19 25);
  --info-500:      oklch(0.60 0.13 240);
}
```

## 3. Layer 2 — semantic tokens (the pair system)

Four named themes ship at launch. Every theme defines the **same semantic set** — a component built once renders correctly in all of them, and any future theme only needs to fill the same slots.

```css
:root, [data-theme="light"] {
  --paper: var(--sand-0);            /* page surface      */
  --paper-2: var(--sand-1);          /* raised surface    */
  --paper-3: var(--sand-2);          /* sunken surface    */
  --ink: var(--ink-900);             /* primary text      */
  --ink-soft: var(--ink-700);        /* secondary text    */
  --ink-muted: var(--ink-500);       /* meta text         */
  --line: var(--clay-400);           /* borders/dividers  */
  --brand: var(--sewa-teal-500);     /* actions, links    */
  --brand-ink: var(--sand-0);        /* text ON brand     */
  --accent: var(--bronze-500);       /* rare highlights   */
  --accent-ink: var(--ink-900);      /* text ON accent    */
  --overlay: oklch(0.21 0.012 70 / 0.5);
  color-scheme: light;               /* native controls, scrollbars adapt */
}
[data-theme="dark"] {                 /* the smart inversion — same slots, deep values */
  --paper:   oklch(0.235 0.012 70);   /* warm charcoal #2A231C — never pure black (brigade/warm-black rule) */
  --paper-2: oklch(0.28 0.012 70);
  --paper-3: oklch(0.33 0.012 70);
  --ink:     oklch(0.965 0.005 85);  /* light text — AUTOMATIC on dark paper */
  --ink-soft: oklch(0.88 0.005 85);
  --ink-muted: oklch(0.72 0.008 85);
  --line:   oklch(0.72 0.03 70 / 0.35);
  --brand:  var(--sewa-teal-400);     /* brightened for dark bg */
  --brand-ink: var(--ink-900);
  --accent: var(--bronze-400);
  --accent-ink: oklch(0.20 0.012 70);
  color-scheme: dark;
}
[data-theme="brand"] {                 /* deep-teal brand immersion for hero/footer/CTA moments */
  --paper: oklch(0.30 0.06 172);      --paper-2: oklch(0.34 0.06 172);
  --ink: oklch(0.97 0.008 100);  --ink-soft: oklch(0.92 0.01 100);  --ink-muted: oklch(0.82 0.02 100);
  --line: oklch(0.97 0.01 100 / 0.25);
  --brand: oklch(0.97 0.01 100);      /* on brand surfaces, the action inverts to light */
  --brand-ink: oklch(0.30 0.06 172);
  --accent: var(--bronze-400);  --accent-ink: oklch(0.20 0.012 70);
  color-scheme: dark;
}
[data-theme="deep"] {                  /* near-black editorial for film-style sections */
  --paper: oklch(0.185 0.010 70);  --paper-2: oklch(0.225 0.010 70);
  --ink: oklch(0.96 0.005 85);    --ink-soft: oklch(0.88 0.006 85);  --ink-muted: oklch(0.70 0.008 85);
  --line: oklch(0.85 0.01 85 / 0.2);  --brand: var(--sewa-teal-400);  --brand-ink: var(--ink-900);
  --accent: var(--bronze-400);    --accent-ink: var(--ink-900);
  color-scheme: dark;
}
```

Rules:
1. Components consume **only semantic tokens** (`bg-paper text-ink border-line` …). Primitive classes are linted out of templates ([07-compliance-standards.md](07-compliance-standards.md) §6).
2. `color-scheme` ships with every theme — native form controls, scrollbars, and `<input type="date">` pickers match the theme with zero JS.
3. Photography/media overlays read `--paper`/`--ink` for their scrim + caption colors — a dark-theme gallery caption can never remain dark-on-dark.

## 4. Layer 3 — section themes in practice

Every CMS block and Blade section accepts `data-theme` (default inherits from the page, which inherits from site):

```html
<section data-theme="brand">          <!-- hero -->
  <h1 class="text-ink">…</h1>          <!-- auto light -->
  <a class="btn bg-brand text-brand-ink">Talk to a consultant</a>
</section>
<section data-theme="light">          <!-- body section -->
  <p class="text-ink-soft">…</p>       <!-- auto dark -->
</section>
```

- **Auto-selection ("smart" mode):** in the block editor, when an editor picks a background media or color for a section, the editor computes luminance and *suggests* the correct theme (dark media → `deep` theme). Suggestion is one click to accept; a hard contrast check runs at publish regardless (below).
- **Page-level override:** pages can set a default section theme (e.g., a campaign landing entirely on `deep`) — page settings in [../04-modules/01-cms.md](../04-modules/01-cms.md) §5.
- **Nesting:** themes nest safely — a `light` card dropped in a `brand` section keeps its own pair. No inverted-text bugs, ever.

## 5. Layer 4 — the admin Theme panel (centralized control)

**Location:** admin → Appearance → Theme. **Everything below is settings, not code.** (Permissions: `admin`; editors get read + preview.)

| Control | What it does |
|---|---|
| **Presets** | Shipped: "Sewa Classic" (light-first), "Sewa Night" (dark-first), "Monochrome Editorial", "Peacock" (brand-forward). A preset = full token set |
| **Brand customizer** | Pick brand/accent hues via oklch-hue sliders (hue/chroma/lightness); the engine derives the full ramp automatically and **enforces contrast** (below) |
| **Site mode** | `light` / `dark` / `system` (`prefers-color-scheme` respect) / `brand` default |
| **Typography** | Display face on/off (serif vs sans display), base scale multiplier (0.9–1.1), tracking presets |
| **Shape** | Radius scale (editorial 0–4 / soft 6–12 / rounded 16+ / mixed preset), button style (pill/editorial) |
| **Section rhythm** | Spacing density (compact/comfortable/expansive — maps to the spacing scale multiplier) |
| **Motion** | Global motion level (full/reduced/off) + smooth-scroll flag (default off — [03-ux-interactions.md](03-ux-interactions.md) §3) |
| **Preview & publish** | Live iframe preview on 4 sample pages (home, service, city, post) × 4 viewports before publish; publish is versioned + instantly cache-purged (tagged) |
| **Custom themes** | Duplicate a preset, edit all slots, name it; themes are entities (schema `themes` table: name, tokens JSON, version) — reusable across pages |
| **Email/PDF sync** | Same theme tokens compile into email + invoice templates at build time — one brand everywhere ([../03-technical-specs/10-email.md](../03-technical-specs/10-email.md)) |

**Token compilation:** the active theme's slots compile to a static `theme.css` (Tailwind `@theme` + pair variables) — a file, not runtime CSS generation — so page performance is identical to hand-written CSS (see budgets in [07-compliance-standards.md](07-compliance-standards.md)).

## 6. The smart contrast enforcement (the "it just works" guarantee)

1. **At authoring time:** the block editor's live preview computes WCAG 2.1 contrast for every ink/paper pair a section creates (including text-over-media via the scrim token). Violations show as inline warnings with one-click fixes ("Switch section to `deep` theme" / "Brighten `--ink-muted` to AA").
2. **At publish time:** a server-side contrast validator walks the page's composed blocks; **publish is blocked** if any pair fails AA (4.5:1 text, 3:1 large text/UI). This is the same "publish-gate" pattern as the SEO/meta gates ([../04-modules/01-cms.md](../04-modules/01-cms.md) §5) — a page cannot go live with unreadable text, regardless of how the theme was customized.
3. **At runtime:** zero JS needed for theming (pure CSS); `prefers-color-scheme` only chooses the *site default* when mode = system. Theme switching never reflows after first paint (both modes' body classes set pre-hydration via an inline `<style>` guard — no dark-mode flash, no CLS).
4. **In CI:** per-preset snapshot tests assert: AA pass on all four themes × key components; no primitive class in templates; `color-scheme` present on all four themes.

## 7. What this buys (requirement traceability)

| Requirement (from the brief) | Where satisfied |
|---|---|
| "All pages highly customizable" | Theme panel + page-level overrides + per-block `data-theme` |
| "Centralized theme control" | Layer 4 admin panel — one place, everything follows (site, portals, admin accent, email, PDFs) |
| "Smart: if background is dark, fonts are light" | Pair-token architecture (§3) + auto-suggestion (§4) + enforced contrast (§6) |
| "Award-winning color combinations" | Presets engineered from the 10-site evidence ([06-reference-sites-analysis.md](06-reference-sites-analysis.md) §2), oklch-derived, AA-locked |
| "Luxury without gold" | Warm paper + warm ink + restrained bronze accent at ≤5% surface; no metallic gradients (see [01-brand-guidelines.md](01-brand-guidelines.md) §3 palette v2) |
| "Mobile-first, ultra responsive" | Tokens are viewport-independent; themes add zero JS; budgets unchanged ([07-compliance-standards.md](07-compliance-standards.md) §3) |

## 8. Tests (Pest + CI)
- Token contract: every theme defines the complete slot set (missing slot = build fail).
- Contrast matrix: ink/paper/brand/accent pairs × 4 themes ≥ AA — golden test.
- Nested-theme rendering: light card inside brand section reads correctly (no leakage).
- Publish-gate: a deliberately low-contrast custom theme cannot publish a page.
- `color-scheme` assertion per theme; no-flash assertion (inline guard present).
- Admin: preset switch compiles new theme.css, cache purge event fires, email/PDF templates recompile.

---

Related: [01-brand-guidelines.md](01-brand-guidelines.md) · [03-ux-interactions.md](03-ux-interactions.md) · [05-section-block-library.md](05-section-block-library.md) · [06-reference-sites-analysis.md](06-reference-sites-analysis.md) · [07-compliance-standards.md](07-compliance-standards.md) · [../04-modules/01-cms.md](../04-modules/01-cms.md) · [../04-modules/05-admin-panel.md](../04-modules/05-admin-panel.md)
