{{-- C3 · Full-Bleed Media (section-library §4) — cinematic section on
    the deep theme; optional quote overlay. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $media = ($data['media_id'] ?? null) ? \App\Models\Media::query()->find($data['media_id']) : null;
    $heights = ['half' => 'min-h-[50vh]', 'full' => 'min-h-[80vh]'][$data['height'] ?? 'half'] ?? 'min-h-[50vh]';
@endphp

<section {{ $attributes }}>
    <div data-theme="deep" class="relative {{ $heights }} flex items-end overflow-hidden">
        @if ($media)
            <x-media :media="$media" conversion="wide" class="absolute inset-0 h-full w-full [&>img]:h-full [&>img]:w-full [&>img]:object-cover" />
            <div class="absolute inset-0 bg-ink-900/40" aria-hidden="true"></div>
        @else
            <div class="absolute inset-0 bg-paper-3" aria-hidden="true"></div>
        @endif

        <div class="container relative mx-auto px-4 pb-10 md:px-6 md:pb-14">
            @if ($data['quote'] ?? null)
                <blockquote class="max-w-2xl font-display text-2xl text-paper md:text-3xl">“{{ $data['quote'] }}”</blockquote>
            @endif
            @if ($data['caption'] ?? null)
                <p class="mt-2 text-xs text-paper/70">{{ $data['caption'] }}</p>
            @endif
        </div>
    </div>
</section>
