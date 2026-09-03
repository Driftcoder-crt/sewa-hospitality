# 03 — UX & Interaction Specification

**Every interaction pattern, performance budget, and accessibility rule for the platform — the standards that make Sewa feel commercial-grade luxury against the reference's 2021-era UX. Design order is mobile-first (390px) — enhanced upward, never shrunk down.**

**Authoritative budgets and standards** (Lighthouse-class performance, Google CWV/SEO, MDN/Firefox web-platform Baseline discipline, WCAG 2.2 AA) are consolidated in [07-compliance-standards.md](07-compliance-standards.md) — that doc is the binding contract; this doc is the UX-level expression of it. Smart theming behavior (dark bg → light text) is specified in [04-theme-engine.md](04-theme-engine.md).

---

## 1. UX principles
1. **Nothing dead-ends:** every list, search, filter, and error state offers a next step (empty-state component with guidance — [02-ui-components.md](02-ui-components.md)).
2. **Nothing self-destructs:** errors persist until dismissed or fixed (the reference's 3-second portal messages are the anti-pattern).
3. **Nothing is hover-only:** all content reachable by tap and keyboard (leadership bios, tooltips, menus).
4. **Every async action shows state:** idle → loading (skeletons, not spinners-only) → success (inline confirmation) → error (actionable, retryable).
5. **One intent per CTA:** buttons name the outcome ("Get a move plan", not "Submit").
6. **Respect the visitor:** consent-gated trackers, no autoplay media, no exit-intent spam on first visit, reduced-motion honored.

## 2. Journey specs (the money paths)

### First visit (corporate HR evaluating Sewa)
```
Home (hero: clear H1 + sub + 2 CTAs — not the reference's image-only triptych)
→ WHO WE ARE (scannable, 3 proof points with sources)
→ Services hub (2 families, clear cards)
→ Service page (scope accordions + named consultant + per-service reviews + FAQ)
→ Contact/quote form (3 fields visible first — name/email/phone — progressive disclosure)
→ Thank-you (SLA promise + what happens next + portal teaser)
```
Micro-rules: sticky "Talk to a consultant" on scroll-past-hero (site-wide, dismissible); page-to-page continuity (service context follows the form).

### Relocating employee (portal)
```
Invite email → set password (mobile-first) → guided tour (3 tooltips)
→ Dashboard: next 3 checklist items + unread messages + docs at a glance
→ Anywhere: consultant card with chat + call options
```
Micro-rules: mobile-first portal layout (checklist + chat are the two hero surfaces); offline-tolerant chat compose (draft kept if connection drops).

### International visitor (ko/ja/tr/ar)
```
Detect (Accept-Language/geo hint) → one-time banner "View in 日本語?" (never silent redirect)
→ Locale page renders dir/lang correctly; forms in their language; dates/numbers localized
```

### Job applicant
```
/careers (life + open roles) → /careers/{job} (full detail page — reference 404s these)
→ Apply form (resume drop w/ progress + type/size guidance) → ack email with next steps
```

## 3. Interaction pattern library

| Pattern | Rule |
|---|---|
| Reveal on scroll | IntersectionObserver/`x-intersect`, fade+rise 300ms, stagger ≤ 100ms, `prefers-reduced-motion` → disabled |
| Counters | count-up ease-out on intersect + "as of" line |
| Accordion | single-source, animated height, keyboard toggle, first item open on mobile |
| Tabs | deep-linkable (`?city=mumbai`), URL-updating, back-button correct |
| Carousel | scroll-snap + buttons + dots; keyboard navigable; NO autoplay unless hero-decorative with pause |
| Lightbox | `<dialog>`, focus trap, ESC close, arrows navigate, captions shown |
| Modals | no modals for primary content (forms get pages; the reference's apply-modal becomes a page + optional quick-apply) |
| Video | facade poster → click loads iframe (autoplay off until intent) |
| Maps | click-to-load; `?city=` still shows address + "Load map" |
| Forms | inline validation on blur, server-validated truth, drafts persisted, submit lock (no double-click), success inline + route to thank-you |
| Search | debounced 250ms, grouped tabs, zero-state with suggestions, cached 10m |
| File upload | drag-drop, progress bar, mime/size guidance before upload, retry on fail |
| Notifications | toast + center; badges synced via realtime with poll fallback |
| Chat | optimistic send, delivered/read states, typing indicator (push mode), day separators, attachment previews |

## 4. Performance budgets (CI-enforced — [../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md) gate 5)

Full budget stack (Lighthouse targets, CWV thresholds, resource budgets, DOM/DB-query ceilings, third-party-zero rule) is specified in [07-compliance-standards.md](07-compliance-standards.md) §1–§2. Summary for daily reference:

| Metric | Budget (public pages) | Budget (portal/admin) |
|---|---|---|
| HTML+CSS+JS (brotli) | ≤ 500 KB total; CSS ≤ 60 KB; JS ≤ 120 KB | JS ≤ 200 KB (islands+Echo) |
| Hero media | ≤ 200 KB (WebP/AVIF) | — |
| LCP | ≤ 2.0s target / 2.5s max (4G, p75) | ≤ 2.0s |
| CLS | ≤ 0.05 (aspect boxes everywhere) | ≤ 0.05 |
| INP | ≤ 200 ms | ≤ 200 ms |
| Fonts | 2 families, subset, swap | same |
| Third-party JS on public pages | **0** (no analytics pre-consent, no pixels, no chat widgets before consent; maps/video on intent) | admin: Sentry only |

**Mobile-first & ultra-responsive contract (binding — evidence: wild-ag proves media-heavy pages can hold 18px mobile body + zero overflow; Taj's 243 undersized tap targets are the cautionary example):** 390px-first design order; Tailwind default breakpoints only (640/768/1024/1280/1536); zero horizontal overflow (CI gate); ≥44px tap targets; 16px minimum body (17–18px long-form); fluid clamp() type (display 40–88px); no `object-fit: fill` on sized media; ≤77px compact mobile header; no hover-gated content; form inputs ≥16px (iOS zoom-guard); safe-area insets honored by sticky bars. Full table: [07-compliance-standards.md](07-compliance-standards.md) §7.

Anonymous pages: full-page response cache + Cloudflare edge ([../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) §2.5) — cold TTFB on shared hosting stays hidden behind the CDN.

## 5. Accessibility standard: WCAG 2.2 AA (binding)
- Semantic landmarks (header/nav/main/footer) + skip link; single H1 per page enforced in templates; heading ladder in editor guidance.
- Focus: visible focus ring token everywhere; logical tab order; dialogs trap + restore focus.
- Color: token pairs pre-verified AA; never color-alone for meaning (chips carry icons/text).
- Media: alt required (pipeline-enforced); transcripts for brand video; captions required for any published video.
- Motion: reduced-motion honored globally; no flashing.
- Forms: labels (not placeholders), error summary + field-level `aria-describedby`, autocomplete attributes.
- Touch: ≥ 44px targets; no hover-only paths; portal mobile-first.
- Language: correct `lang`/`dir` per locale (empty `lang=""` — reference defect — impossible here).

## 6. RTL & localization UX
- Logical properties throughout; mirrored icons where directionality matters (arrows); numerals per locale reviewed by native reviewers ([../06-content-seo/04-multilingual-content.md](../06-content-seo/04-multilingual-content.md)).
- Forms: name fields sized for CJK + Latin; phone field international-format aware; addresses render per locale convention.
- Dates: locale formats; booking/due dates never ambiguous (ISO in machine contexts, locale in display).

## 7. Error & edge states (the honesty map)
| State | UX |
|---|---|
| 404 | branded page + search + top services + contact (never bare) |
| 500 (should never ship) | friendly page + incident link (status page) — Sentry-alerted |
| Form network fail | inline retry, draft kept, never lose typed data |
| AI-degraded | English/native fallback content silently; admin notified |
| Realtime-degraded | polling takes over transparently |
| Empty search | suggestions + "request this topic" (feeds content backlog) |
| Slow island | skeleton placeholders (never blank) |

## 8. QA reference
- Every interaction pattern above maps to a component state in `/dev/components` ([02-ui-components.md](02-ui-components.md) §3) and a UAT checklist item in module docs.
- Quarterly UX audit: keyboard-only pass, screen-reader spot-check (NVDA/VoiceOver), RTL pass, 4G throttled Lighthouse runs — logged in CHANGELOG.

---

Related: [01-brand-guidelines.md](01-brand-guidelines.md) · [02-ui-components.md](02-ui-components.md) · [../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md) · [../02-formula-reference/02-components-interactions.md](../02-formula-reference/02-components-interactions.md)
