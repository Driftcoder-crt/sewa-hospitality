{{-- E1 · CTA Band (section-library §6) — the recurring conversion band:
     headline, copy, 1–2 buttons, brand/deep theme, centered/split.
     Theme slots invert automatically (brand: light-on-teal; deep:
     light-on-ink). --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $ctas = is_array($data['ctas'] ?? null) ? array_values($data['ctas']) : [];
    $theme = in_array($data['theme'] ?? 'brand', ['brand', 'deep'], true) ? ($data['theme'] ?? 'brand') : 'brand';
    $split = ($data['layout'] ?? 'centered') === 'split';
@endphp

<section {{ $attributes }}>
    <div data-theme="{{ $theme }}" class="px-4 py-14 md:px-6 md:py-20">
        <div class="container mx-auto {{ $split ? 'flex flex-col gap-6 md:flex-row md:items-center md:justify-between' : 'mx-auto max-w-3xl text-center' }}">
            <div class="{{ $split ? 'max-w-2xl' : '' }}">
                <h2 class="font-display text-3xl md:text-4xl text-paper">{{ $data['headline'] ?? '' }}</h2>
                @if ($data['copy'] ?? null)
                    <p class="mt-3 text-lg text-paper/85">{{ $data['copy'] }}</p>
                @endif
            </div>

            @if ($ctas !== [])
                <div class="{{ $split ? 'shrink-0' : 'mt-8 flex justify-center' }} flex flex-wrap gap-3 {{ $split ? '' : '' }}">
                    @foreach ($ctas as $cta)
                        @if (($cta['label'] ?? '') && ($cta['url'] ?? ''))
                            @php($variant = ($cta['variant'] ?? 'primary') === 'secondary' ? 'secondary' : 'primary')
                            @if ($variant === 'primary')
                                {{-- On brand/deep surfaces the action inverts to light (theme-engine §3). --}}
                                <a href="{{ $cta['url'] }}"
                                   class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink transition-opacity hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                    {{ $cta['label'] }}
                                </a>
                            @else
                                <a href="{{ $cta['url'] }}"
                                   class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-full border border-paper/40 px-6 text-sm font-semibold text-paper transition-colors hover:bg-paper/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                    {{ $cta['label'] }}
                                </a>
                            @endif
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
