{{-- C8 · Before/After Slider (§4): drag handle + keyboard (range input a11y core). --}}
@props(['data' => [], 'isLead' => false])
@php
    $data = is_array($data) ? $data : [];
@endphp
<section {{ $attributes }} data-theme="light"><div class="px-4 py-12 md:px-6 md:py-16"><div class="container mx-auto max-w-3xl">
<div class="rounded-2xl border border-line bg-paper-2 p-4" x-data="{ pos: 50 }">
    <div class="relative aspect-[16/9] overflow-hidden rounded-xl bg-paper-3">
        <div class="absolute inset-0 flex items-center justify-center bg-paper-3 text-xs text-ink-muted">Before image slot</div>
        <div class="absolute inset-y-0 end-0 flex items-center justify-center bg-paper-3/90 text-xs text-ink-muted" :style="`left:${pos}%`" style="left:50%">After image slot</div>
        <input type="range" min="0" max="100" x-model.number="pos" class="absolute inset-0 h-full w-full cursor-ew-resize opacity-0" aria-label="Reveal after image">
        <div class="pointer-events-none absolute inset-y-0 w-0.5 bg-brand" :style="`left:${pos}%`" style="left:50%"></div>
    </div>
    <div class="mt-2 flex justify-between text-xs text-ink-muted"><span>{{ $data['label_before'] ?? 'Before' }}</span><span>{{ $data['label_after'] ?? 'After' }}</span></div>
    @if (($data['caption'] ?? '') !== '') <p class="mt-1 text-center text-xs text-ink-muted">{{ $data['caption'] }}</p> @endif
</div>
</div></div></section>
