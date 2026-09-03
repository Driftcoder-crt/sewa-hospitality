# 10 — Email System

**Transactional email via Resend or Brevo free tier (primary) with Hostinger SMTP fallback (native escape hatch), SPF/DKIM/DMARC-authenticated, queue-driven, template-based, and fully fallback-safe.**

---

## 1. Principles
1. **Never send from the web server's raw SMTP as the primary path** — deliverability on shared hosting IPs is poor; a provider (Resend/Brevo) exists for this.
2. **Everything goes through queues** — a form success never waits on SMTP, and a provider outage never blocks a lead ([07-queues-scheduling.md](07-queues-scheduling.md)).
3. **One template system** (Blade components + Tailwind-styled, inlined) shared by all modules — brand-consistent mail like the brand guidelines.
4. **Transactional + marketing separated** — transactional from the provider domain; marketing (newsletter) only to double-opt-in subscribers with one-click unsubscribe (CAN-SPAM/DPDP hygiene).

## 2. Provider setup (primary)

**Choice: Resend or Brevo free tier (decided at setup; both specced):**

| | Resend free | Brevo (Sendinblue) free |
|---|---|---|
| Volume | 100 emails/day (3k/mo) | 300 emails/day |
| SMTP + API | both | both |
| Verdict | cleanest API; fine at launch volume | higher daily cap; fine at launch volume |

- Domain verified in provider (DNS records): SPF, DKIM, (DMARC below).
- Laravel transport: SMTP (zero SDK dependency — keeps [01-stack-and-dependencies.md](01-stack-and-dependencies.md) allowlist clean) with API fallback documented.
- **Escalation path:** if daily volumes exceed free tier (marketing growth), upgrade one provider plan — costs documented in [../04-modules/12-billing-finance.md](../04-modules/12-billing-finance.md) ops notes.

**From-addresses** ([../01-platform-vision/04-subdomains-ventures.md](../01-platform-vision/04-subdomains-ventures.md) §5): hello@ (public replies), support@ (portal), careers@ (applications), no-reply@ (system), billing@ (finance). All identities live in one provider domain config.

## 3. DNS records (deliverability lock)

```
TXT  @           "v=spf1 include:spf.<provider>.com include:_spf.hostingermail.com ~all"
TXT  selector._domainkey  (DKIM from provider)
TXT  _dmarc      "v=DMARC1; p=quarantine; rua=mailto:dmarc@sewahospitality.com; pct=100; adkim=s; aspf=s"
MX                (mail routing — no-reply needs no inbound; hello/support/careers need inboxes via Hostinger mail or provider inbox)
```
- DMARC enforced `quarantine` from day 30 (after alignment reports confirm), target `p=reject` by day 90.
- Weekly DMARC digest reviewed by ops (visibility into spoofing attempts).

## 4. The email catalog (every email the platform sends)

| Key | Trigger | Recipient | Contents |
|---|---|---|---|
| `lead.received` | Lead created (contact/quote/callback) | ops + assigned consultant | lead fields, source page, locale, SLA countdown, admin deep-link |
| `lead.ack` | Lead created | lead's email | warm acknowledgment (their language, if translated) + what happens next + reply-to |
| `application.received` | Job application submitted | careers@ + recruiter | application summary + resume link (signed) |
| `application.ack` | Application submitted | applicant | confirmation + what's next + data-retention note |
| `newsletter.confirm` | Subscribe request | subscriber | double-opt-in token link |
| `newsletter.issue` | Campaign send | confirmed subscribers | the issue (markdown-sourced) |
| `password.reset` | Forgot password | user | signed token link (60-min expiry) |
| `portal.invite` | Client invited to portal | client email | magic onboarding link + credentials flow |
| `move.stage_changed` | Portal move stage change | employee + manager | stage, what's next, link to portal |
| `document.published` | New doc in portal | employee | category + link (never attachment) |
| `invoice.issued` | Invoice sent | org billing contact | invoice PDF attached + portal link |
| `invoice.reminder` | Due/overdue schedule | org billing | polite + link |
| `review.request` | Move marked complete | client employee + HR | review CTA (Google link) + feedback fallback |
| `ai.translation_ready` | Human review needed | editors | items awaiting review |
| `ops.digest` | Daily 09:00 | ops list | leads, SLA breaches, failed jobs, queue depth, reviews, zero-result searches |
| `backups.alert` / `monitoring.alert` | Any monitor | ops | alert payloads |

Every template: brand header/footer, one primary CTA button, locale-aware (subject + body from the I18n module), plain-text alternative generated, unsubscribe link on marketing only.

## 5. Fallback chain (native escape hatch — the "never blocked" rule)

```
send() → provider SMTP  ──ok──> done
   │ breaker opens (5 fails/60s)
   ▼
Hostinger SMTP (no-reply@ fallback identity)  ──ok──> done (+ ops alert)
   │ fails
   ▼
job retries (queue backoff) + Sentry SEV-2 + ops alert
   │
   ▼
"pending emails" admin view — one-click resend after fix
```
- The fallback identity is verified too (included in SPF via hostingermail).
- Marketing sends pause automatically when the breaker is open (transactional takes priority) — enforced in the SendNewsletter job.

## 6. Queue & retry mapping
All sends on `emails` queue: tries=5, backoff 60/300/900/1800/3600, retry_until 24h ([07-queues-scheduling.md](07-queues-scheduling.md)). Idempotency: each template send carries a deterministic key (e.g. `application.ack:{id}`) checked against `mail_log` — retries never double-send.

## 7. Mail log & compliance
- `mail_log` table: key, recipient (hashed), template, status, sent_at, provider_message_id (bounces/complaints webhook where provider supports).
- Bounce/complaint handling: 1 bounce → flag; 3 bounces → suppress; complaints → immediate suppress (provider webhooks queued).
- Consent: marketing emails only to `newsletter_subscribers.status=confirmed` ([03-database-schema.md](03-database-schema.md) §5); transactional justified by relationship, logged.

## 8. Testing
- Mail trap in local/staging (Hog/Mailpit) — visual QA of every template in both RTL and LTR.
- Pest: template renders for all catalog keys × {en, ar} (RTL correctness), idempotency test (double-dispatch = one send), fallback-path test (breaker forced open → Hostinger path used).

---

Related: [05-security-reliability.md](05-security-reliability.md) · [07-queues-scheduling.md](07-queues-scheduling.md) · [../04-modules/03-leads-crm.md](../04-modules/03-leads-crm.md) · [../04-modules/06-hr-employee-module.md](../04-modules/06-hr-employee-module.md) · [../04-modules/08-testimonials-reviews.md](../04-modules/08-testimonials-reviews.md)
