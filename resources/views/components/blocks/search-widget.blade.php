{{-- F10 · Search Widget (§7): inline box → /search grouped results. --}}
@props(['data' => [], 'isLead' => false])
@php
    $data = is_array($data) ? $data : [];
@endphp
<section {{ $attributes }} data-theme="light"><div class="px-4 py-10 md:px-6"><div class="container mx-auto max-w-xl">
@if (($data['heading'] ?? '') !== '') <h2 class="font-display text-xl">{{ $data['heading'] }}</h2> @endif
<form action="/search" method="GET" role="search" class="mt-3 flex gap-2">
    <label class="sr-only" for="sw-q">Search the site</label>
    <input id="sw-q" type="search" name="q" required minlength="2" placeholder="Housing in Pune, FRRO, fleet…"
           class="min-h-[44px] w-full rounded-full border border-line bg-paper px-4 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30">
    <button type="submit" class="inline-flex min-h-[44px] shrink-0 items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink">Search</button>
</form>
</div></div></section>
