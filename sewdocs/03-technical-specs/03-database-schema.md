# 03 — Database Schema

**Complete data model for the Sewa platform. MySQL 8 · utf8mb4 · InnoDB · strict mode. All tables use `id` as ULID (sortable, URL-safe for portal/API references), `created_at/updated_at` timestamps, and soft deletes where content or legal retention needs them.**

> ULID choice: URL-exposable IDs (portal links, API), time-sortable (feeds, invoices), no autoincrement enumeration leaks.

---

## 1. Identity & access

### users
```
id (ULID) pk · name · email (unique) · password · phone · avatar_media_id?
· locale (default 'en') · timezone (default 'Asia/Kolkata') · two_factor_secret? · two_factor_enabled bool
· last_login_at? · status (active|invited|disabled) · created_at · updated_at
```
### roles / permissions (Spatie)
```
roles: id pk · name · guard · slug · display_name
  seed: super-admin, admin, editor, hr-manager, recruiter, finance, consultant, client-manager, client-employee, author
role_user, permissions, role_permissions, model_has_roles, model_has_permissions (Spatie schema)
```
### social_accounts (future), sessions, password_reset_tokens, personal_access_tokens (Sanctum)
```
personal_access_tokens: add scopes column usage discipline ('portal.read', 'app.write'…)
```
### organizations (client companies)
```
id · name · slug · industry? · gstin? · pan? · billing_address json · status · notes
· crm_owner_user_id? (account manager) · created_at…
```
### organization_users
```
organization_id · user_id · role_in_org (manager|employee|billing) · invited_by? · joined_at
```

## 2. CMS module

### pages
```
id · slug (unique) · title · type (standard|landing|legal|about) · parent_id? (tree)
· template (blade view key) · status (draft|scheduled|published|archived)
· published_at · scheduled_at? · meta_title · meta_description · og_image_media_id?
· noindex (bool, default false) · canonical_override? · blocks (json, ordered)
· locale · locale_source_id? (translation group) · author_user_id? · created_by · updated_by
```
**blocks** JSON structure (typed block library — the CMS "Lego"):
```json
[
 {"type":"hero","data":{"headline":"…","sub":"…","media":"<media_id>","cta":{"label":"…","url":"…"}}},
 {"type":"stats","data":[{"value":20000,"suffix":"+","label":"Happy clients","as_of":"2026-08"}]},
 {"type":"cta_band","data":{"headline":"…","button_label":"…","button_url":"…"}},
 {"type":"testimonial_grid","data":{"source":"home","limit":4}},
 {"type":"accordion","data":{"items":[{"title":"…","body_html":"…"}]}},
 {"type":"rich_text","data":{"html":"…"}},
 {"type":"gallery","data":{"media_ids":[…],"layout":"grid|carousel"}},
 {"type":"logos_strip","data":{"group":"memberships"}},
 {"type":"ventures_strip","data":{}},
 {"type":"video","data":{"youtube_id":"…","poster_media_id":"…"}},
 {"type":"leadership_grid","data":{"department":"leadership"}},
 {"type":"faq","data":{"items":[{"q":"…","a":"…"}]}}
]
```
### menus / menu_items
```
menus: id · name · location (header|footer|sitemap) · locale
menu_items: id · menu_id · parent_id? · label · url · target · type (route|page|service|custom) · ref_id? · sort
```
### settings (key-value, site-scope)
```
key (unique) · value (json) · group (brand|contact|seo|integrations|legal) · editable_by_role
  seeds: organization identity JSON ([../01-platform-vision/02-brand-sewa-hospitality.md](../01-platform-vision/02-brand-sewa-hospitality.md) §9),
  NAP, social links, offices list pointer, counters, membership badges, analytics ids
```
### redirects
```
id · from (unique, normalized path) · to · code (301|302) · hits · note · active
```
### media (Spatie medialibrary) — `media` table per package + app columns:
```
alt_text (required at upload) · credit? · focal_point? · folder namespace (brand|services|cities|blog|team|csr|testimonials|portal)
```

## 3. Services & Cities

