{{-- Shared guard fields: honeypot + Turnstile + idempotency anchor.
     Include inside every public form island <form>. Static DOM ids:
     one form island renders per page by contract; Livewire scopes
     wire:model by component instance, not DOM id. --}}
<div aria-hidden="true" class="hidden" tabindex="-1">
    <label for="hp-website-url">Leave this field empty</label>
    <input id="hp-website-url"
           type="text"
           name="website_url"
           value=""
           autocomplete="off"
           tabindex="-1"
           wire:model="websiteUrl">
</div>

<input type="hidden" wire:model="idempotencyKey">

<input type="hidden" wire:model="utmJson"
       x-init="$nextTick(() => {
           const params = new URLSearchParams(window.location.search);
           const utm = {};
           for (const [k, v] of params.entries()) { if (k.startsWith('utm_')) utm[k] = v; }
           if (Object.keys(utm).length) { $wire.utmJson = JSON.stringify(utm); }
       })">

<x-leads.turnstile />
