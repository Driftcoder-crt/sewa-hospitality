# 03 — Reference API & Data Layer

**How the reference platform's data actually works: every endpoint, payload, and data flow. This is the map of "how data is written" that Sewa replaces with a proper API.**

---

## 1. Architecture

```
Browser ──> www.formulaindia.com (Next.js SSP)
                │  server-side fetches
                ▼
           api.formulaindia.com/api/…      (REST API — the content source)
                │  media
                ▼
           api.formulaindia.com/upload/media/{size}/{md5}.jpg
Browser ──> /blog/  (WordPress — own DB)
Browser ──> /login/ (CodeIgniter portal — own DB + ci_session)
```

Three persistence systems, no shared identity, no shared design data. Sewa collapses all of this into one Laravel application + one MySQL database + one auth (spec: [../03-technical-specs/04-api-spec.md](../03-technical-specs/04-api-spec.md)).

## 2. Write endpoints (how data gets INTO the reference system)

### 2.1 Contact form
```
POST https://api.formulaindia.com/api/contactus
Content-Type: application/json
{
  "firstname":  "…",           // input name=firstname
  "lastname":   "…",           // name=lastname
  "mobilenumber": "…",         // name=mobilenumber
  "email":      "…",
  "service":    "RELOCATION SERVICES" | … | "General Enquiry",  // from <select name="subject">
  "message":    "…"
}
```
Behavior: client-side validation only → fetch POST → success redirects `/thankyou`, error redirects `/contact-us`. reCAPTCHA wrapper present in the page bundle (sitekey via props). **No server contract visible: no idempotency, no visible rate limiting, silent failure mode** — a network error just bounces the user back to the form with their typed data lost.

### 2.2 Career application
```
POST https://api.formulaindia.com/api/postcareers
Content-Type: multipart/form-data
FormData fields:
  file:      <resume>        // from <input type=file name=resume>
  fullname:  "…"
  email:     "…"
  number:    "…"             // mobile
  message:   "…"
  position:  "JOB TITLE"     // injected from the Apply button's data attribute
```
Behavior: axios POST (0.22) → `/thankyou`. Same silent-failure weaknesses; resume file type/size validation is client-side only (no visible server contract).

### 2.3 Client portal auth
```
POST https://www.formulaindia.com/login/login          {loginEmail, loginPassword} → JSON {status:1} → dashboard
POST https://www.formulaindia.com/login/forgetpassword {loginEmail}                → inline success/error (3s)
```
CodeIgniter `ci_session` cookie (SameSite=Lax). Error messages self-destruct after 3 seconds. Password reset presumably emails a link (endpoint exists; mail flow unobserved).

**Sewa replacements:** [../04-modules/03-leads-crm.md](../04-modules/03-leads-crm.md) (leads), [../04-modules/06-hr-employee-module.md](../04-modules/06-hr-employee-module.md) (applications), [../04-modules/04-client-portal.md](../04-modules/04-client-portal.md) (auth) — all with server-side validation, Turnstile, rate limits, queues, idempotency, audit logs.

## 3. Read endpoints (how content gets OUT)

Discovered via the Next.js `__NEXT_DATA__` payloads embedded in each page (the API responses were server-fetched at request time):

| Page | API data (inferred endpoint family) | Payload shape |
|---|---|---|
| Home | testimonials, latest blog post, services | `testimonials[]: {id, name, degination (sic), testimonials, location, status, is_home, doe}`; `services[]: {id, title, nickname, image, alt_tag, description, parent, status, sortorder, is_home, meta_title, meta_keyword, meta_desc, doe, ordering}` |
| Services hub | postData → services with `{title, id, nickname, parent, parentname, parentnickname, description, image}` |
| Careers | postCareers → `careers[]: {id, title, location, exp, skills (HTML), responsibility (HTML), status, doe}` |
| About | postLeadership → `milestones[]: {id, name, description, designation, image, doe, status, ordering}` (15 leaders) |
| News | postNews → `{id, title, nickname, details, date, image, alt_tag, archieved (sic), status, meta_title, meta_keyword, meta_desc, doe}` |
| Clients Speak | posts → testimonials[] (same shape as home; is_home false) |

