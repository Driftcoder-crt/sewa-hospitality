# 04 — REST API Specification (v1)

**api.sewahospitality.com/v1 — versioned, typed, mobile-app-ready. One contract powers the public site's dynamic needs, the client portal, the admin's data endpoints, and the future mobile apps.**

---

## 1. Conventions

| Concern | Rule |
|---|---|
| Base | `https://api.sewahospitality.com/v1` |
| Auth | Sanctum: `Authorization: Bearer <token>`; public reads = guest; writes = token or Turnstile-protected session |
| Scopes | `public.read`, `portal.read`, `portal.write`, `app.read`, `app.write`, `admin.read`, `admin.write` |
| Envelope | success: `{ "data": …, "meta": { "pagination": {…} } }` · error: `{ "error": { "code": "validation_failed", "message": "…", "details": {field: [msgs]} } }` |
| Pagination | cursor-based for feeds (`?cursor=`), page-based for admin tables; limits capped (100) |
| Content | `application/json`; uploads `multipart/form-data` |
| Idempotency | `Idempotency-Key` header required on POST writes from app clients (ULID; stored 24h) |
| Rate limits | public reads 60/min/IP · writes 5/min/IP + Turnstile · portal 120/min/user · app 240/min/token (429 + Retry-After) |
| Caching | ETag + If-None-Match on GETs (304s); `Cache-Control: public, max-age=60` on anonymous reads |
| Versioning | `/v1` frozen after release; breaking changes → `/v2` with 12-month overlap |
| Errors | HTTP codes: 400 validation, 401 auth, 403 scope, 404, 409 conflict, 422 business rule, 429 throttle, 5xx (JSON body always) |
| Docs | OpenAPI 3.1 generated from code (route annotations), admin-only viewer |

**What we fix vs. the reference API:** versioned routes (reference: none), typed envelopes (reference: `{status, stausCode (typo), msg, data-as-string}`), no double-encoded data, idempotency (reference: none — double-submits possible), rate limits (reference: none visible), scopes, real pagination, real error codes.

## 2. Public endpoints (site + app shared)

### Content
| Method | Path | Returns |
|---|---|---|
| GET | `/v1/services` | services tree (families, children, hero media refs) |
| GET | `/v1/services/{slug}` | service detail incl. faq, related, locales map |
| GET | `/v1/cities` | cities (hub first), pagination |
| GET | `/v1/cities/{slug}` | city detail + services coverage + housing counts |
| GET | `/v1/housing` | housing_units filters: `city, type, tier, bedrooms, max_rate` |
| GET | `/v1/posts` | posts feed; filters: `type=blog|news, category, tag, author, year, month`; cursor |
| GET | `/v1/posts/{slug}` | post detail (author object, categories, related) |
| GET | `/v1/categories` / `tags` | taxonomies with counts |
| GET | `/v1/testimonials` | published testimonials; filters: `service, city, source` |
| GET | `/v1/ngo-partners` | CSR partners |
| GET | `/v1/jobs` | open job postings; filters `department, city, type` |
| GET | `/v1/jobs/{slug}` | job detail |
| GET | `/v1/team` | public team/leadership (is_public=1) |
| GET | `/v1/search?q=` | unified search (Scout): services, cities, posts, housing, jobs — grouped hits |
| GET | `/v1/health` | `{ status: "ok", time }` (UptimeRobot target; no auth) |

Media URLs in payloads are absolute `https://media.sewahospitality.com/…` with alt text included (`{url, alt, width, height, variants:{thumb,card,hero}}`) — alt text is first-class, unlike the reference.

### Public writes (all Turnstile + honeypot + rate-limited + idempotent)
| Method | Path | Body |
|---|---|---|
| POST | `/v1/leads` | `{name, email, phone, company?, message, service_slug?, city_slug?, locale, source, consent: true, newsletter?: bool}` → 201 `{data:{lead_ref}}` |
| POST | `/v1/applications` | multipart: `job_slug, name, email, phone, cover_message, resume (file), consent, source` → 201 |
| POST | `/v1/newsletter/subscribe` | `{email, locale, source}` → 202 (double opt-in email queued) |
| POST | `/v1/quote-requests` | `{name, email, phone, company, service_slug, city_slug?, requirements, budget_hint?}` → 201 |

## 3. Portal endpoints (app.sewa… + future mobile app; scope `portal.*`)

### Auth
| Method | Path | Notes |
|---|---|---|
| POST | `/v1/auth/login` | `{email, password, device_name}` → `{token, user, organizations[]}`; 2FA flow: `/v1/auth/2fa/verify` |
| POST | `/v1/auth/forgot-password` | always 202 (no account enumeration) |
| POST | `/v1/auth/reset-password` | token flow |
| POST | `/v1/auth/logout` | revoke current token |

