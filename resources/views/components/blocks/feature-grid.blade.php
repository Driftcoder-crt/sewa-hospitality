{{-- A4 · Feature Grid (section-library §2) — 2/3/4-col cards
     (border/plain/filled), per-cell icon + title + text + optional link.
     2–6px editorial radius; uniform aspect boxes. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $items = is_array($data['items'] ?? null) ? array_values($data['items']) : [];
    $cols = ['2' => 'md:grid-cols-2', '3' => 'md:grid-cols-3', '4' => 'md:grid-cols-2 xl:grid-cols-4'][$data['columns'] ?? '3'] ?? 'md:grid-cols-3';
    $styles = [
        'border' => 'border border-line bg-paper',
        'plain' => '',
        'filled' => 'border border-transparent bg-paper-2',
    ];
    $style = $styles[$data['style'] ?? 'border'] ?? $styles['border'];
@endphp

<div {{ $attributes }}>
    <div data-theme="light" class="px-4 py-12 md:px-6 md:py-16">
        <div class="container mx-auto grid gap-4 sm:grid-cols-2 {{ $cols }}">
            @foreach ($items as $item)
                @php($url = $item['url'] ?? '')
                <div class="rounded-2xl p-6 {{ $style }}">
                    @if ($item['icon'] ?? null)
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-brand/10 text-brand">
                            <x-icon name="{{ $item['icon'] }}" class="h-5 w-5" />
                        </span>
                    @endif
                    <h3 class="font-display mt-4 text-xl">{{ $item['title'] ?? '' }}</h3>
                    @if ($item['text'] ?? null)
                        <p class="mt-2 text-sm leading-relaxed text-ink-soft">{{ $item['text'] }}</p>
                    @endif
                    @if ($url)
                        <a href="{{ $url }}" class="mt-4 inline-flex min-h-[44px] items-center gap-1 text-sm font-semibold text-brand hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                            {{ ($item['link_label'] ?? 'Learn more') }}
                            <span aria-hidden="true">→</span>
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
