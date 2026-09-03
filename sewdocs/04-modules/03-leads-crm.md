# 03 — Leads & CRM Module

**Every form on the platform feeds one pipeline: inbox → qualify → assign → SLA → convert. The reference fires leads at a bare API endpoint with no CRM, no SLA, no visibility — Sewa turns every submission into a tracked, never-lost opportunity.**

---

## 1. Purpose
Own all inbound demand (contact, quote requests, callback, newsletter, portal demo requests, campaign landings) as a CRM: capture without data loss, respond within published SLAs, assign and track to won/lost, and sync review requests at journey completion.

## 2. Data model
`leads`, `lead_events`, `newsletter_subscribers` ([../03-technical-specs/03-database-schema.md](../03-technical-specs/03-database-schema.md) §5). Money-path rules: `idempotency_key` unique, `sla_due_at` computed at create, all writes transactional, activity fully logged.

## 3. Public surface (forms)

| Form | Location | Fields | Notes |
|---|---|---|---|
| Contact | /contact + embedded blocks | name*, email*, phone*, company?, message*, service (select pre-filtered by context), consent* | locale-aware copy |
| Quote request | service pages, /housing | name*, email*, phone*, company*, service_slug, city_slug?, requirements*, budget_hint? | richest intent — routes to pipeline stage `proposal` fast-lane |
| Callback | sitewide footer link/popup | phone*, name?, preferred window? | 2-hour SLA promise |
| Newsletter | footer + blog sidebar + popups | email*, locale | double opt-in; **actually works** (reference: action="#") |
| Career contact | careers pages | → routes to HR module | not a lead |

All forms: Turnstile + honeypot + rate limit + idempotency + draft persistence + inline validation ([../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) §1.2). Success: instant inline confirmation + `/thank-you` with next steps (SLA promise, what happens next, portal teaser) — never a silent redirect losing typed data.

## 4. Admin surface (the CRM)

1. **Inbox** — realtime island (new-lead toast, wire:poll + optional Ably push): new leads list with source, service tag, city, locale, score, SLA countdown (amber/red as deadline nears). Filters: status/source/service/assigned/locale/date. Bulk: assign, archive.
2. **Lead detail** — full submission + timeline (lead_events: notes, calls, emails sent, status changes), AI enrichment panel (optional, guarded by breaker — company detection, language of message, suggested reply language), assignment, status machine, next-action date, lost-reason codes.
3. **Pipeline** — kanban: new → contacted → qualified → proposal → won/lost/nurture (drag with audit trail). Won → one-click create organization + portal invite (Portal module) or convert to quote (Billing).
4. **SLA monitor** — breaches list + daily digest stats ([../03-technical-specs/07-queues-scheduling.md](../03-technical-specs/07-queues-scheduling.md) `sla:calculate`). Published SLAs: contact 2 business hours, quote 4, callback 2.
5. **Newsletter manager** — subscribers, statuses, bounces, issue composer (markdown → email), confirm-rate stats.
6. **Exports** — role-gated CSV export, audit-logged.

Permissions: `admin`, `consultant` (assigned only), `finance` (won-value view only). Editors see counts, not PII.

## 5. Behavior & rules
- **Status machine:** new → contacted → qualified → proposal → won | lost(reason) | nurture; transitions logged; won requires either organization link or quote reference (data hygiene).
- **Assignment:** round-robin by service/city with consultant availability (from HR module) + manual override; unassigned > 15 min → escalate to admin.
- **Dedupe:** same email + phone within 48h → merge-into-existing flag with one-click review (never silently drop — but never spam duplicates either).
- **Locale-aware responses:** ack email in lead's language when a reviewed translation exists; fallback EN. Response language recorded for consultant briefing.
- **Journey end:** when a linked move is marked complete (Portal event), queue `review.request` email (Testimonials module) + CSAT survey.

## 6. Error handling
- Submission path cannot fail invisibly: DB write is the source of truth; emails queue separately; a mail outage never loses the lead (reference's silent-failure defect eliminated).
- If AI enrichment breaker is open → panel shows "enrichment paused" (no blocking).
- Bounced ack email → lead flagged "unreachable" with retry guidance.

## 7. Events & integrations
Emits: `LeadCreated`, `LeadStatusChanged`, `SlaBreached`, `NewsletterSubscribed`. Listens: `MoveStageChanged(complete)` → review request; `QuoteAccepted` (Billing) → auto-link + status update; campaign UTM capture ([../07-marketing-trust/02-analytics-plan.md](../07-marketing-trust/02-analytics-plan.md)).

## 8. Tests
Idempotent double-submit; SLA computation per source; throttle; Turnstile fail path; merge-dedupe flow; status-machine transitions (invalid ones rejected); conversion to quote/organization; newsletter double-opt-in states; locale ack selection; exports permission-gated + audited.

---

Related: [00-module-system.md](00-module-system.md) · [../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) · [../03-technical-specs/07-queues-scheduling.md](../03-technical-specs/07-queues-scheduling.md) · [../08-ai-system/02-ai-use-cases.md](../08-ai-system/02-ai-use-cases.md) · [../02-formula-reference/03-api-and-data-layer.md](../02-formula-reference/03-api-and-data-layer.md) (what we're replacing)
