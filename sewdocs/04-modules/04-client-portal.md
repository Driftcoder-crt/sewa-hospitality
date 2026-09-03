# 04 — Client Portal Module

**app.sewahospitality.com — the real product the reference only pitches: login, move timeline, documents, chat, checklists, invoices — with realtime, role-scoped views for corporate managers and relocating employees.**

---

## 1. Purpose
Replace the reference's CodeIgniter login wall (login + forgot password + a dashboard nobody can see) with a genuinely useful client-facing product: transparency of the move, safe document exchange, two-way chat, task tracking, and billing visibility. This module is also the "MobiRelo pitch" made real — the technology differentiators the reference advertises (dashboard, document management, tracking, instant consultant access) ship as actual features.

## 2. Data model
`organizations`, `organization_users`, `portal_move_records`, `portal_documents`, `portal_threads/messages`, `portal_checklist_items`, `portal_notifications` ([../03-technical-specs/03-database-schema.md](../03-technical-specs/03-database-schema.md) §8). Tenant scoping on every query: manager sees org-wide; employee sees own move.

## 3. Public surface (app.sewa…)

| Route | Who | Contents |
|---|---|---|
| /login · /forgot · /reset | — | auth pages (rate-limited, enumeration-safe) |
| / | all | dashboard: my move summary card(s), next 3 checklist items, unread messages, latest documents, notifications |
| /moves/{id} | employee+manager | timeline (stage progress), services included, checklist (due dates, done-by), assigned consultant card |
| /moves/{id}/documents | role-scoped | document list by category, filter, download (signed URL, audit-logged) |
| /messages | all | threads with consultant team; attachments via media library |
| /notifications | all | notification center (mark-read; realtime badge) |
| /invoices | manager+billing | org invoices (list + detail + PDF) |
| /profile | all | details, password, locale, notification prefs |

Realtime: chat + notifications + timeline updates (Ably push with wire:poll fallback per [../03-technical-specs/11-realtime.md](../03-technical-specs/11-realtime.md) §3 — every feature works on polling alone).

## 4. Admin surface (ops side of the portal)
1. **Moves** — list/filter (org, stage, city, consultant); move editor: services, assignee + manager emails, timeline milestones, stage machine (intake → planning → in-progress → settling → complete → closed), summary notes.
2. **Checklists** — per-move task builder (templates per service type), due dates, done-by tracking (admin or portal side marks done).
3. **Documents** — upload per move (category: visa/lease/inventory/invoice/other), visibility (employee/manager/both), expiry dates (visa/lease reminders → notification events), publish action → `document.published` event + employee notified.
4. **Threads** — consultant inbox: all threads assigned to me; reply (plain text + attachments); internal notes (not client-visible) — chat with context, unlike email.
5. **Invitations** — invite org users (manager/employee/billing roles), magic-link first-login, welcome email ([../03-technical-specs/10-email.md](../03-technical-specs/10-email.md) catalog: portal.invite).
6. **Move templates** — service-combo presets (e.g. "Standard expat relocation Pune": home search + school search + FRRO + fleet) → new move prefilled.

Permissions: consultant (assigned moves), admin+ (all), hr (read for employee coordination), finance (billing view).

## 5. Behavior & rules
- **Stage machine** publishes events: `MoveStageChanged` → email + notification + review-request on completion.
- **Document security:** category-scoped visibility, signed 15-min download URLs, uploads scan-hooked, originals never public URLs.
- **Chat SLA:** consultant reply windows (business hours, published); thread idle > SLA → ops digest flag.
- **Employee onboarding:** invite → set password → guided first-login tour (dashboard, docs, chat).
- **Manager views:** org-wide moves board (their relocating population, stage distribution, upcoming arrival dates) — the corporate mobility-team view the reference sells but doesn't show.
- **Data residency:** all portal content stays in India-region hosting; document retention policy per legal (retention:anonymize does NOT touch active moves).

## 6. Error handling
- Chat send failure → inline retry with the message preserved (never lost typing).
- Upload interruption → resumable chunk upload for >5 MB docs; partial files never published.
- Realtime outage → polling fallback (transparent to users).
- Download link expired → regenerate flow with audit continuity.

## 7. Events & integrations
Emits: `MoveStageChanged`, `ChecklistItemDone`, `MessageSent`, `DocumentPublished`, `NotificationCreated` (+ realtime broadcast per [../03-technical-specs/11-realtime.md](../03-technical-specs/11-realtime.md)). Listens: `InvoiceIssued` (Billing) → notify + portal list; `LeadStatusChanged(won)` → move creation flow; Testimonials listens for `MoveStageChanged(complete)`.

## 8. Tests
Tenant isolation matrix (employee A cannot read org B's anything — exhaustive per-endpoint suite); signed URL expiry + audit rows; stage machine transitions + event emission; chat send/persist/retry; visibility rules on documents (employee vs manager vs both); checklist due notifications; invite flow token expiry.

---

Related: [00-module-system.md](00-module-system.md) · [03-leads-crm.md](03-leads-crm.md) · [12-billing-finance.md](12-billing-finance.md) · [../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) · [../03-technical-specs/11-realtime.md](../03-technical-specs/11-realtime.md) · [../04-modules/13-mobile-readiness.md](13-mobile-readiness.md) · Reference: [../02-formula-reference/01-site-map-and-pages.md](../02-formula-reference/01-site-map-and-pages.md) §3.10
