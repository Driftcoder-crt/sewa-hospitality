# 04 — Subdomains, Domains & Venture Architecture

**How sewahospitality.com is divided — and how it grows without ever fragmenting.**

---

## 1. The reference's domain mess (what we fix)

The reference company operates at least 8 web properties:

| Property | Purpose |
|---|---|
| formulaindia.com | Main corporate site (Next.js) |
| api.formulaindia.com | REST API + media CDN |
| formulaindia.co.jp | Japanese market site (flag button on header) |
| formulahousing.com | Corporate housing venture |
| movewithformula.com | Moving venture |
| formulaservicedapartment.com | Serviced apartments venture |
| formulacarrental.com | Car rental venture |
| thetravelformula.com | Travel venture |
| suraksha4u.com | Sanitization venture |

**Problems with that model (our opportunity):**
- SEO equity is split 8 ways. A new content page on formulaindia.com does nothing for the housing site's rankings — and vice versa.
- The main site *pushes users away* (services deep-link out to sister sites), losing the visitor.
- 8 sites = 8 stacks, 8 CMSs, 8 design systems to maintain (the mirror shows design drift between them already).
- A Japanese site is the *only* international-language presence — no Korean, Turkish, or Arabic at all, despite those markets.

**Sewa's answer:** one domain, one platform, one design system; subdomains only where there is a *technical* reason; path-based structure for everything content-related; multilingual done properly in-code instead of with a separate .jp site.

## 2. Sewa domain architecture (locked)

```
sewahospitality.com                 → Main marketing + content platform (Laravel)
├── /services/...                   → Service pages (Section 03 catalog)
├── /housing/...                    → Housing inventory & city pages
├── /blog/...                       → Blog (path, not subdomain)
├── /news/...                       → News
├── /cities/...                     → City program (all-India)
├── /careers                        → Careers public pages
├── /csr                            → CSR program
├── /about, /contact, /legal/...    → Corporate pages
├── /ko/ /ja/ /tr/ /ar/...          → Locale path prefixes (hreflang-linked)
│
api.sewahospitality.com             → Versioned REST API (/v1) — app-ready
admin.sewahospitality.com           → Admin panel (staff only, IP + 2FA policies)
app.sewahospitality.com             → Client portal (relocating employees & corporate clients)
media.sewahospitality.com           → Media CDN (Spatie conversions, Cloudflare cache)
```

### Why each choice

| Decision | Reasoning |
|---|---|
| Blog at `/blog/` path | Keeps all ranking equity on the root domain; a `blog.` subdomain would restart authority from zero. Same for `/cities/`, `/housing/`. |
| `api.` subdomain | Clean separation for future mobile app + external integrations; CORS-locked; independent rate limits; can be pointed at a VPS later without touching the main site. |
| `admin.` subdomain | Security isolation: separate cookie domain, stricter CSP, optional IP allowlist; admin traffic never mixes with public sessions. |
| `app.` subdomain | Client portal needs its own auth zone + realtime connections; looks like a product, feels like a product, and can later be handed to a mobile shell app. |
| `media.` subdomain | Cookieless static domain = maximal Cloudflare/browser caching; images served from `media.` with cache-everything + WebP/AVIF conversions. |
| Locale paths (`/ja/...`) not subdomains | hreflang alternates work cleanly; one sitemap covers all; cheaper to operate; the reference's .jp approach abandons the main domain — we don't. |

## 3. Subdomain technical policies

### api.sewahospitality.com
- Laravel routes under `routes/api.php`, versioned `/v1/*` (full spec: [../03-technical-specs/04-api-spec.md](../03-technical-specs/04-api-spec.md)).
- CORS allowlist: sewahospitality.com, app.sewahospitality.com, admin.sewahospitality.com (no wildcard).
- Rate limited per IP + per token (throttle groups documented in [../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md)).
- Health endpoint `/v1/health` for UptimeRobot.

### admin.sewahospitality.com
- Staff auth only (Spatie roles: super-admin, admin, editor, hr, finance, consultant).
- 2FA required for super-admin/admin; session lifetime 2h idle; login rate-limited + Turnstile.
- CSP: no third-party scripts except analytics self-hosted config; no public CDN dependencies (SRI anyway).

