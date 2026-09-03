{{-- A1 · Hero (05-section-block-library §2) — headline, sub, CTAs[1–2],
     media w/ overlay scrim, align, height presets (full/split/compact),
     eyebrow label. The page's LEAD hero renders the single <h1>
     (template-enforced, ui-components doc). Token-driven: any data-theme. --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $ctas = is_array($data['ctas'] ?? null) ? array_values($data['ctas']) : [];
    $height = $data['height'] ?? 'compact';
    $align = ($data['align'] ?? 'start') === 'center';
    $overlay = $data['overlay'] ?? 'soft';
    $heights = ['full' => 'min-h-[70vh] md:min-h-[78vh] py-20 md:py-32', 'split' => 'min-h-[50vh] py-16 md:py-24', 'compact' => 'py-14 md:py-20'];
    $scrim = match ($overlay) {
        'strong' => 'bg-ink-900/60',
        'soft' => 'bg-ink-900/25',
        default => '',
    };
@endphp

<section {{ $attributes }} aria-labelledby="{{ $isLead ? 'page-h1' : '' }}">
    <div class="relative {{ $heights[$height] ?? $heights['compact'] }} flex items-center overflow-hidden" data-theme="brand">
        @if ($data['media_id'] ?? null)
            @php($media = \App\Models\Media::query()->find($data['media_id']))
            @if ($media)
                <div class="absolute inset-0" aria-hidden="true">
                    <x-media :media="$media" conversion="hero" eager class="h-full w-full [&>img]:h-full [&>img]:w-full [&>img]:object-cover" />
                    @if ($scrim) <div class="absolute inset-0 {{ $scrim }}"></div> @endif
                </div>
                @php($textTone = 'text-paper')
            @else
                @php($textTone = 'text-ink')
            @endif
        @else
            @php($textTone = 'text-ink')
        @endif

        <div class="container relative mx-auto px-4 md:px-6">
            <div class="{{ $align ? 'mx-auto max-w-3xl text-center' : 'max-w-3xl' }}">
                @if ($data['eyebrow'] ?? null)
                    <p class="eyebrow {{ $textTone === 'text-paper' ? 'text-paper/80' : 'text-ink-muted' }}">{{ $data['eyebrow'] }}</p>
                @endif

                @if ($isLead)
                    <h1 id="page-h1" class="font-display mt-3 text-4xl md:text-6xl {{ $textTone }}">{{ $data['headline'] ?? '' }}</h1>
                @else
                    <h2 class="font-display mt-3 text-3xl md:text-5xl {{ $textTone }}">{{ $data['headline'] ?? '' }}</h2>
                @endif

                @if ($data['sub'] ?? null)
                    <p class="mt-4 text-lg {{ $textTone === 'text-paper' ? 'text-paper/85' : 'text-ink-soft' }}">{{ $data['sub'] }}</p>
                @endif

                @if ($ctas !== [])
                    <div class="mt-8 flex flex-wrap gap-3 {{ $align ? 'justify-center' : '' }}">
                        @foreach ($ctas as $cta)
                            @if (($cta['label'] ?? '') && ($cta['url'] ?? ''))
                                <x-button href="{{ $cta['url'] }}" variant="{{ ($cta['variant'] ?? 'primary') === 'secondary' ? ($textTone === 'text-paper' ? 'ghost' : 'secondary') : 'primary' }}">
                                    {{ $cta['label'] }}
                                </x-button>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
