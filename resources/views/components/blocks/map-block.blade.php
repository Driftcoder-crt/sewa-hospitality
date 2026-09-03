{{-- C7 · Map Block (§4): click-to-load facade (no third-party JS pre-consent),
     address card + directions. --}}
@props(['data' => [], 'isLead' => false])
@php
    $data = is_array($data) ? $data : [];
    $lat = (string) ($data['pin_lat'] ?? '');
    $lng = (string) ($data['pin_lng'] ?? '');
    $hasPin = $lat !== '' && $lng !== '';
@endphp
<section {{ $attributes }} data-theme="light"><div class="px-4 py-12 md:px-6 md:py-16"><div class="container mx-auto max-w-4xl">
@if (($data['heading'] ?? '') !== '') <h2 class="font-display text-2xl md:text-3xl">{{ $data['heading'] }}</h2> @endif
<div class="mt-6 grid gap-4 rounded-2xl border border-line bg-paper-2 p-5 sm:grid-cols-2" x-data="{ loaded: false }">
    <div class="flex flex-col justify-between gap-4">
        <address class="text-sm not-italic leading-relaxed text-ink-soft">{{ $data['address'] ?? '' }}</address>
        @if ($hasPin)
            <a href="https://www.google.com/maps/dir/?api=1&destination={{ $lat }},{{ $lng }}" target="_blank" rel="noopener"
               class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink">Directions ↗</a>
        @endif
    </div>
    <div class="min-h-48 rounded-xl bg-paper-3">
        @if ($hasPin)
            <button type="button" x-show="! loaded" @click="loaded = true" class="flex h-48 w-full items-center justify-center text-sm text-ink-muted hover:bg-paper-3/70">
                Load map (click — no third-party JS until you do)
            </button>
            <iframe x-show="loaded" x-cloak class="h-48 w-full rounded-xl border-0" loading="lazy" title="Office location map"
                    src="https://www.openstreetmap.org/export/embed.html?bbox={{ $lng - 0.01 }}%2C{{ $lat - 0.006 }}%2C{{ $lng + 0.01 }}%2C{{ $lat + 0.006 }}&marker={{ $lat }}%2C{{ $lng }}"></iframe>
        @else
            <div class="flex h-48 items-center justify-center text-xs text-ink-muted">Map pin pending — add lat/lng in the editor</div>
        @endif
    </div>
</div>
</div></div></section>