### app.sewahospitality.com (client portal)
- Two client personas in one portal: **corporate client (HR/mobility manager)** and **relocating employee** — role-scoped views (module spec: [../04-modules/04-client-portal.md](../04-modules/04-client-portal.md)).
- Realtime: Ably-backed notifications + chat with native polling fallback ([../03-technical-specs/11-realtime.md](../03-technical-specs/11-realtime.md)).
- Sanctum sessions; "remember this device" opt-in.

### media.sewahospitality.com
- Origin: Laravel storage (public disk) via Spatie Media Library conversions; fronted by Cloudflare (free tier, cache rules documented in [../03-technical-specs/09-media-pipeline.md](../03-technical-specs/09-media-pipeline.md)).
- Naming: deterministic media IDs + conversion suffixes (`-thumb`, `-card`, `-hero`); no user-controlled filenames.
- Reserved path namespaces: `/brand/`, `/services/`, `/cities/`, `/blog/`, `/testimonials/`, `/csr/`, `/team/`.

## 4. Future venture architecture (the escape plan)

**Principle: promote to a subdomain site only when a service line has (a) real dedicated revenue, (b) dedicated staff, (c) enough content to survive as a standalone authority.** Until then, everything lives under the master domain as a strong section.

When promotion happens (the "Sewa Housing" moment):

1. Create `housing.sewahospitality.com` (or a new brand domain with clear interlinking).
2. **Migrate content with 301s from `/housing/...` paths** — one-time, Search-Console-verified move (this is the payoff of path-first: a clean 301 map).
3. Share the platform: same Laravel codebase, same admin (multi-tenant `site_id` on CMS entities — schema already supports this, see [../03-technical-specs/03-database-schema.md](../03-technical-specs/03-database-schema.md)).
4. Share the design tokens (Tailwind theme package) so brand stays coherent.
5. Interlink "Provided by Sewa Hospitality" on every promoted page — the parent brand always benefits.

This is deliberately the *reverse* of the reference model: they split on day one and spent years paying for it; we consolidate first and split with proof.

## 5. Email domains & deliverability

| Address | Purpose |
|---|---|
| hello@sewahospitality.com | Public enquiries (Leads module) |
| support@sewahospitality.com | Portal/ops replies |
| careers@sewahospitality.com | Applications (HR module) |
| no-reply@sewahospitality.com | Transactional (auth, notifications) |
| billing@sewahospitality.com | Invoices/finance |

Sending via Resend/Brevo with SPF, DKIM, DMARC configured on the domain (full spec: [../03-technical-specs/10-email.md](../03-technical-specs/10-email.md)). Subdomains can carry their own mail identity later (e.g. `mail.housing.…`) — schema decision documented, not built now.

## 6. DNS & Cloudflare layout (free plan)

```
sewahospitality.com        A      → Hostinger shared IP (orange-cloud/proxied)
*.sewahospitality.com      A      → same (proxied)   [api, admin, app, media]
sewahospitality.com       MX/SPF/DKIM/DMARC → mail provider records
media                       → Cache Everything + Edge TTL 1 month (immutable, hash URLs)
admin                       → Cloudflare Access optional later (free up to 50 users)
```

- SSL: Cloudflare universal flexible→**Full (strict)** with origin cert from Hostinger.
- Turnstile keys registered per subdomain ([../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md)).
- All four app subdomains stay on shared hosting initially — none requires a VPS (constraint honored).

## 7. Domain decisions we are explicitly NOT making now

| Deferred decision | Trigger to revisit |
|---|---|
| `.in` / `.co.in` secondary domains | Only if India-geo keyword strategy demands; otherwise one domain forever |
| Separate Japan/Korea market sites | Never preferred; locale paths + hreflang instead; revisit only if local search engines (Naver etc.) demand it — tracked in [../06-content-seo/04-multilingual-content.md](../06-content-seo/04-multilingual-content.md) |
| Multi-tenant SaaS mode (Sewa powering other companies' portals) | Only post-Phase-2 VPS; noted in [../09-delivery/02-future-scaling.md](../09-delivery/02-future-scaling.md) |

---

Related: [01-executive-summary.md](01-executive-summary.md) · [02-brand-sewa-hospitality.md](02-brand-sewa-hospitality.md) · [03-service-catalog.md](03-service-catalog.md) · [../03-technical-specs/06-hosting-deployment.md](../03-technical-specs/06-hosting-deployment.md)
