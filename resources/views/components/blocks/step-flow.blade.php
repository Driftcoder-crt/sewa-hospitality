{{-- A6 · Step Flow (section-library §2): numbered serif steps + connector. --}}
@props(['data' => [], 'isLead' => false])
@php
    $data = is_array($data) ? $data : [];
    $items = is_array($data['items'] ?? null) ? array_values($data['items']) : [];
@endphp
<section {{ $attributes }} data-theme="light"><div class="px-4 py-12 md:px-6 md:py-16"><div class="container mx-auto max-w-4xl">
<ol class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
@foreach ($items as $i => $item)
    <li class="relative border-t-2 border-brand pt-5">
        <span class="font-display text-4xl text-brand/40">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
        <h3 class="mt-2 font-semibold">{{ $item['title'] ?? '' }}</h3>
        @if (($item['text'] ?? '') !== '') <p class="mt-1 text-sm text-ink-soft">{{ $item['text'] }}</p> @endif
    </li>
@endforeach
</ol>
</div></div></section>
