# 12 — Billing & Finance Module

**Quotes → invoices → payments with concurrency-safe sequential numbering, portal visibility, and GST-correct documents — a commercial surface the reference platform simply doesn't have.**

---

## 1. Purpose
Close the commercial loop: quote requests (Leads) → formal quotes → invoices → recorded payments, visible to clients in the portal and managed by finance in admin — with the same error-lock discipline as the money paths ([../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) §2.1).

## 2. Data model
`quotes`, `invoices`, `invoice_payments` ([../03-technical-specs/03-database-schema.md](../03-technical-specs/03-database-schema.md) §9). Numbering: `SEWA-Q-YYYY-####` / `SEWA-I-YYYY-####` allocated inside a locked transaction (`SELECT … FOR UPDATE`) — no duplicates under concurrency, ever.

## 3. Public surface (portal + email)
| Surface | Spec |
|---|---|
| Portal /invoices (manager+billing roles) | org invoices list (status chips: draft/sent/partial/paid/overdue/void), detail (lines, tax breakdown, payments), PDF download (signed, audit-logged) |
| Quote acceptance link | quote sent as email with secure accept/reject token link (logged-in or tokenized); accepted → `QuoteAccepted` → invoice draft |
| Invoice email | `invoice.issued` email with PDF attached + portal link; reminders on schedule |

## 4. Admin surface
1. **Quotes** — builder: org, move record link, lead link, line items (description/qty/rate/tax class), totals auto-computed, validity window, notes; status machine (draft → sent → accepted/expired/rejected); duplicate-to-invoice action; version history on edits after send.
2. **Invoices** — issue from quote or standalone; PDF preview (branded template, GST fields); send action (queues email); mark partial/paid with payment records (method: bank/UPI/cheque/gateway, reference, amount); void with reason (audit); overdue automation (reminders day +3/+10/+20 — queued, polite tone).
3. **Payments** — record/review; reconciliation view (unmatched references); export CSV (finance role, audited).
4. **Organizations** — billing profile: GSTIN, PAN, address, billing contacts, payment terms.
5. **Reports** — monthly revenue by service/city (dashboard charts — Livewire island), outstanding aging, quote win rate (ties to CRM statuses).

Permissions: `finance` (full), `admin` (full), others: none. Portal managers see own-org invoices only.

## 5. Behavior & rules
- **Sequential numbering integrity:** allocation under lock; void keeps number (never reused) — statutory hygiene.
- **GST correctness:** line-level tax classes (5/18/28 as applicable to service type — configurable settings per service), reverse-charge handling where relevant, place-of-supply logic documented with the accountant-of-record. (The platform computes; the accountant certifies — roles documented.)
- **Quote integrity:** edits after sending create a new version row (audit trail); the sent PDF is immutable in media storage.
- **Amount handling:** INR integers (paise-safe), no floats anywhere; format per locale in display only.
- **Rounding:** line-level rounding rules per GST convention; totals recomputed on any line change.
- **Reminder etiquette:** max 3 reminders, then human outreach task — never automated spam.

## 6. Error handling
- PDF generation failure → queue retry; invoice state never "sent" unless email actually queued ([../03-technical-specs/10-email.md](../03-technical-specs/10-email.md) catalog).
- Payment record with unknown reference → reconciliation queue (never auto-matched wrongly).
- Gateway/manual mismatch (amount ≠ invoice) → flagged, not silently accepted.

## 7. Events & integrations
`QuoteAccepted` → Leads status auto-update + move link; `InvoiceIssued` → portal notification + email; `PaymentRecorded` → status transitions + thank-you note. Analytics: revenue events (server-side, no client PII).

## 8. Tests
Numbering concurrency (parallel issue × N = N unique); float-free arithmetic (paise edge cases); GST class computation incl. mixed-class quotes; quote versioning after send; token acceptance single-use + expiry; reminder schedule (travel()); void preserves number; portal tenant isolation on invoices; export permissions + audit rows.

---

Related: [00-module-system.md](00-module-system.md) · [03-leads-crm.md](03-leads-crm.md) · [04-client-portal.md](04-client-portal.md) · [../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) · [../03-technical-specs/10-email.md](../03-technical-specs/10-email.md)
