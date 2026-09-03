{{-- /housing/{unit} — unit detail (cities doc §3): amenities, included
    services, honest from-rate, availability enquiry (feeds Leads M3
    with housing.* tag + unit ref). --}}
@extends('layouts.app')

@section('title', $unit->name.' — '.$unit->city?->name.' — Sewa Hospitality')
@section('meta_description', \Illuminate\Support\Str::limit($unit->name.' in '.($unit->locality ?: $unit->city?->name).': '.($unit->rateLabel() ?? 'rate on request').'. '.($unit->tier->label()).' housing, Sewa inspected.', 155))

@push('head')
    <meta name="robots" content="index, follow, max-image-preview:large">
    {{-- JSON-LD precomputed; the @json directive with a multi-line array
         literal breaks the chained Livewire/Blade compilers. --}}
    @php
        $unitSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Accommodation',
            'name' => $unit->name,
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $unit->locality ?: $unit->city?->name,
                'addressRegion' => $unit->city?->state,
                'addressCountry' => 'IN',
            ],
            ...(auth()->check() && $unit->isVerified() ? ['award' => 'Sewa Verified '.($unit->verified_at?->format('M Y'))] : []),
        ];
    @endphp
    <script type="application/ld+json">
        {!! json_encode($unitSchema, 15) !!}
    </script>
@endpush

@section('content')
    <section data-theme="light" class="px-4 py-10 md:px-6 md:py-14">
        <div class="container mx-auto max-w-5xl">
            <nav aria-label="Breadcrumb" class="text-xs text-ink-muted">
                <ol class="flex flex-wrap items-center gap-1">
                    <li><a href="/housing" class="hover:text-ink">Housing</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="/cities/{{ $unit->city?->slug }}" class="hover:text-ink">{{ $unit->city?->name }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li aria-current="page" class="text-ink">{{ $unit->name }}</li>
                </ol>
            </nav>

            <div class="mt-4 grid gap-8 md:grid-cols-[1fr_320px]">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-brand/10 px-2.5 py-1 text-xs font-semibold text-ink">{{ $unit->tier->label() }}</span>
                        <span class="rounded-full bg-paper-2 px-2.5 py-1 text-xs text-ink-muted">{{ $unit->type->label() }}</span>
                        @if ($unit->isVerified())
                            <span class="inline-flex items-center gap-1 rounded-full bg-success-500/15 px-2.5 py-1 text-xs font-semibold text-ink">
                                <x-icon name="shield-check" class="h-3.5 w-3.5 text-brand" /> Sewa Verified @if ($unit->verified_at) · {{ $unit->verified_at->format('M Y') }} @endif
                            </span>
                        @endif
                    </div>

                    <h1 class="font-display mt-3 text-3xl md:text-4xl">{{ $unit->name }}</h1>
                    <p class="mt-2 text-ink-soft">{{ $unit->locality ?: $unit->area }}{{ $unit->city?->name ? ', '.$unit->city->name : '' }} · {{ $unit->bedrooms }} {{ str('bedroom')->plural($unit->bedrooms) }}@if ($unit->area_sqft) · ≈ {{ number_format($unit->area_sqft) }} sq ft @endif</p>

                    @if ($unit->amenities)
                        <h2 class="font-display mt-8 text-xl">Amenities</h2>
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($unit->amenities as $amenity)
                                <li class="rounded-full border border-line bg-paper-2 px-3 py-1.5 text-sm text-ink-soft">{{ $amenity }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <h2 class="font-display mt-8 text-xl">Included services</h2>
                    <ul class="mt-3 list-disc space-y-1 ps-6 text-sm text-ink-soft">
                        <li>Housekeeping on the Sewa Verified schedule</li>
                        <li>Utilities and internet management</li>
                        <li>One named contact for the tenancy — care, delivered</li>
                    </ul>

                    @if ($unit->notes)
                        <h2 class="font-display mt-8 text-xl">Notes</h2>
                        <p class="mt-2 text-sm text-ink-soft">{{ $unit->notes }}</p>
                    @endif
                </div>

                <aside class="md:sticky md:top-24 h-fit rounded-2xl border border-line bg-paper-2 p-6">
                    @if ($unit->rateLabel())
                        <p class="font-display text-2xl text-ink">{{ $unit->rateLabel() }}</p>
                        <p class="mt-1 text-xs text-ink-muted">Rates vary by season and term — request an exact quote.@if ($unit->isRateStale()) <strong class="text-ink"> Confirm current rate with us.</strong>@endif</p>
                    @else
                        <p class="font-display text-2xl text-ink">Rate on request</p>
                        <p class="mt-1 text-xs text-ink-muted">Tell us your dates and we reply with a dated quote.</p>
                    @endif

                    <div class="mt-4 flex flex-col gap-2">
                        <x-button href="/contact" size="sm">Check availability</x-button>
                        <x-button href="tel:+919873255531" variant="secondary" size="sm">Call +91 98732 55531</x-button>
                    </div>
                    <p class="mt-3 text-xs text-ink-muted">Availability enquiry references this home — a consultant replies with current options.</p>
                </aside>
            </div>
        </div>
    </section>
@endsection
