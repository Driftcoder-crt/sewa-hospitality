# 13 — Mobile App Readiness

**The platform ships API-first from day one so a future Sewa mobile app (client/employee or consultant) is an integration project, not a rewrite. The reference has a separate legacy app welded to a 2021-era site; Sewa's app path is a first-class contract.**

---

## 1. Purpose
Define the binding contract between the platform and any future mobile client: auth, endpoints, events, media, realtime, and versioning — all specified in this suite so the app team never blocks on the web team.

## 2. The contract (what an app gets for free at launch)

| Capability | Source spec |
|---|---|
| Auth (login, forgot, token lifecycle, scopes) | [../03-technical-specs/04-api-spec.md](../03-technical-specs/04-api-spec.md) §3 (Sanctum, device tokens) |
| Moves, documents, threads, notifications, invoices | [04-client-portal.md](04-client-portal.md) §3 endpoints are app-ready with one JSON contract |
| Realtime events (push) | [../03-technical-specs/11-realtime.md](../03-technical-specs/11-realtime.md) §4 event catalog + Ably protocol (every mobile push library speaks it) |
| Media (documents, galleries, avatars) | [../03-technical-specs/09-media-pipeline.md](../03-technical-specs/09-media-pipeline.md) (signed URLs, WebP/AVIF variants, alt-bearing payloads) |
| Public content (services/cities/housing/posts) | [../03-technical-specs/04-api-spec.md](../03-technical-specs/04-api-spec.md) §2 — an app renders the same catalog data |
| Search | [../03-technical-specs/08-search.md](../03-technical-specs/08-search.md) `/v1/search` |
| Forms (lead/application) | idempotency + Turnstile app-mode (server verifies via app token — documented flow) |

## 3. Event catalog (versioned, reserved now)

```
move.stage_changed      {move_id, stage, at}
move.checklist_done     {move_id, item_id, done_at}
message.created         {thread_id, message_id, sender_role}
document.published      {move_id, document_id, category}
invoice.issued          {invoice_id, organization_id}
notification.created    {user_id, type, title, body, deep_link}
```
Naming, payloads, and semantics are **frozen at v1**; additive changes only within v1 ([../03-technical-specs/04-api-spec.md](../03-technical-specs/04-api-spec.md) §1).

## 4. App scenarios (what Phase-2 apps would be)

| App | Persona | Core journeys (all covered by v1 endpoints + events) |
|---|---|---|
| Client/employee app | relocating family + corporate manager | move timeline, checklist, docs (offline-cached), chat, notifications, invoices, city guide content |
| Consultant/ops app | Sewa consultants | assigned moves, checklist execution, thread replies, document upload, lead follow-ups |

Offline strategy notes (for the app team): document blobs cacheable; checklist mutations queueable with idempotency keys (the API already guarantees retry-safety — this is why idempotency was a v1 requirement).

## 5. Governance

1. **API versioning discipline:** `/v1` frozen at launch; additive-only changes; breaking → `/v2` with 12-month overlap. OpenAPI doc generated and versioned in-repo.
2. **Token governance:** app tokens carry scopes (`app.read`, `app.write`), device-bound (device_name + revocable per device in admin → portal user's security page), 90-day expiry with refresh flow documented.
3. **Rate limits** sized for app burst patterns (240/min/token) — documented so the app backoff logic matches ([../03-technical-specs/04-api-spec.md](../03-technical-specs/04-api-spec.md) §1).
4. **Push readiness:** event → notification rows already exist (portal notifications); app push (FCM/APNs) becomes a new transport on `notification.created` — no platform changes needed, just a Phase-2 listener.
5. **Contract tests:** the v1 endpoint suite runs in CI as the compliance gate — the web app consumes the same contracts, so drift is impossible by construction ([../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md) §3).

## 6. What is deliberately NOT built now
- No React Native/Flutter codebase, no app store presence, no push infra — only the contract above. App development is a Phase-2 decision with its own business case ([../09-delivery/02-future-scaling.md](../09-delivery/02-future-scaling.md)); the platform's job is to make that decision cheap.

---

Related: [04-client-portal.md](04-client-portal.md) · [../03-technical-specs/04-api-spec.md](../03-technical-specs/04-api-spec.md) · [../03-technical-specs/11-realtime.md](../03-technical-specs/11-realtime.md) · [../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md) · [../09-delivery/02-future-scaling.md](../09-delivery/02-future-scaling.md)
