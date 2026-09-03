{{-- B6 · Timeline (section-library §3) — vertical dated milestones
     with connector line; relocation journey / company story. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $items = is_array($data['items'] ?? null) ? array_values($data['items']) : [];
@endphp

<div {{ $attributes }}>
    <div data-theme="light" class="px-4 py-10 md:px-6 md:py-12">
        <div class="container mx-auto max-w-3xl">
            <ol class="relative border-s-2 border-line ps-6">
                @foreach ($items as $item)
                    <li class="relative pb-8 last:pb-0">
                        <span class="absolute -start-[31px] top-1 inline-flex h-4 w-4 items-center justify-center rounded-full border-2 border-brand bg-paper" aria-hidden="true"></span>
                        <p class="eyebrow text-ink-muted">{{ $item['date'] ?? '' }}</p>
                        <h3 class="font-display mt-1 text-xl">{{ $item['title'] ?? '' }}</h3>
                        @if ($item['text'] ?? null)
                            <p class="mt-1 text-sm leading-relaxed text-ink-soft">{{ $item['text'] }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</div>
