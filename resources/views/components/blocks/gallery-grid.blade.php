{{-- C1 · Gallery Grid (section-library §4) — real <img> + alt, captions,
     2–4 col, aspect presets. Lightbox arrives with the CSR/about usage
     wave (C-series polish in M4); grid is complete now. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $items = is_array($data['items'] ?? null) ? array_values($data['items']) : [];
    $cols = ['2' => 'sm:grid-cols-2', '3' => 'sm:grid-cols-2 md:grid-cols-3', '4' => 'sm:grid-cols-2 md:grid-cols-4'][$data['columns'] ?? '3'] ?? 'sm:grid-cols-2 md:grid-cols-3';
    $aspects = ['square' => 'aspect-square', 'landscape' => 'aspect-[3/2]', 'portrait' => 'aspect-[3/4]'][$data['aspect'] ?? 'landscape'] ?? 'aspect-[3/2]';
@endphp

<div {{ $attributes }}>
    <div data-theme="light" class="px-4 py-10 md:px-6 md:py-12">
        <div class="container mx-auto grid gap-4 {{ $cols }}">
            @foreach ($items as $item)
                @php($media = ($item['media_id'] ?? null) ? \App\Models\Media::query()->find($item['media_id']) : null)
                <figure class="m-0">
                    @if ($media)
                        <div class="{{ $aspects }} overflow-hidden rounded-2xl">
                            <x-media :media="$media" conversion="card" class="h-full w-full [&>img]:h-full [&>img]:w-full [&>img]:object-cover" />
                        </div>
                    @else
                        <div class="{{ $aspects }} rounded-2xl bg-paper-3" aria-hidden="true"></div>
                    @endif
                    @if ($item['caption'] ?? null)
                        <figcaption class="mt-2 text-xs text-ink-muted">{{ $item['caption'] }}</figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </div>
</div>
