{{-- /cities — hub (cities doc §3): map-style grid, hub cities first,
     1-line relocation snapshot per city. --}}
@extends('layouts.app')

@section('title', 'Cities we serve — Sewa Hospitality')
@section('meta_description', 'Sewa Hospitality operates across India — relocation, housing and immigration support in Gurugram, Mumbai, Bengaluru, Delhi, Pune, Hyderabad, Chennai and beyond.')

@push('head')
    <link rel="canonical" href="{{ rtrim(config('app.url', 'https://sewahospitality.com'), '/') }}/cities">
    <x-site.hreflang />
    <meta name="robots" content="index, follow, max-image-preview:large">
@endpush

@section('content')
    <section data-theme="light" class="px-4 py-14 md:px-6 md:py-20">
        <div class="container mx-auto max-w-3xl">
            <p class="eyebrow text-ink-muted">WHERE WE OPERATE</p>
            <h1 class="font-display mt-3 text-4xl md:text-5xl">Cities, covered with care.</h1>
            <p class="mt-4 text-lg text-ink-soft">Honest, dated local information for every city we serve — relocation notes, housing options and service availability.</p>
        </div>
    </section>

    <section data-theme="light" class="px-4 pb-14 md:px-6 md:pb-20">
        <div class="container mx-auto grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($cities as $city)
                <a href="{{ $city->publicPath() }}"
                   class="group flex min-h-[44px] flex-col rounded-2xl border border-line bg-paper p-6 transition-colors hover:bg-paper-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    <div class="flex items-center gap-2">
                        <h2 class="font-display text-xl text-ink">{{ $city->name }}</h2>
                        @if ($city->is_hub)
                            <span class="rounded-full bg-brand/10 px-2 py-0.5 text-xs font-semibold text-ink">Hub</span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-ink-muted">{{ $city->state }}</p>
                    @if ($city->description)
                        <p class="mt-2 text-sm leading-relaxed text-ink-soft">{{ \Illuminate\Support\Str::limit(strip_tags($city->description), 120) }}</p>
                    @endif
                    <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand">Relocating to {{ $city->name }} <span class="transition-transform group-hover:translate-x-0.5" aria-hidden="true">→</span></span>
                </a>
            @endforeach
        </div>

        @if ($cities->isEmpty())
            <x-empty-state title="City program publishing now" description="Wave-one city guides are on their way — call us meanwhile and a consultant covers your destination." />
        @endif
    </section>
@endsection
