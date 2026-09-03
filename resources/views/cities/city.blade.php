{{-- /cities/{slug} — the money template (cities doc §3): hero → intro
    → coverage strip → housing cards → content blocks (neighborhoods/
    schools/FRRO answer-first) → FAQ → CTA. Coverage truth enforced. --}}
@extends('layouts.app')

@section('title', ($city->meta_title ?: 'Relocating to '.$city->name).' — Sewa Hospitality')
@section('meta_description', $city->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $city->description), 155))

@push('head')
    <link rel="canonical" href="{{ rtrim(config('app.url', 'https://sewahospitality.com'), '/') }}{{ $city->publicPath() }}">
    <x-site.hreflang :alternates="\App\Modules\I18n\Services\ContentVariants::alternatesFor($city)" />
    <meta name="robots" content="{{ $city->noindex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large' }}">
    <meta property="og:locale" content="en_IN">
    <meta property="og:type" content="place">
@endpush

@section('content')
    @include('cms.partials.blocks', ['blocks' => array_merge(
        [['type' => 'hero', 'data' => [
            'eyebrow' => mb_strtoupper($city->name).' · '.mb_strtoupper($city->state),
            'headline' => 'Relocating to '.$city->name.'?',
            'sub' => \Illuminate\Support\Str::limit(strip_tags((string) $city->description), 180),
            'height' => 'compact',
            'overlay' => 'none',
            'align' => 'start',
            'ctas' => [['label' => 'Talk to a consultant', 'url' => '/contact', 'variant' => 'primary']],
        ]]],
        $blocks,
    ), 'leadIndex' => 0])

    @if ($coverage->isNotEmpty())
        <section data-theme="light" class="border-t border-line px-4 py-10 md:px-6">
            <div class="container mx-auto">
                <p class="eyebrow text-ink-muted">SERVICES IN {{ mb_strtoupper($city->name) }}</p>
                <ul class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($coverage as $service)
                        <li>
                            <a href="{{ $service->publicPath() }}"
                               class="flex min-h-[44px] items-center gap-2 rounded-xl border border-line bg-paper p-3 text-sm font-medium text-ink-soft hover:bg-paper-3 hover:text-ink">
                                @if ($service->icon_svg_key) <x-icon name="{{ $service->icon_svg_key }}" class="h-4 w-4 text-brand" /> @endif
                                {{ $service->name }}
                                @if ($service->pivot->note) <span class="ms-auto text-xs text-ink-muted">{{ $service->pivot->note }}</span> @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <section data-theme="light" class="px-4 py-12 md:px-6">
        <div class="container mx-auto">
            <div class="flex items-baseline justify-between gap-4">
                <h2 class="font-display text-2xl md:text-3xl">Housing in {{ $city->name }}</h2>
                <a href="/housing?city={{ $city->slug }}" class="inline-flex min-h-[44px] items-center text-sm font-semibold text-brand hover:underline">Browse all →</a>
            </div>
            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($units as $unit)
                    <x-housing-card :unit="$unit" />
                @endforeach
            </div>
            @if ($units->isEmpty())
                <div class="mt-2">
                    <x-empty-state title="Inventory for {{ $city->name }} updates continuously"
                        description="Tell us your dates and brief — a consultant sends current options with dated rates." >
                        <x-button href="/contact" variant="secondary" size="sm">Request options</x-button>
                    </x-empty-state>
                </div>
            @endif
        </div>
    </section>

    <x-blocks.cta-band :data="[
        'headline' => 'Moving to '.$city->name.'?',
        'copy' => 'A named consultant plans the whole move — housing, immigration, settling-in.',
        'theme' => 'brand',
        'layout' => 'centered',
        'ctas' => [['label' => 'Talk to a consultant', 'url' => '/contact', 'variant' => 'primary']],
    ]" />
@endsection
