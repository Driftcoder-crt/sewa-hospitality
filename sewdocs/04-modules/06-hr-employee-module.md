# 06 — HR & Employees Module (Careers + Internal HR)

**Public careers site with real per-job pages and an application ATS, plus the internal HR layer (employees, authors, team visibility) that powers the About page and blog authorship — fixing the reference's 404 job details, "admin" authors, and hover-only leader bios.**

---

## 1. Purpose
Two faces, one module: (a) the hiring funnel — job postings, applications, screening pipeline; (b) the people registry — employees, leadership profiles, author profiles — feeding the About page, service-page consultants, and blog bylines. Real names, real people, real E-E-A-T.

## 2. Data model
`job_postings`, `job_applications`, `employees`, `author_profiles` ([../03-technical-specs/03-database-schema.md](../03-technical-specs/03-database-schema.md) §4). Resume files live in the media library `careers/` namespace; `consent_at` recorded on every application.

## 3. Public surface

| Route | Spec |
|---|---|
| /careers | intro, life-at-Sewa (real culture content + gallery), open-roles list by department/city, application CTA |
| /careers/{slug} | **per-job page** (the reference 404s these): role, dept, location, experience range, responsibilities, skills, apply form (name*, email*, phone*, resume file ≤ 5MB*, message), share links, related openings |
| /about#team | leadership grid (from employees.is_public) — clickable profiles, not hover-only bios |
| /team/{person} (optional) | profile pages for leadership/consultants/authors: role, bio, credentials, languages, LinkedIn, authored posts |
| Author bylines | every blog post renders its author's profile link ([07-blog-news.md](07-blog-news.md)) |

## 4. Admin surface
1. **Job postings** — CRUD with status machine (draft → open → paused → closed), closing dates, location/city link, department taxonomy, locale variants; editor with live preview.
2. **Applications (ATS-lite pipeline)** — new/screening/shortlisted/interview/offer/hired/rejected/withdrawn kanban with resume preview (signed URL), notes log, rating, email templating for status updates, `source` tracking (which job page/campaign), duplicate detection (same email across postings).
3. **Employees** — internal directory: code, designation, department, manager, office, join date, employment type, `is_public` flag (marketing visibility) + public bio/credentials/photo editing for leadership; future-proof columns (leave docs) reserved but not built (schema §4).
4. **Author profiles** — linked to users with `author` role; bio, credentials, photo, LinkedIn; feeds blog bylines + schema `Person` ([../06-content-seo/02-seo-technical.md](../06-content-seo/02-seo-technical.md)).
5. **Team visibility** — curate who appears on About/leadership grids and service-page consultant cards (per-service assignment).

Permissions: hr-manager (all HR), recruiter (applications pipeline), editor (team page content only), admin+.

## 5. Behavior & rules
- **Job status machine:** closed postings keep their URL (history/SEO) but render a "closed — see similar" state with links; never a 404.
- **Application gate:** incomplete uploads blocked with friendly guidance; idempotency key prevents double-applies; consent logged with policy version.
- **Candidate UX:** application ack email (their language if translation ready) with what-happens-next + data-retention note; interview-stage status emails via template.
- **Authorship rule:** posts cannot publish without a human author (the "admin author" defect is structurally impossible); authors get credit pages aggregating their posts.
- **Hiring analytics:** time-in-stage, source effectiveness, offer-accept rate — admin reports.

## 6. Error handling
- Resume upload failure → resumable retry; application state preserved (never lose a candidate's typed message — reference defect pattern eliminated).
- Malformed resume (corrupt PDF) → rejected at upload with clear reason; screening tool never sees it.
- ATS email failures → queue retries ([../03-technical-specs/10-email.md](../03-technical-specs/10-email.md) catalog) + ops digest; candidate still receives portal-free confirmation via fallback transport.

## 7. Events & integrations
Emits: `ApplicationReceived`, `ApplicationStatusChanged`, `JobOpened/Closed`. Listens: none inbound (leads flow separately). Integrations: media library (resumes), email catalog (application.ack/received, status updates), AI enrichment optional (CV→summary assist for recruiters — guarded, human-approved; [../08-ai-system/02-ai-use-cases.md](../08-ai-system/02-ai-use-cases.md)).

## 8. Tests
Per-job pages render for all statuses (open/closed); application validation + idempotency; resume limits/mimes; status machine + emails; author-required publish gate; `is_public` filtering; duplicates merge review; consent logging; employee directory permission scoping.

---

Related: [00-module-system.md](00-module-system.md) · [01-cms.md](01-cms.md) (blocks: leadership_grid) · [07-blog-news.md](07-blog-news.md) (authorship) · [../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) · Reference defects: [../02-formula-reference/06-weaknesses-opportunities.md](../02-formula-reference/06-weaknesses-opportunities.md) D6–D7
