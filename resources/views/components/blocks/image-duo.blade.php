{{-- C6 · Image Duo/Trio (§4): art-directed overlapping spread. --}}
@props(['data' => [], 'isLead' => false])
@php
    $data = is_array($data) ? $data : [];
    $items = is_array($data['items'] ?? null) ? array_values($data['items']) : [];
@endphp
<section {{ $attributes }} data-theme="light"><div class="px-4 py-12 md:px-6 md:py-16"><div class="container mx-auto max-w-4xl">
<div @class(['grid gap-4', 'sm:grid-cols-2' => count($items) === 2, 'sm:grid-cols-3' => count($items) >= 3])>
    @foreach ($items as $item)
        <figure class="overflow-hidden rounded-2xl border border-line bg-paper-3">
            @php($media = ($item['media_id'] ?? null) ? \App\Models\Media::query()->find($item['media_id']) : null)
            @if ($media)
                <x-media :media="$media" conversion="card" class="w-full [&>img]:aspect-[4/3] [&>img]:w-full [&>img]:object-cover" />
            @else
                <div class="flex aspect-[4/3] items-center justify-center text-xs text-ink-muted">Image slot (alt enforced at upload)</div>
            @endif
            @if (($item['caption'] ?? '') !== '') <figcaption class="p-3 text-xs text-ink-muted">{{ $item['caption'] }}</figcaption> @endif
        </figure>
    @endforeach
</div>
</div></div></section>
