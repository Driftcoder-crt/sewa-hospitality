{{-- A7 · Marquee Strip (§2): CSS-only ribbon, pause-on-hover, reduced-motion static. --}}
@props(['data' => [], 'isLead' => false])
@php
    $data = is_array($data) ? $data : [];
    $items = array_values(is_array($data['items'] ?? null) ? $data['items'] : []);
    $messages = array_merge($items, $items, $items, $items); // seamless loop
@endphp
<section {{ $attributes }}>
<div data-theme="brand" class="overflow-hidden py-3" aria-hidden="false">
    <div class="flex w-max animate-[sewa-marquee_30s_linear_infinite] items-center gap-10 motion-reduce:animate-none hover:[animation-play-state:paused] motion-reduce:flex-wrap motion-reduce:w-full motion-reduce:justify-center">
        @foreach ($messages as $item)
            <span class="whitespace-nowrap font-display text-paper">{{ $item['text'] ?? '' }}</span>
            <span class="text-paper/50" aria-hidden="true">✦</span>
        @endforeach
    </div>
</div>
</section>
