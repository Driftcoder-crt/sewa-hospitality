{{-- E5 · Promo Card Grid (section-library §6): 2–4 offer cards with
     badge, terms, validity and CTA. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $cols = in_array($data['columns'] ?? '3', ['2', '3', '4'], true) ? ($data['columns'] ?? '3') : '3';
    $items = is_array($data['items'] ?? null) ? array_values($data['items']) : [];
    $gridClass = match ($cols) {
        '2' => 'sm:grid-cols-2',
        '4' => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };
@endphp

<section {{ $attributes }} data-theme="light">
    <div class="px-4 py-12 md:px-6 md:py-16">
        <div class="container mx-auto">
            <div class="grid gap-4 {{ $gridClass }}">
                @foreach ($items as $item)
                    <article class="flex flex-col rounded-2xl border border-line bg-paper-2 p-5">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="font-display text-xl">{{ $item['title'] ?? '' }}</h3>
                            @if (($item['badge'] ?? '') !== '')
                                <span class="inline-flex shrink-0 items-center rounded-full bg-brand/15 px-2.5 py-1 text-xs font-semibold text-brand">{{ $item['badge'] }}</span>
                            @endif
                        </div>

                        @if (($item['terms'] ?? '') !== '')
                            <p class="mt-2 text-sm text-ink-soft">{{ $item['terms'] }}</p>
                        @endif

                        <div class="mt-auto flex items-center justify-between gap-2 pt-4">
                            @if (($item['cta_label'] ?? '') && ($item['cta_url'] ?? ''))
                                <a href="{{ $item['cta_url'] }}" class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-4 text-sm font-semibold text-brand-ink hover:opacity-90">
                                    {{ $item['cta_label'] }}
                                </a>
                            @endif
                            @if (($item['validity'] ?? '') !== '')
                                <span class="text-xs text-ink-muted">{{ $item['validity'] }}</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</section>
