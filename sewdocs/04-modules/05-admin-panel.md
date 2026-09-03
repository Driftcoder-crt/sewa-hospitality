# 05 — Admin Panel

**admin.sewahospitality.com — a custom-built panel on Livewire 4 + Alpine 3 + Tailwind 4.3. No Filament, no admin packages: full design control, zero upgrade-lock risk, and a security surface we own end-to-end.**

---

## 1. Why custom (decision record)
Filament is excluded by directive; the same reasoning applies to any admin framework: a heavy dependency in the most security-sensitive surface, opinionated UX the brand can't shape, and upgrade cycles we don't control. Livewire 4 gives us components, islands (live dashboards without re-render storms), and view-based single-file components — everything an admin needs. Cost: we build ~30 screens (all specced across module docs). Benefit: total control, commercial-grade UX, one design system end-to-end.

## 2. Global admin UX

- **Shell:** fixed sidebar (collapsible, module sections), topbar with global search (⌘K — role-scoped), environment badge, user menu. Livewire islands update lists/toasts without page reloads.
- **Design:** same Tailwind token set as the public site ([../05-design-system/02-ui-components.md](../05-design-system/02-ui-components.md)) with admin-density variants — one brand, two surfaces.
- **Accessibility & speed:** keyboard-first (⌘K palette, `g+key` module jumps), focus-visible everywhere, tables virtualized past 100 rows, all lists server-paginated with saved filters.
- **Mobile:** responsive to tablet; destructive actions require confirm-typing.

## 3. Navigation map (modules → screens)

| Section | Screens (module docs for detail) |
|---|---|
| Dashboard | KPI tiles (leads today, SLA breaches, pipeline value, open moves, applications, reviews), queue/health widget ([../03-technical-specs/12-monitoring.md](../03-technical-specs/12-monitoring.md)), activity feed |
| Content (CMS) | Pages · Menus · Blocks preview · Media · Redirects · Settings ([01-cms.md](01-cms.md)) |
| Services | Tree · Service editor · Coverage ([02-services-module.md](02-services-module.md)) |
| Cities & Housing | Cities · Housing units · Verification queue ([10-cities-content.md](10-cities-content.md)) |
| Blog & News | Posts (editor+calendar) · Categories · Tags · Authors · Translations review ([07-blog-news.md](07-blog-news.md), [11-multilingual.md](11-multilingual.md)) |
| Leads (CRM) | Inbox · Pipeline · Lead detail · SLA monitor · Newsletter ([03-leads-crm.md](03-leads-crm.md)) |
| Careers & HR | Job postings · Applications (ATS pipeline) · Employees · Team visibility ([06-hr-employee-module.md](06-hr-employee-module.md)) |
| Portal ops | Moves · Checklists · Documents · Threads · Invitations ([04-client-portal.md](04-client-portal.md)) |
| Billing | Quotes · Invoices · Payments · Organizations ([12-billing-finance.md](12-billing-finance.md)) |
| Testimonials | Testimonials · Google review sync · Review requests ([08-testimonials-reviews.md](08-testimonials-reviews.md)) |
| CSR | NGO partners · Stories ([09-csr-module.md](09-csr-module.md)) |
| AI | Providers · Budget gauges · Usage log · Translation queue ([../08-ai-system/01-ai-architecture.md](../08-ai-system/01-ai-architecture.md)) |
| Ops | Schedule status · Queue health · Failed jobs (+retry) · Backups · Status page editor · Audit log ([../03-technical-specs/07-queues-scheduling.md](../03-technical-specs/07-queues-scheduling.md), [../03-technical-specs/12-monitoring.md](../03-technical-specs/12-monitoring.md)) |
| System | Users · Roles & permissions · API tokens · Settings-integrations · Redirects |

## 4. Admin conventions (every module screen follows)

1. **Index pattern:** filter bar (saved per user), sortable columns, status chips, row actions, bulk actions with confirm, empty-states with a create CTA.
2. **Editor pattern:** two-pane (form + live preview where applicable), autosave with revision history, publish/save-draft separation, unsaved-changes guard.
3. **Destructive pattern:** confirm dialog naming the object + typed confirmation for irreversible (delete published content, purge lists).
4. **Audit pattern:** sidebar shows created/updated by/at + link to filtered activity log; all exports one-click with audit row.
5. **Permission pattern:** UI hides disallowed actions AND the server re-checks (never UI-only).
6. **Feedback pattern:** toasts for actions, inline validation, optimistic counts on islands, error banners with retry (never self-destructing messages — reference defect).
7. **List safety:** PII masking for non-privileged roles (editors see counts, not names).

## 5. Roles & permission matrix (seeded)

| Role | Scope summary |
|---|---|
| super-admin | everything incl. system settings, users, integrations, audit purge policies |
| admin | everything except destructive system settings |
| editor | CMS content, services, cities, blog, testimonials, CSR (no leads PII, no system) |
| author | own posts draft→review; profile |
| hr-manager / recruiter | jobs, applications, employees (own dept), team page content |
| finance | billing + organizations + won-value reports (no content editing) |
| consultant | assigned leads, assigned moves/threads/checklists; no content |
| ops (optional) | Ops + monitoring views |

2FA mandatory for super-admin/admin ([../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) §1.1). Session 2h idle. All admin logins audit-logged with IP.

## 6. Error handling in the panel
- Any queue/worker issue visible in Ops widget with one-click retry (admins aren't blind — reference admins literally had no panel).
- Feature-flag toggles (realtime transport, AI enable) live in Ops with audit trail.
- If a module is degraded (breaker open), its screens show a capability banner, not a broken page.

## 7. Build estimate & quality gates
- ~30 unique screens + shared index/editor components (block canvas, kanban, table, drawer) — the heavy reusable components are built once.
- Every screen ships with its Pest feature tests ([../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md)) and a UAT checklist in the module doc.
- Design review against the admin design tokens happens per module milestone ([../09-delivery/01-build-roadmap.md](../09-delivery/01-build-roadmap.md)).

---

Related: [00-module-system.md](00-module-system.md) (permission matrix) · [../03-technical-specs/01-stack-and-dependencies.md](../03-technical-specs/01-stack-and-dependencies.md) (exclusion rationale) · [../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) · [../05-design-system/02-ui-components.md](../05-design-system/02-ui-components.md)
