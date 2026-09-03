# 00 — The Module System

**How the platform is divided into proper, commercially-grade modules — the binding conventions every module doc below follows.**

---

## 1. The 14 modules (+ the shared kernel)

| # | Module | Public surface | Admin surface | Reference equivalent |
|---|---|---|---|---|
| 01 | Cms | all pages, banners, menus, settings | page/block/menu/settings editors | (none — reference hardcodes everything) |
| 02 | Services | /services tree, service pages | service editor | /services (11 services + immigration sub-tree) |
| 03 | Leads/CRM | contact + quote + callback + newsletter forms | inbox, pipeline, SLA, assignment | api/contactus (bare endpoint, no CRM) |
| 04 | Portal | app.sewa… (clients + relocating employees) | move management, docs, threads | /login CodeIgniter portal |
| 05 | Admin panel | admin.sewa… (the panel itself is module 00's sibling surface) | everything below | (none found) |
| 06 | HR & Employees | /about team, /careers, authors | employees, job postings, applications, ATS | careers page (6 jobs, modal form, 404 detail pages) |
| 07 | Blog & News | /blog, /news, posts/categories/tags | editor with review workflow, calendar | WordPress blog (45 posts, Rank Math) + 3-post news |
| 08 | Testimonials & Reviews | home strip, service pages, /reviews | review sync, curation, review requests | 24 static cards |
| 09 | CSR | /csr | NGO partners, stories, galleries | CSR page (7 NGOs, gallery) |
| 10 | Cities & Housing | /cities tree, /housing inventory | city editor, housing units, verification | 8 blog city guides + sister-site links |
| 11 | Multilingual (I18n) | locale paths /ja /ko /tr /ar /hi | translation review queue | (none — EN only + separate .jp site) |
| 12 | Billing & Finance | portal invoices, quotes | quote→invoice→payment, numbering | (none) |
| 13 | Mobile readiness | (API contract) | token management | (separate legacy app, out of scope) |
| — | Ai | (invisible) | provider config, budget gauges, review queues | (none) |

## 2. Module anatomy (every module doc specifies these)

1. **Purpose** — one paragraph.
2. **Data model** — entities, key columns, ownership (links to [../03-technical-specs/03-database-schema.md](../03-technical-specs/03-database-schema.md)).
3. **Public surface** — routes, pages, and their content blocks.
4. **Admin surface** — every screen, its controls, and its permission.
5. **Behavior & rules** — workflows, validation, status machines.
6. **Error handling** — failure modes and fallbacks per the doctrine ([../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md)).
7. **Events & integrations** — what it emits/listens to.
8. **Tests** — the module's Pest scenarios ([../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md) §3).

## 3. Hard rules of module design

1. **Boundaries are real:** own your models/policies/jobs/components; cross-module reads go through service interfaces or events — never another module's tables directly.
2. **The CMS block library is the content backbone:** Services, Cities, CSR, Testimonials expose themselves to CMS pages as *linkable content* (e.g. a `testimonial_grid` block with source filter) — editors compose pages without engineering.
3. **Every write path is a money path:** transaction + idempotency + audit + queue handoff. Every form is a lead/application/invoice event, never a fire-and-forget POST (the reference's core weakness).
4. **Every list is role-scoped:** admin lists filter by the permission matrix (see module 05); portal lists are tenant-scoped.
5. **Every content entity is i18n-native:** `locale + locale_source_id` translation groups + `status=machine|human` state; publishing a locale never half-breaks another ([../04-modules/11-multilingual.md](../04-modules/11-multilingual.md)).
6. **Every public entity is SEO-complete at publish:** meta, canonical, schema binding, sitemap membership — publish is blocked on missing SEO fields ([../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md)).
7. **No module ships a UI dependency on a third-party service:** AI/Ably/email enhance; the native path always works.

## 4. Event catalog (module integrations)

```
Cms:        PagePublished · PageUnpublished · SettingsUpdated
Services:   ServicePublished · ServiceUpdated
Cities:     CityPublished · HousingUnitVerified
Blog:       PostPublished · PostScheduled · TranslationReadyForReview
Leads:      LeadCreated · LeadStatusChanged · NewsletterSubscribed · SlaBreached
Careers/Hr: ApplicationReceived · ApplicationStatusChanged · JobOpened · JobClosed
Testimonials: ReviewReceived · TestimonialPublished
Csr:        StoryPublished
Portal:     MoveStageChanged · ChecklistItemDone · MessageSent · DocumentPublished
Billing:    QuoteAccepted · InvoiceIssued · PaymentRecorded
I18n:       LocaleEnabled · TranslationApproved
Ai:         InvocationFallback (breaker events)
```
Listeners wire cross-module reactions — e.g. `MoveStageChanged(stage=complete)` → Testimonials module queues `review.request` email; `LeadCreated` → admin realtime toast + SLA timer + AI enrichment job.

## 5. Permissions overview (detail in module 05)

| Role | Content | Leads | Portal/ops | HR | Billing | System |
|---|---|---|---|---|---|---|
| super-admin | ● | ● | ● | ● | ● | ● |
| admin | ● | ● | ● | ● | ● | ○ |
| editor | ● | ○ | ○ | ○ | ○ | ○ |
| author (blog) | blog only | ○ | ○ | ○ | ○ | ○ |
| hr-manager / recruiter | ○ | ○ | ○ | ● | ○ | ○ |
| finance | ○ | ○ | ○ | ○ | ● | ○ |
| consultant | ○ | assigned leads | assigned moves | ○ | ○ | ○ |
| client-manager (portal) | ○ | ○ | org moves | ○ | org invoices | ○ |
| client-employee (portal) | ○ | ○ | own move | ○ | ○ | ○ |

## 6. Build order dependency

```
M0 foundation (auth, roles, settings, media) → M1 CMS core → M2 services/cities content →
M3 leads+careers intake → M4 blog/news/testimonials/csr → M5 portal+billing →
M6 i18n+AI+launch hardening (mobile contract frozen here) → M7 launch
```
Detailed phasing with acceptance criteria: [../09-delivery/01-build-roadmap.md](../09-delivery/01-build-roadmap.md).

---

Related: all module docs below · [../03-technical-specs/02-architecture.md](../03-technical-specs/02-architecture.md) · [../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md)
