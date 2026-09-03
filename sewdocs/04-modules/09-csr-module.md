# 09 — CSR Module

**The CSR program page: NGO partners, impact stories, galleries — the reference's strongest trust surface, rebuilt as CMS-managed content with real link-outs and measurable claims.**

---

## 1. Purpose
Corporate mobility buyers (especially Japanese, Korean, and European HR teams) evaluate vendors on governance and community footprint. The reference dedicates a full page with 7 NGO partners; Sewa matches that surface and makes it honest: named partners with links, real numbers with "as of" dating, and stories that can be cited.

## 2. Data model
`ngo_partners`, `csr_stories` ([../03-technical-specs/03-database-schema.md](../03-technical-specs/03-database-schema.md) §7) + CMS `gallery` blocks.

## 3. Public surface

| Route | Spec |
|---|---|
| /csr | hero → program intro (Sewa's CSR philosophy — service as the name implies) → partner cards (logo, linked official site, focus areas, since, one measurable claim each with "as of" date) → impact stories feed → gallery (real `<img>` + alt — not CSS backgrounds) → CTA |
| /csr/{story-slug} | story pages: what was done, photos, partner attribution, date — citable, shareable, schema `Article` |
| /about teaser | CMS block pulling one partner highlight (reference has the same teaser pattern — kept) |

## 4. Admin surface
1. **NGO partners** — CRUD: name, logo (media, alt), website URL, description, focus areas (tags), since year, city, sort, status; locale variants.
2. **Stories** — post-like editor (cover, body, gallery inserts, partner link, published date, locale groups) with the same publish gates (metas, alt, author).
3. **Gallery management** — drag-order gallery picker with per-photo captions (caption + alt required; captions render publicly for context).
4. **Claims ledger** — every measurable claim ("600+ women trained", "55,000 treated") carries as_of date + source note in the editor — displayed with the claim. (Anti-overstatement discipline — same honesty rules as stats and reviews.)

## 5. Behavior & rules
- Partner logos link to the NGOs' official sites (reference does this — kept; it's the right thing).
- Only active partnerships display; archived partners move to a "past associations" collapsed list (honesty over logo walls).
- Stories publish into the blog feed optionally (cross-post flag) for wider reach.

## 6. Error handling
- Dead partner-site links checked monthly (seo:audit extension) → editor alert to re-verify.
- Gallery with missing captions/alt → publish blocked (consistent media discipline).

## 7. Events & integrations
`StoryPublished` → sitemap/cache/search; optional cross-post event to Blog module. CMS blocks (`gallery`, partner card grid) consumed on /about and /csr.

## 8. Tests
Partner card render + external-link `rel` correctness; claims render with as_of; gallery alt/caption gate; locale fallback; cross-post flag creates post with source link; archived partner visibility rule.

---

Related: [00-module-system.md](00-module-system.md) · [01-cms.md](01-cms.md) · [07-blog-news.md](07-blog-news.md) · [../07-marketing-trust/03-trust-authority.md](../07-marketing-trust/03-trust-authority.md) · Reference inventory: [../02-formula-reference/01-site-map-and-pages.md](../02-formula-reference/01-site-map-and-pages.md) §3.8
