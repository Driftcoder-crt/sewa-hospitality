{{-- A2 · Split Hero (section-library §2) — asymmetric copy/media split
     (wild-ag/nomu pattern). The optional email mini-form renders only
     when the leads module exists (M3) — until then it stays hidden so
     nothing dead-ends (ux-interactions §1). --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $ctas = is_array($data['ctas'] ?? null) ? array_values($data['ctas']) : [];
    $mediaSide = ($data['media_side'] ?? 'right') === 'left';
    $leadsWired = class_exists(\App\Modules\Leads\Models\Lead::class);
    $media = $data['media_id'] ?? null ? \App\Models\Media::query()->find($data['media_id']) : null;
@endphp

<section {{ $attributes }}>
    <div data-theme="light" class="grid items-stretch md:grid-cols-2">
        @if ($mediaSide)
            <div class="relative min-h-64 md:min-h-[520px]">
                @if ($media)
                    <x-media :media="$media" conversion="wide" eager class="absolute inset-0 h-full w-full [&>img]:h-full [&>img]:w-full [&>img]:object-cover" />
                @else
                    <div class="absolute inset-0 bg-paper-3"></div>
                @endif
            </div>
        @endif

        <div class="flex items-center px-4 py-14 md:px-10 md:py-20">
            <div class="max-w-xl">
                @if ($data['eyebrow'] ?? null)
                    <p class="eyebrow text-ink-muted">{{ $data['eyebrow'] }}</p>
                @endif

                @if ($isLead)
                    <h1 id="page-h1" class="font-display mt-3 text-4xl md:text-5xl">{{ $data['headline'] ?? '' }}</h1>
                @else
                    <h2 class="font-display mt-3 text-3xl md:text-4xl">{{ $data['headline'] ?? '' }}</h2>
                @endif

                @if ($data['sub'] ?? null)
                    <p class="mt-4 text-lg text-ink-soft">{{ $data['sub'] }}</p>
                @endif

                @if ($ctas !== [])
                    <div class="mt-8 flex flex-wrap gap-3">
                        @foreach ($ctas as $cta)
                            @if (($cta['label'] ?? '') && ($cta['url'] ?? ''))
                                <x-button href="{{ $cta['url'] }}" variant="{{ ($cta['variant'] ?? 'primary') === 'secondary' ? 'secondary' : 'primary' }}">
                                    {{ $cta['label'] }}
                                </x-button>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if ($leadsWired)
                    {{-- Email mini-form (E2, section-library §2 "optional form
                         mini (email+CTA)") — wired to the Leads module's
                         newsletter capture (double opt-in, no dead end). --}}
                    <div class="mt-8 max-w-md">
                        <livewire:leads.newsletter-signup :compact="true" />
                    </div>
                @endif
            </div>
        </div>

        @if (! $mediaSide)
            <div class="relative min-h-64 md:min-h-[520px]">
                @if ($media)
                    <x-media :media="$media" conversion="wide" eager class="absolute inset-0 h-full w-full [&>img]:h-full [&>img]:w-full [&>img]:object-cover" />
                @else
                    <div class="absolute inset-0 bg-paper-3"></div>
                @endif
            </div>
        @endif
    </div>
</section>
