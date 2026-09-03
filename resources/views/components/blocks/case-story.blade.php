{{-- D5 · Case Story (section-library §5): anonymized enterprise
     scenario — challenge → approach → outcome + honest metrics. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $metrics = is_array($data['metrics'] ?? null) ? array_values($data['metrics']) : [];
@endphp

<section {{ $attributes }} data-theme="light">
    <div class="px-4 py-12 md:px-6 md:py-16">
        <div class="container mx-auto max-w-4xl">
            <p class="eyebrow text-ink-muted">{{ $data['client_label'] ?? 'Client story' }}</p>

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach ([['Challenge', $data['challenge'] ?? ''], ['Approach', $data['approach'] ?? ''], ['Outcome', $data['outcome'] ?? '']] as [$label, $copy])
                    <div class="rounded-2xl border border-line bg-paper-2 p-5">
                        <h3 class="font-display text-lg">{{ $label }}</h3>
                        <p class="mt-2 text-sm text-ink-soft">{{ $copy }}</p>
                    </div>
                @endforeach
            </div>

            @if ($metrics !== [])
                <dl class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                    @foreach ($metrics as $metric)
                        <div class="text-center">
                            <dt class="sr-only">{{ $metric['label'] ?? '' }}</dt>
                            <dd>
                                <span class="font-display text-3xl text-brand">{{ $metric['value'] ?? '' }}</span>
                                <span class="mt-1 block text-xs uppercase tracking-wide text-ink-muted">{{ $metric['label'] ?? '' }}</span>
                            </dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>
    </div>
</section>
