# 02 — Analytics Plan

**One measurement model for the platform: what we track, how events are named, how funnels connect to revenue, and how consent stays intact — via GTM + GA4 with server-side lead events from Laravel.**

---

## 1. Measurement principles
1. **Consent-first:** no tag fires before consent (Consent Mode v2, [01-google-ecosystem.md](01-google-ecosystem.md) §2); PII never sent to analytics (no emails/names in events — IDs and context only).
2. **Conversions are server-confirmed:** a lead counts when it's in the DB (server event), not when a form button is clicked — kills the inflated-conversion problem.
3. **One taxonomy:** events snake_case, params lowercase, documented here; admin edits add events through this doc, not ad-hoc.
4. **Dashboards over raw reports:** 3 standing views (funnel, content, ops) defined below.

## 2. Event map (GA4)

### Core engagement
| Event | Params | Fires |
|---|---|---|
| `page_view` (auto) | + `content_group` (site area: service/city/housing/blog/portal/admin n/a) | all public pages |
| `view_service` | `service_slug`, `family`, `locale` | service pages |
| `view_city` | `city_slug`, `locale` | city pages |
| `view_housing` | `city`, `tier`, `unit_id` | housing detail |
| `filter_housing` | `city`, `tier`, `bedrooms`, `results_count` | filter application |
| `view_post` | `post_slug`, `type`, `author_id`, `locale`, `category` | posts |
| `search` | `term_grouped`, `tab`, `results_count` (no raw terms for long-tail privacy → hash term, log full in internal zero-result store) | site search |
| `click_locale_switch` | `from`, `to` | locale switcher/banner |
| `click_consultant` | `context` | consultant cards |
| `click_review` | `source` | Google review CTAs |
| `video_play` | `video_id`, `surface` | video facade |

### Conversion funnel (the money events — all server-side via the Laravel GA4 Measurement Protocol, consent-checked)
| Event | Params | Source |
|---|---|---|
| `lead_submit` | `service_tag`, `city`, `locale`, `form_type` (contact/quote/callback), `lead_ref` (internal), `utm_*` | Leads module after DB write |
| `application_submit` | `job_dept`, `city`, `source` | Careers module |
| `newsletter_subscribe` | `locale`, `source` (after double opt-in confirm — real intent) |
| `quote_accepted` | `service`, `city` | Billing module |
| `review_request_sent` / `review_received` | `city`, `service` | Testimonials module |
| `portal_invite_sent` / `portal_first_login` | `role` | Portal module |

### Campaign hygiene
- UTM discipline: `utm_source/medium/campaign/content/term` — enforced by a link builder tool in admin (marketing generates tagged links, not hand-typed).
- Auto-tagging for Ads; gclid captured server-side on lead (attribution table in Leads module for "how did you hear" join).
- Referrer groups: AI engines (chat/perplexity/gemini) tracked as a distinct channel ([../06-content-seo/05-aeo-llm-presence.md](../06-content-seo/05-aeo-llm-presence.md) §5).

## 3. Funnels & KPIs

### Demand funnel (public site)
```
visitor → page_view(content_group) → view_service/city → form_start → lead_submit
```
Micro-conversions tracked: form_start (field focus), form_abandon (validation error then exit — UX signal), scroll_75 on money pages.

### Hiring funnel
```
view_careers → view_job → application_submit → (ATS stage events internal only)
```

### Client lifecycle funnel
```
lead_submit → quote_accepted → portal_invite_sent → portal_first_login →
move milestones (internal) → review_received
```
(This end-to-end chain is the platform's unique measurement asset — the reference cannot see any of it.)

### Standing dashboards
| Dashboard | Contents |
|---|---|
| Demand | sessions by channel/locale, funnel drop-offs, CPL by service/city, top zero-result searches |
| Content | page performance, refresh impact (before/after), AEO referral channel, newsletter growth |
| Ops/CS | SLA compliance trend, review rating trend, service-recovery log |

KPI targets live in [01-content-strategy.md](../06-content-seo/01-content-strategy.md) §7 and [04-growth-roadmap.md](04-growth-roadmap.md).

## 4. Privacy & consent implementation
- Cookie banner copies in all launch locales; equal choice (accept/reject one click each); preferences editable in footer.
- GA4 with no-Google-ads signals until consent; IP anonymization on; data retention 14 months.
- Server events respect consent state (no Measurement Protocol calls for rejected users).
- No session recording tooling on portal/admin (client-data surfaces); Clarity-equivalent only on public pages, consent-gated.

## 5. Tooling notes
- GTM container per environment; server containers avoided at launch (shared-hosting simplicity; documented Phase-2 option if tag hygiene degrades).
- Internal analytics (Pulse) covers app-ops ([../03-technical-specs/12-monitoring.md](../03-technical-specs/12-monitoring.md)); GA4 covers demand — the two don't overlap by design.
- Reports scheduled monthly to leadership (PDF export of the 3 dashboards) — measurement is consumed, not archived.

---

Related: [01-google-ecosystem.md](01-google-ecosystem.md) · [03-trust-authority.md](03-trust-authority.md) · [04-growth-roadmap.md](04-growth-roadmap.md) · [../04-modules/03-leads-crm.md](../04-modules/03-leads-crm.md) · [../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) §1.4
