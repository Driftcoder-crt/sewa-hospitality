{{-- A3 · Section Wrapper (section-library §2) — the framing primitive
     every other block sits in: theme slot, anchor id, eyebrow + title +
     intro, padding density. Renders its children via $slot. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $density = $data['density'] ?? 'normal';
    $paddings = ['compact' => 'py-10 md:py-12', 'normal' => 'py-14 md:py-20', 'spacious' => 'py-20 md:py-28'];
    $anchor = $data['anchor_id'] ?? null;
@endphp

<section {{ $attributes->merge($anchor ? ['id' => \Illuminate\Support\Str::slug($anchor)] : []) }}>
    <div data-theme="light" class="{{ $paddings[$density] ?? $paddings['normal'] }}">
        <div class="container mx-auto px-4 md:px-6">
            <div class="max-w-3xl">
                @if ($data['eyebrow'] ?? null)
                    <p class="eyebrow text-ink-muted">{{ $data['eyebrow'] }}</p>
                @endif
                @if ($data['title'] ?? null)
                    <h2 class="font-display mt-2 text-3xl md:text-4xl">{{ $data['title'] }}</h2>
                @endif
                @if ($data['intro'] ?? null)
                    <p class="mt-3 text-lg text-ink-soft">{{ $data['intro'] }}</p>
                @endif
            </div>

            <div class="mt-8">
                {{ $slot }}
            </div>
        </div>
    </div>
</section>