**API response envelope (every endpoint):**
```json
{ "status": true, "stausCode": 200, "msg": "…", "data": "<JSON-encoded string>" }
```
Defects worth noting: `stausCode` typo burned into the contract; `data` is a *string* that clients must JSON-parse again; no pagination metadata on testimonial/career feeds; `degination`/`archieved` field typos; no API versioning; no docs; CORS-open media.

## 4. Media pipeline

- Uploads land at `api.formulaindia.com/upload/media/` with **MD5-hash filenames**; 4 derivative sizes: original path, `/large`, `/medium`, `/thumb`.
- 179 media files mirrored (~service heroes, leader headshots, career gallery 39 photos, news thumbs, testimonial imagery).
- The main site uses next/image with an optimizer (`/_next/image?url=…&w=…&q=75`), while the portal and blog use raw sizes — inconsistent delivery discipline.
- No alt management at the CDN level: alt text lives in the API records (some empty).

**Sewa media spec:** [../03-technical-specs/09-media-pipeline.md](../03-technical-specs/09-media-pipeline.md) — Spatie Media Library conversions (thumb/card/hero), WebP/AVIF, alt-text required at upload, `media.sewahospitality.com` CDN with immutable hash URLs + Cloudflare caching, focal-point cropping.

## 5. Client-side storage

- **No localStorage/sessionStorage usage found anywhere** — the reference stores nothing client-side (state resets on every visit).
- Cookies: only `ci_session` (portal) + analytics cookies (GA4 gtag, Clarity, FB Pixel — see [05-seo-content-analysis.md](05-seo-content-analysis.md) § tracking).
- Sewa: consent-gated analytics cookies (GDPR/DPDP-friendly), locale preference persisted, form drafts in localStorage, nothing sensitive client-side ever.

## 6. Data model implied by the reference (Sewa formalizes it)

| Reference concept | Sewa entity |
|---|---|
| `services` tree (parent/nickname) | `services` (self-referencing parent, slug, families, content blocks, SEO, i18n) |
| `testimonials` (name/degination/location/is_home) | `testimonials` (+ review source, date, service link, media) |
| `careers` (title/location/exp/skills/responsibility) | `job_postings` (+ department, type, status, closing date, i18n) |
| `leadership` (name/designation/description/ordering) | `team_members` (unified for leadership, authors, consultants) |
| `news` (title/nickname/details/date/meta_*) | `posts` (unified blog+news entity, type flag, meta, i18n) |
| WP posts/categories/tags | `posts`, `categories` (nested sets), `tags` |
| Portal users (ci_session) | `users` + roles (Spatie) + Sanctum tokens |

Full schema: [../03-technical-specs/03-database-schema.md](../03-technical-specs/03-database-schema.md).

## 7. Contract Sewa's API must honor (improvements over the reference)

1. **Versioned** (`/v1`) — the reference has zero versioning; any change is a breaking change.
2. **Typed envelopes** — `{data, meta{pagination}, error{code,message}}`; no string-in-string; no typo'd keys.
3. **Mobile-ready from day one** — the reference's API is server-internal; Sewa's `/v1` powers site, portals, and future apps with one contract ([../04-modules/13-mobile-readiness.md](../04-modules/13-mobile-readiness.md)).
4. **Auth** — Sanctum tokens with scopes (public read vs portal vs app).
5. **Rate limits + Turnstile** on all public writes ([../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md)).
6. **Idempotency keys** on application/lead submissions (retry-safe network conditions — India mobile networks demand it).
7. **OpenAPI document** generated from code, published to admin only (reference has nothing).
8. **ETag/If-Modified-Since** on content reads (shared hosting CPU discipline).

---

Related: [01-site-map-and-pages.md](01-site-map-and-pages.md) · [02-components-interactions.md](02-components-interactions.md) · [../03-technical-specs/04-api-spec.md](../03-technical-specs/04-api-spec.md) · [../03-technical-specs/03-database-schema.md](../03-technical-specs/03-database-schema.md)
