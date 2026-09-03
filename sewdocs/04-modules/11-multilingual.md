# 11 — Multilingual Module (I18n)

**Auto-detected, AI-assisted, human-reviewed multilingual serving for Sewa's international client base — Korean, Japanese, Turkish, Arabic (RTL) and more — replacing the reference's English-only site and its dead-end separate .jp site.**

---

## 1. Purpose
Sewa's clients are international mobility teams and relocating families: Korean and Japanese corporate relocations are a core segment (the reference runs a Japan desk job requiring N2/N3 Japanese), plus Turkish, Saudi/Arabic, and other foreign-nationality flows. They must be **identified automatically and served in their language**, with content quality that builds trust rather than machine-translation shame.

Locale set at launch: `en` (default/x-default), `hi`, `ja`, `ko`, `tr`, `ar` (RTL). Extensible by settings row (fr, de, ru, zh…).

## 2. Data model
`locales` (code, name, direction, enabled, auto_translate), `translations` (UI strings: locale+namespace+key with `status: machine|human`), and per-entity `locale + locale_source_id` translation groups ([../03-technical-specs/03-database-schema.md](../03-technical-specs/03-database-schema.md) §10).

## 3. Detection & resolution (automatic, respectful)
```
Priority: (1) explicit path prefix /ja/… → (2) user cookie (chosen once) →
(3) Accept-Language header quality match against enabled locales →
(4) geo hint (Cloudflare country header) ONLY as tiebreaker → (5) en
Detection never redirects the URL silently (SEO-safe): a hint shows a one-time
locale suggestion banner ("View in 한국어?") that sets the cookie on click.
```
- Language switcher in footer + banner: plain locale names (the reference's Japan-flag button pattern is kept in spirit, but as a proper switcher).
- No locale-by-IP lockouts; English always reachable in one click.
- `hreflang` alternates auto-generated for every page: all published locale variants + `x-default` → en ([../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md)).
- RTL: `dir="rtl"` + Tailwind logical properties (`ps-/pe-/ms-/me-` etc.) verified by the design system ([../05-design-system/03-ux-interactions.md](../05-design-system/03-ux-interactions.md)).

## 4. Translation pipeline (AI-assisted, human-gated)

```
1. Editor publishes an entity in en (or hi).
2. I18n module enqueues TranslateContent per enabled locale (queue: ai — [../03-technical-specs/07-queues-scheduling.md](../03-technical-specs/07-queues-scheduling.md)).
3. AI translation (z-ai/glm-5.3-free via TokenRouter/OpenRouter — [../08-ai-system/01-ai-architecture.md](../08-ai-system/01-ai-architecture.md))
   → stored as locale variant with status=machine.
4. Translation Review Queue (admin): side-by-side original/translation editor;
   native-speaker or trained reviewer approves → status=human → locale becomes publishable.
5. Publish per-locale is a deliberate action (or auto-publish policy for low-risk content types, configurable).
```
- **Register rules per locale** (formal politeness for ja/ko; formal Arabic; natural Turkish) — enforced in review, defined in [../06-content-seo/04-multilingual-content.md](../06-content-seo/04-multilingual-content.md).
- **Fallback:** missing translated entity → EN renders, hreflang omits the locale; a blank or machine-only page NEVER publishes as final.
- **UI strings:** the `translations` table ships machine-seeded for the launch set; every UI string reviewable in admin with the same machine/human states.

## 5. Locale-specific serving rules
| Surface | Behavior |
|---|---|
| Forms | locale recorded on lead; ack email in lead language when human translation exists (else EN + "reply in any language" note) |
| Phone/WhatsApp CTAs | locale-aware hours/availability copy; response-language promise |
| Pricing | INR everywhere + locale-appropriate thousands separators; no pseudo-localized currencies |
| Dates | locale formats (ja: 2026年9月1日; ko: 2026년 9월 1일; ar: ٩/١/٢٠٢٦ / RTL numerals per review) |
| URLs | path prefix only (`/ja/services/…`); never parameter or cookie-based locales |
| Sitemap | one sitemap with hreflang alternates per URL — Google reads it correctly |

## 6. Admin surface
1. **Locales** — enable/disable, register notes, auto-publish policy per content type.
2. **Translation review queue** — side-by-side editor (original / machine draft / edit fields), approve / edit-and-approve / reject-with-note; bulk approve for trusted content types after quality stabilizes; reviewer attribution logged.
3. **UI strings** — namespace browser (site/portal/admin/email) with search, edit, machine/human states.
4. **Detection settings** — banner copy per locale, cookie policy note.

## 7. Error handling
- AI provider breaker open → translation queue parks items (content still publishes EN-only; queue drains later — nothing blocked: [../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) §2.3).
- A locale half-published can't happen: publish is per-locale atomic; hreflang always matches reality.
- Detection edge cases (weird Accept-Language lists) resolve deterministically (documented order above) — tested.

## 8. Tests
Detection resolution matrix (header/cookie/path/geo) incl. `ja-KR` vs `ko` precedence; hreflang set complete per page (all published locales + x-default); RTL rendering suite (`dir`, logical properties, mirrored layouts) for ar; translation states (machine never public-published without policy or approval); fallback to EN on missing variant; lead locale flows into ack email selection; cookie set only on explicit action.

---

Related: [00-module-system.md](00-module-system.md) · [07-blog-news.md](07-blog-news.md) · [../08-ai-system/01-ai-architecture.md](../08-ai-system/01-ai-architecture.md) · [../08-ai-system/02-ai-use-cases.md](../08-ai-system/02-ai-use-cases.md) · [../06-content-seo/04-multilingual-content.md](../06-content-seo/04-multilingual-content.md) · [../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md)
