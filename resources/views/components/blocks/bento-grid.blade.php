{{-- A5 · Bento Grid (section-library §2): mixed-size editorial tiles. --}}
@props(['data' => [], 'isLead' => false])
@php
    $data = is_array($data) ? $data : [];
    $items = is_array($data['items'] ?? null) ? array_values($data['items']) : [];
@endphp
<section {{ $attributes }} data-theme="light"><div class="px-4 py-12 md:px-6 md:py-16"><div class="container mx-auto grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
@foreach ($items as $item)
    @php($span = match ($item['size'] ?? 'normal') { 'wide' => 'sm:col-span-2', 'tall' => 'sm:row-span-2', default => '' })
    <article class="rounded-2xl border border-line bg-paper-2 p-6 {{ $span }}">
        <h3 class="font-display text-xl">{{ $item['title'] ?? '' }}</h3>
        @if (($item['text'] ?? '') !== '') <p class="mt-2 text-sm text-ink-soft">{{ $item['text'] }}</p> @endif
    </article>
@endforeach
</div></div></section>
