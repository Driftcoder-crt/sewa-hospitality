# 11 — Realtime Architecture

**Layered realtime on shared hosting: native-first (always ours), Ably for true push, automatic fallbacks so nothing can ever block a feature, and a protocol-compatible Phase-2 path to self-hosted Laravel Reverb on a VPS.**

---

## 1. The constraint, honestly

Hostinger shared hosting cannot run a long-lived process — no websockets server, no daemons. So realtime is designed as **transports behind one interface**, with the native transport always ours and the push transport swappable:

```
Realtime\Transport (interface)
  ├── NativeTransport     — Livewire 4 islands + wire:poll (always available, zero infra, ours)
  ├── AblyTransport       — true push via Ably (free: 6M msgs/mo, no daily cap) through Laravel Echo
  └── (Phase 2) ReverbTransport — self-hosted websockets on a small VPS, drop-in
```

**Rule: every realtime feature is designed against NativeTransport first.** Push only makes an already-working thing faster. This is the "always keep free native our own option so if a thing blocks" directive, implemented as architecture.

## 2. Why Ably (decision record)

| | Ably free | Pusher free (Sandbox) |
|---|---|---|
| Volume | 6M messages/month cap | 200K messages/day hard cap |
| Cap shape | monthly, roomy | daily wall — a burst 403s you mid-day |
| Delivery | at-least-once + connection recovery (resumable) | standard |
| Laravel Echo | native protocol support | native |
| Swap-out later | Reverb is protocol-compatible | same |

Pusher's hard daily wall is the exact "thing that blocks" the platform must never depend on — so Ably it is. (Full decision record in [01-stack-and-dependencies.md](01-stack-and-dependencies.md) §6.)

## 3. What is actually realtime (feature → transport)

| Feature | Surface | Native (always works) | Push upgrade (Ably) |
|---|---|---|---|
| Admin dashboard live tiles | admin | islands + wire:poll 30s | 2–5s refresh on lead/application events |
| Lead inbox "new lead" toast | admin | poll island | instant toast + sound |
| Portal chat | app. | poll thread every 10s (island) | instant message delivery + typing indicator |
| Move timeline/checklist | app. | poll 60s | instant on `move.*` events |
| Portal notifications | app. | poll 30s | instant badge + toast |
| New review alert | admin | daily sync + poll | instant on sync (daily — syncs are cron anyway) |
| Queue/Scheduler health | admin/status | poll 30s | n/a (poll only) |
| Public site | www | **none needed** — cached static pages (no live features on marketing pages) | — |

Marketing pages never need websockets — anonymous full-page caching (see [05-security-reliability.md](05-security-reliability.md) §2.5) beats any push for that surface.

## 4. Native transport (the always-ours layer)

**Livewire 4 islands** are the core: an island re-renders an isolated region without touching the rest of the page — dashboards, inboxes, timelines update without full-page loads and without any external service. `wire:poll` drives them at the intervals above; intervals stagger by island importance (admin inbox 10–30s, health 30s, public n/a).

Additional native rules:
- Poll requests are cheap: islands return rendered fragments only; DB queries per poll are bounded (cached counts, eager loads, LIMIT 1 windows).
- Poll pauses when tab hidden (`document.visibilityState`) and when user idle — CPU/DB friendly on shared hosting.
- Everything readable over polling is also rendered server-side initially — **no feature is JS-dependent to function.**

## 5. Ably layer (push acceleration)

### Server side
- Laravel broadcasting with the Ably broadcaster; events named per the platform event catalog ([../04-modules/13-mobile-readiness.md](../04-modules/13-mobile-readiness.md) §4): `move.stage_changed`, `message.created`, `notification.created`, `invoice.issued`, `document.published`, plus admin channel `admin.leads.new`.
- Auth: private channels via authenticated Echo endpoints; capability tokens minted server-side, scoped per user/tenant (a client user can never subscribe to another org's channel).
- Publishing happens from queued jobs/services (post-commit) — never inside a DB transaction.

### Client side
- Echo connects only on `app.` and `admin.` surfaces (no third-party JS on public pages).
- Channel subscription map: `portal.user.{id}` (notifications, threads), `portal.org.{id}` (manager views), `admin.dashboard` (role-gated).

### The automatic fallback
```
RealtimeManager::boot():
  if (config realtime.transport == 'ably')
      attempt connect … on failure ×3 → flip client flag → islands continue on wire:poll only
  breaker opens server-side (publish failures) → events silently skip Ably
      (clients on poll transport are unaffected; push clients reconnect to poll on next poll tick)
```
- Feature flag `REALTIME_TRANSPORT=native|ably` (env + settings toggle in admin Ops page).
- If Ably free tier is exhausted (budget guard alerts at 80%), ops flip to native — the platform keeps working; users see slightly slower updates. **Nothing blocks.**

## 6. Bandwidth/usage budget (free tier arithmetic)

- Portal clients active-day pattern: ~200 users × ~1 msg/min × 8h ≈ 96K messages/day worst case → ~2.9M/month — inside 6M with headroom; alerts at 4.8M.
- Message payloads are tiny (ids + minimal state; islands fetch detail by poll/HTTP).
- Peak-day caps enforced by design (throttled chat), not by outage.

## 7. Phase 2 — Reverb on a VPS (the exit path, not the plan)

Trigger: Ably usage sustained near cap, or VPS migration happens for other reasons ([../09-delivery/02-future-scaling.md](../09-delivery/02-future-scaling.md)). Because Reverb implements the same protocol:
1. Add `reverb` broadcaster config (app id/key/secret on the VPS).
2. Flip `REALTIME_TRANSPORT=reverb` (or auto via ably-primary/reverb-failover).
3. Zero client-code changes (Echo is protocol-driven).
Native/poll fallback remains in place beneath both push providers — the layered doctrine survives every migration.

## 8. Testing realtime
- Pest: event → channel payload contract tests for the whole event catalog; transport interface tests (Native always returns rendered islands; Ably publisher failure → island still updates via poll assertion).
- Staging: forced-failover drills (disable Ably token → verify polling seamlessly takes over in under 30s, no user-visible error) — part of the quarterly resilience drill ([13-testing-qa.md](13-testing-qa.md)).

---

Related: [01-stack-and-dependencies.md](01-stack-and-dependencies.md) · [05-security-reliability.md](05-security-reliability.md) · [07-queues-scheduling.md](07-queues-scheduling.md) · [../04-modules/04-client-portal.md](../04-modules/04-client-portal.md) · [../04-modules/05-admin-panel.md](../04-modules/05-admin-panel.md) · [../04-modules/13-mobile-readiness.md](../04-modules/13-mobile-readiness.md)