### Moves & documents
| Method | Path | Notes |
|---|---|---|
| GET | `/v1/me` | profile + roles + organizations |
| GET | `/v1/moves` | my move records (manager sees org's moves) |
| GET | `/v1/moves/{id}` | timeline, stage, services, checklist |
| GET | `/v1/moves/{id}/documents` | docs visible to caller's role |
| GET | `/v1/documents/{id}/download` | signed, expiring URL; audit-logged |
| GET/POST | `/v1/threads` `/v1/threads/{id}/messages` | chat; POST triggers realtime broadcast |
| GET | `/v1/notifications` · PATCH `/v1/notifications/{id}/read` | notification center |

### Billing (client view)
| Method | Path | Notes |
|---|---|---|
| GET | `/v1/invoices` | org invoices (manager/billing role) |
| GET | `/v1/invoices/{id}` | lines + payments |
| GET | `/v1/invoices/{id}/download` | PDF stream |

## 4. App-ready event stream (for future mobile push)

Realtime events are named and versioned from day one (broadcast over Ably; mobile app subscribes later):
```
move.stage_changed    {move_id, stage, at}
move.checklist_done   {move_id, item_id, done_at}
message.created       {thread_id, message_id, sender_role}
document.published   {move_id, document_id, category}
invoice.issued        {invoice_id, organization_id}
notification.created {user_id, type, title, body, deep_link}
```
Transport detail: [11-realtime.md](11-realtime.md). Mobile contract: [../04-modules/13-mobile-readiness.md](../04-modules/13-mobile-readiness.md).

## 5. Admin data endpoints

Admin UI is Livewire (not an API consumer), but a small, token-scoped admin API exists for internal integrations (BI exports, bulk imports):
```
GET  /v1/admin/leads?status=&assigned=…      (export: /v1/admin/leads.csv)
GET  /v1/admin/applications?status=…
GET  /v1/admin/analytics/summary             (counts, funnel, SLA breaches)
POST /v1/admin/imports/{entity}              (bulk, async via queue)
```
Guarded by `admin.read`/`admin.write` scopes + role checks; every call audit-logged.

## 6. Webhooks (outbound, Phase 2 — reserved)

```
POST {org-configured URL}  invoice.issued / move.stage_changed (HMAC-SHA256 signed, secret per org)
```
Design now, enable later — portal notifications already cover the base need.

## 7. Request/response examples

**Create lead (public write):**
```http
POST /v1/leads
Content-Type: application/json
Idempotency-Key: 01J8Z…
X-Turnstile-Token: …

{ "name": "Kim Minjun", "email": "minjun@corp.kr", "phone": "+82…",
  "company": "Hyundai…", "message": "Relocating 3 engineers to Pune…",
  "service_slug": "employee-mobility/relocation", "city_slug": "pune",
  "locale": "ko", "source": "contact-form", "consent": true }
```
```json
201 Created
{ "data": { "lead_ref": "LD-8K2F…", "sla_hours": 2 },
  "meta": { "message": "요청이 접수되었습니다" } }
```
Errors:
```json
422 { "error": { "code": "validation_failed", "message": "…",
       "details": { "email": ["Enter a valid email address."] } } }
429 { "error": { "code": "rate_limited", "message": "…" } }
```

**Get services tree:**
```json
200
{ "data": [
  { "slug": "employee-mobility", "name": "Employee Mobility", "children": [
      { "slug": "relocation", "name": "Relocation Services", "lead_tag": "relocation",
        "hero": { "url": "https://media…/hero.webp", "alt": "…" } }, … ] },
  { "slug": "business-mobility", "children": [ … ] } ] }
```

## 8. Validation rules (shared form-request library)

| Field | Rules |
|---|---|
| name | `string min:2 max:120` |
| email | `email:rfc,dns max:190` |
| phone | `nullable` E.164-ish `regex:/^\+?[0-9\s-]{7,18}$/` (Indian + international patterns) |
| message | `string min:10 max:5000` |
| resume | `file mimes:pdf,doc,docx max:5120` (5 MB; stored via media library with antivirus-scan hook) |
| locale | `in:en,hi,ja,ko,tr,ar` (+ configurable) |
| consent | `accepted` (DPDP-compliant consent logging: consent_at + policy version) |

## 9. Security requirements for this API

Turnstile on public writes, honeypot field, idempotency store (24h), per-IP + per-token throttles, scope enforcement middleware, no CORS wildcard (allowlist: sewahospitality.com, app., admin., plus dev origins behind flag), signed document URLs (15-min expiry), full audit trail on admin scope. Deep-dive: [05-security-reliability.md](05-security-reliability.md).

---

Related: [02-architecture.md](02-architecture.md) · [03-database-schema.md](03-database-schema.md) · [05-security-reliability.md](05-security-reliability.md) · [11-realtime.md](11-realtime.md) · [../04-modules/13-mobile-readiness.md](../04-modules/13-mobile-readiness.md)
