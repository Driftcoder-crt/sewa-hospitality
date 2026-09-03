{{-- /services — the catalog hub (services doc §3): intro + 11 service
     cards + family sections; never manually duplicated copy — cards
     compose from the seeded tree. --}}
@extends('layouts.app')

@section('title', 'Services — Sewa Hospitality')
@section('meta_description', 'Eleven mobility and hospitality services across India: relocation, immigration, serviced apartments, moving, corporate housing, fleet, travel, business space, recruitment, interiors and sanitization.')

@push('head')
    <link rel="canonical" href="{{ rtrim(config('app.url', 'https://sewahospitality.com'), '/') }}/services">
    <x-site.hreflang />
    <meta name="robots" content="index, follow, max-image-preview:large">
@endpush

@section('content')
    <section data-theme="light" class="px-4 py-14 md:px-6 md:py-20">
        <div class="container mx-auto max-w-3xl">
            <p class="eyebrow text-ink-muted">WHAT WE DO</p>
            <h1 class="font-display mt-3 text-4xl md:text-5xl">Every stage of a move, one partner.</h1>
            <p class="mt-4 text-lg text-ink-soft">From visa appointments to lease signings, from fleet dispatch to move-out cleaning — eleven services under one accountable roof.</p>
        </div>
    </section>

    <section data-theme="light" class="px-4 pb-14 md:px-6 md:pb-20">
        <div class="container mx-auto">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)
                    <x-service-card :service="$service" />
                @endforeach
            </div>

            @if ($services->isEmpty())
                <x-empty-state title="Service catalog coming right up" description="Our team is publishing the full catalog — call us meanwhile, a consultant answers." />
            @endif
        </div>
    </section>

    @foreach ($families as $entry)
        @php($family = $entry['service'])
        @if ($family->family === \App\Modules\Services\Enums\ServiceFamily::Standalone)
            @continue {{-- the immigration hub renders as a card above --}}
        @endif
        <section data-theme="light" class="border-t border-line px-4 py-12 md:px-6">
            <div class="container mx-auto">
                <div class="flex items-baseline justify-between gap-4">
                    <h2 class="font-display text-2xl md:text-3xl">{{ $family->name }}</h2>
                    <a href="{{ $family->publicPath() }}" class="inline-flex min-h-[44px] items-center text-sm font-semibold text-brand hover:underline">Family page →</a>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($entry['children'] as $child)
                        <x-service-card :service="$child" />
                    @endforeach
                </div>
            </div>
        </section>
    @endforeach

    <x-blocks.cta-band :data="[
        'headline' => 'Not sure which service you need?',
        'copy' => 'Describe the move — a consultant maps the right services and a scoped plan.',
        'theme' => 'brand',
        'layout' => 'centered',
        'ctas' => [['label' => 'Talk to a consultant', 'url' => '/contact', 'variant' => 'primary']],
    ]" />
@endsection
