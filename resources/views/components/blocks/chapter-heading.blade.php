{{-- B3 · Chapter Heading (section-library §3) — big serif display
     divider with optional number ("01") + rule line (orionix-style).
     Renders an <h2> (the H1 belongs to the lead hero — ladder rule). --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
@endphp

<div {{ $attributes }}>
    <div data-theme="light" class="px-4 pt-12 md:px-6 md:pt-16">
        <div class="container mx-auto flex items-end gap-4">
            @if ($data['number'] ?? null)
                <span class="font-display text-sm text-accent md:text-base" aria-hidden="true">{{ $data['number'] }}</span>
            @endif
            <h2 class="font-display text-3xl md:text-5xl">{{ $data['title'] ?? '' }}</h2>
        </div>
        <div class="container mx-auto mt-4 h-px bg-line" aria-hidden="true"></div>
    </div>
</div>