### services
```
id · slug · family (employee-mobility|business-mobility|standalone) · parent_id? (self-tree)
· name · short_desc · hero_media_id? · intro · content_blocks (json, same block library)
· faq (json) · icon_svg_key? · status · sort · lead_tag (e.g. "housing.corporate")
· meta_title · meta_description · noindex · locale · locale_source_id? · cta_label_override?
```
### service_offices (pivot service↔office, optional coverage nuance), `service_related` (pivot service↔service)
### cities
```
id · slug · name · state · country ('IN') · lat · lng · is_hub (bool)
· description · content_blocks (json) · hero_media_id? · population? · cost_index?
· status (draft|published) · locale · locale_source_id? · meta_title · meta_description · noindex
```
### city_services (pivot city↔service with localized notes: "Fleet: 40 vehicles in Pune")
### housing_units (inventory — serviced apartments & corporate housing)
```
id · city_id · type (serviced-apartment|corporate-housing|guest-house) · name · area · locality
· bedrooms · tier (essential|professional|executive) · status
· from_rate (int, INR) · rate_unit (night|month) · amenities (json) · media_ids
· verified_at (Sewa-verified badge date) · verified_by_user_id · managed_by? (vendor)
· published (bool) · notes
```

## 4. Content: blog/news + HR + careers

### posts
```
id · slug (unique per type+locale) · type (blog|news) · title · excerpt · body (html)
· cover_media_id? · status (draft|scheduled|review|published) · published_at · scheduled_at
· author_user_id (NOT nullable — "admin-anonymous" banned) · review_notes?
· canonical? · meta_title · meta_description · noindex · locale · locale_source_id?
· reading_time (computed) · word_count (computed) · doi? (no)
```
### categories
```
id · slug · name · parent_id? (nested) · description? · meta… · locale · locale_source_id?
  seeds mirror the reference's 15 categories (Expat News, Lifestyle in India, Relocation, Moving, Visa & Immigration News, Corporate Housing, Health & Safety, Global Mobility, Fleet, News, Expat in India, Relocation Guide to India, Uncategorized) — plus Sewa's additions (City Guides, Immigration Explainers)
```
### category_post / tag / tag_post (tags: per-post only; no sitewide cloud injection)
### authors are users (role author) + `author_profiles`:
```
user_id pk · title? · bio · credentials (json: certifications, years, languages) · linkedin?
· photo_media_id? · is_public (bool: shows on About/team + bylines)
```
### job_postings
```
id · slug · title · department (enum: relocation|immigration|fleet|housing|finance|hr|ops|tech)
· location_city_id? · location_text · employment_type (full|part|contract|intern)
· experience_min · experience_max · description_html · responsibilities_html · skills_html
· status (draft|open|paused|closed) · closes_at? · posted_by_user_id · published_at
· applies_to_email (default careers@) · locale · locale_source_id? · sort
```
### job_applications
```
id · job_posting_id? · applicant_name · applicant_email · applicant_phone
· resume_media_id · cover_message · source (site|campaign) · status (new|screening|shortlisted|interview|offer|hired|rejected|withdrawn)
· rating? · notes (json log) · idempotency_key (unique) · consent_at (data-processing consent)
· created_at…
```
### employees (HR module — internal directory)
```
id · user_id? (nullable until account exists) · employee_code · full_name
· designation · department · joined_at · employment_type · office_city_id?
· is_public (bool: leadership/team page) · bio · credentials (json) · photo_media_id?
· manager_employee_id? · status (active|notice|alumni)
```
### employee_documents / leave records / appraisals (phase 2 — schema reserved, not built)

## 5. Leads & CRM

### leads
```
id · source (contact|service_page|career_newsletter|portal_request|campaign|import)
· type (enquiry|newsletter|callback|quote_request|demo)
· name · email · phone · company? · message · service_id? · city_id? · locale
· status (new|contacted|qualified|proposal|won|lost|nurture) · lost_reason?
· assigned_user_id? · score (0-100, AI-assisted optional) · enrichment (json)
· idempotency_key (unique) · consent_at · ip (hashed) · user_agent
· sla_due_at (computed by source rules) · first_response_at? · created_at…
```
### lead_events (activity log: status changes, notes, emails, calls)
```
id · lead_id · user_id? · type (note|status|email|call|sms|form|assign) · payload json · created_at
```
### newsletter_subscribers
```
id · email (unique) · status (pending|confirmed|unsubscribed|bounced) · token · locale
· confirmed_at? · unsubscribed_at? · source · created_at
```

## 6. Testimonials & reviews

### testimonials
```
id · client_name · client_role? · company? · city_id? · service_id? · body · rating (1-5)?
· source (google|direct|email|form) · source_url? · verified_at? · published_at
· status (pending|published|archived) · media? · locale · locale_source_id?
```
### google_reviews (sync cache from GBP API or provider)
```
id · external_id (unique) · rating · text · reviewer · review_at · url · fetched_at · synced
```

## 7. CSR

### ngo_partners
```
id · name · slug · logo_media_id · website · description · focus_areas (json)
· since? · city? · status · sort · locale · locale_source_id?
```
### csr_stories
```
id · slug · title · body · media_ids · ngo_partner_id? · published_at · status · locale…
```

## 8. Client portal & operations

