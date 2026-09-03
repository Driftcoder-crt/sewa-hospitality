# 08 — Testimonials & Reviews Module

**A real review system: Google Business Profile sync, verified service-tagged testimonials, automated post-service review requests, and honest rating schema — replacing the reference's 24 unsourced static quote cards and its self-declared 9.9/10 rating.**

---

## 1. Purpose
Turn happy clients into visible, credible, structured social proof: collect reviews automatically at journey completion, sync Google reviews, curate testimonials per service/city, and render them with schema that matches what's on the page (the SEO-safe pattern the reference violates).

## 2. Data model
`testimonials`, `google_reviews` ([../03-technical-specs/03-database-schema.md](../03-technical-specs/03-database-schema.md) §6).

## 3. Public surface
| Surface | Spec |
|---|---|
| /reviews | hub: rating summary (GBP live stats + note "as of {date}"), Google review cards (synced, linked source), curated Sewa testimonials, filter by service/city |
| Home strip | 4–6 latest, source badges (Google/direct) — CMS `testimonial_grid` block |
| Service pages | per-service testimonials (the reference shows quotes but no service linkage discipline) |
| City pages | per-city testimonials (relocation credibility where the reader is looking) |
| Structured data | `Review`/`AggregateRating` **only** on pages where the reviewed entity + reviews are visibly rendered; Google reviews carry `sameAs` source URL |

## 4. Admin surface
1. **Testimonials manager** — table (name, service, city, source, status, published_at), editor with service/city link, rating (optional — only when client actually rated), media (photo optional with consent), approve/archive states.
2. **Google sync** — daily 06:00 sync ([../03-technical-specs/07-queues-scheduling.md](../03-technical-specs/07-queues-scheduling.md)); stats card (rating, count, trend vs. last month); new-review alerts (5★ = share prompt; ≤3★ = service recovery alert to ops — see Behavior).
3. **Review request campaigns** — triggered automatically when a move completes (`MoveStageChanged complete`): email (client employee + HR) with Google review link + in-form feedback fallback; request queue, sent/opened/done tracking, polite single follow-up after 7 days, hard stop after one follow-up (no spam).
4. **Feedback inbox** — private feedback from the fallback form routed to ops as CSAT data (never auto-published; candidate testimonial only if the client opts in).

## 5. Behavior & rules
- **Verification discipline:** a testimonial shows "verified" only when linked to a completed move or a synced Google review — no anonymous quotes.
- **Rating honesty rule:** site-wide rating number = live GBP stats with "as of" date; the platform never displays a rating it can't source (reference's 9.9/1024 self-declared pattern is banned by policy and by the test suite).
- **Consent:** publishing a client name/company requires recorded consent flag; default renders first name + city.
- **Service recovery:** ≤3★ Google review → immediate ops alert (SLA 4h outreach) — the review engine doubles as a quality loop.
- **Translation:** machine-translated testimonial bodies flagged `machine` until reviewed (only for display languages; source text preserved).

## 6. Error handling
- GBP sync failure → last-known stats with "synced {date}" note + alert; site never shows stale-unlabeled data.
- Review request email failure → queue retries; request state idempotent (one completion = one request chain, ever).
- Filter empty state → fallback to all testimonials (never a dead section).

## 7. Events & integrations
Listens: `MoveStageChanged(complete)` → enqueue review request. Emits: `ReviewReceived` (ops alert), `TestimonialPublished` (cache/sitemap minor). Integrations: GBP API (or provider) behind breaker; email catalog (`review.request`); CMS `testimonial_grid` block; analytics event `click_review` on Google CTA.

## 8. Tests
Sync idempotency (double-cron ≠ duplicates); one-review-request-per-move invariant; consent gating on names; rating-shown == rating-sourced (golden test); schema matches visible reviews; service/city filters; recovery alert on ≤3★; machine-flag not public until reviewed.

---

Related: [00-module-system.md](00-module-system.md) · [04-client-portal.md](04-client-portal.md) (completion trigger) · [../07-marketing-trust/01-google-ecosystem.md](../07-marketing-trust/01-google-ecosystem.md) (GBP growth plan) · [../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md) (schema rules) · Reference defects: [../02-formula-reference/06-weaknesses-opportunities.md](../02-formula-reference/06-weaknesses-opportunities.md) D1, B5
