{{-- B7 · FAQ (section-library §3) — accordion items + FAQPage JSON-LD
     (AEO gold, 05-aeo-llm-presence). Schema emitted ONLY here where
     answers are visibly rendered (schema-matches-visible rule). --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $items = is_array($data['items'] ?? null) ? array_values($data['items']) : [];
    $uid = 'faq-'.\Illuminate\Support\Str::random(5);

    // JSON-LD is built here and echoed via json_encode below — passing a
    // multi-line array literal to the @json directive breaks the chained
    // Livewire/Blade compilers (verified: truncated compiled output).
    $faqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($items)->map(fn (array $item): array => [
            '@type' => 'Question',
            'name' => (string) ($item['q'] ?? ''),
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => (string) ($item['a'] ?? '')],
        ])->values()->all(),
    ];
@endphp

<div {{ $attributes }}>
    <div data-theme="light" class="px-4 py-10 md:px-6 md:py-12">
        <div class="container mx-auto max-w-3xl">
            @if ($data['heading'] ?? null)
                <h2 class="font-display text-2xl md:text-3xl">{{ $data['heading'] }}</h2>
            @endif

            <div class="mt-4" x-data="{ open: null }">
                @foreach ($items as $i => $item)
                    <div class="border-b border-line">
                        <h3 class="m-0">
                            <button type="button"
                                    class="flex w-full items-center justify-between gap-4 py-4 text-start"
                                    @click="open = (open === {{ $i }} ? null : {{ $i }})"
                                    :aria-expanded="(open === {{ $i }}).toString()"
                                    aria-controls="{{ $uid }}-{{ $i }}">
                                <span class="font-display text-lg">{{ $item['q'] ?? '' }}</span>
                                <svg class="h-5 w-5 shrink-0 text-ink-muted transition-transform" :class="open === {{ $i }} && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.06l3.71-3.83a.75.75 0 1 1 1.08 1.04l-4.25 4.39a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
                            </button>
                        </h3>
                        <div x-show="open === {{ $i }}" x-collapse id="{{ $uid }}-{{ $i }}" role="region">
                            <p class="pb-4 leading-relaxed text-ink-soft">{{ $item['a'] ?? '' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if ($items !== [])
        <script type="application/ld+json">
            {!! json_encode($faqSchema, 15) !!}
        </script>
    @endif
</div>