### portal_move_records (a relocation engagement)
```
id · organization_id · employee_user_id? · primary_consultant_user_id?
· assignee_name · assignee_email · origin_city · destination_city_id? · move_date
· stage (intake|planning|in-progress|settling|complete|closed) · status · summary
· service_ids (json) · timeline (json: milestones with due/done) · created_at…
```
### portal_documents
```
id · move_record_id? · organization_id · user_id? · title · media_id · category (visa|lease|inventory|invoice|other)
· visible_to (employee|manager|both) · uploaded_by · expires_at? · created_at
```
### portal_messages / portal_threads
```
threads: id · move_record_id? · organization_id? · subject? · status (open|closed)
messages: id · thread_id · sender_user_id? · sender_role (client|consultant|system)
· body · media_ids? · read_at? · created_at
```
### portal_checklist_items (task tracking shown in portal + admin)
```
id · move_record_id · title · detail? · due_at? · done_at? · done_by? · sort · status
```

## 9. Billing

### quotes / invoices
```
quotes: id · number (SEWA-Q-YYYY-####) · organization_id · move_record_id? · lead_id?
        · status (draft|sent|accepted|expired|rejected) · lines (json: desc/qty/rate/tax) · total · currency 'INR'
        · valid_until · sent_at · accepted_at? · created_by · notes
invoices: id · number (SEWA-I-YYYY-####) · quote_id? · organization_id · status (draft|sent|paid|partial|overdue|void)
        · lines json · subtotal · tax_breakdown json · total · due_at · paid_at? · notes
        · sequential lock (see idempotent numbering under [05-security-reliability.md](05-security-reliability.md))
invoice_payments: id · invoice_id · method (bank|upi|cheque|gateway) · amount · paid_at · reference · recorded_by
```

## 10. I18n & AI

### locales
```
code (pk: en|hi|ja|ko|tr|ar|…) · name · native_name · direction (ltr|rtl) · enabled · fallback_for? · auto_translate (bool)
```
### translations (key-value cache for UI strings)
```
locale · namespace (site|portal|admin|email) · key · value · status (machine|human-reviewed)
· reviewed_by? · updated_at  (unique: locale+namespace+key)
```
Content entities carry `locale + locale_source_id` (translation groups) instead of separate tables.

### ai_invocations (budget + audit)
```
id · user_id? · feature (translate|enrich|summarize|draft|score) · provider · model
· tokens_in · tokens_out · cost_estimate · status (ok|fallback|error) · latency_ms
· created_at   (no content stored unless feature requires; retention 90 days)
```

## 11. Audit & ops

### activity_log
```
id · user_id? · context (admin|portal|api) · action (create|update|delete|login|export|publish)
· subject_type · subject_id · changes (json diff, sensitive fields redacted) · ip · user_agent · created_at
```
### jobs / failed_jobs / scheduled_command_locks (Laravel + cache locks)
### pulse tables (Laravel Pulse default)

## 12. Indexes & integrity rules (summary)

- Every `slug` unique-scoped as documented; every `locale` pair unique with source.
- Foreign keys declared explicitly (no implicit); `ON DELETE RESTRICT` for anything legal/financial (invoices, applications, leads).
- Search indexes: MySQL FULLTEXT on posts(title, body), cities(name, description), housing_units(name, locality, amenities), services(name, short_desc); Scout DB driver maps these.
- `leads.idempotency_key`, `job_applications.idempotency_key` UNIQUE — network retries safe.
- Invoice/quote numbering via `SELECT … FOR UPDATE` inside transaction (no duplicate numbers under concurrency).
- Soft deletes: pages, posts, services, cities, testimonials, ngo_partners, housing_units. Hard for audit/financial logs with retention policies instead.
- Migrations: zero destructive migration without a paired backup note + rollback plan ([13-testing-qa.md](13-testing-qa.md)).

## 13. Seed data (first deploy)

- Locales: en, hi, ja, ko, tr, ar (RTL) — enabled per launch plan ([../06-content-seo/04-multilingual-content.md](../06-content-seo/04-multilingual-content.md)).
- Categories: reference's 15 equivalents + Sewa additions (City Guides, Immigration Explainers, Housing Market Notes).
- Roles/permissions matrix ([../04-modules/05-admin-panel.md](../04-modules/05-admin-panel.md)).
- Settings: full organization identity JSON, NAP, socials, offices (Gurugram HQ + phased adds).
- Demo-blocking: no fake content in production DB; staging gets fixtures.

---

Related: [02-architecture.md](02-architecture.md) · [04-api-spec.md](04-api-spec.md) · [05-security-reliability.md](05-security-reliability.md) · module specs in [../04-modules/](../04-modules/)
