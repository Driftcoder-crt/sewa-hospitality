{{-- B9 · Story Pillars (section-library §3) — 3–5 tall cards with tall
     imagery, title, 2-line hook. Values / service pillars. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $items = is_array($data['items'] ?? null) ? array_values($data['items']) : [];
@endphp

<div {{ $attributes }}>
    <div data-theme="light" class="px-4 py-10 md:px-6 md:py-14">
        <div class="container mx-auto grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($items as $item)
                @php($media = ($item['media_id'] ?? null) ? \App\Models\Media::query()->find($item['media_id']) : null)
                <article class="overflow-hidden rounded-2xl border border-line bg-paper">
                    <div class="aspect-[3/4] bg-paper-3">
                        @if ($media)
                            <x-media :media="$media" conversion="card" class="h-full w-full [&>img]:h-full [&>img]:w-full [&>img]:object-cover" />
                        @endif
                    </div>
                    <div class="p-5">
                        <h3 class="font-display text-xl">{{ $item['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-soft">{{ $item['hook'] ?? '' }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</div>
