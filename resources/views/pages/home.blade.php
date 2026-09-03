@extends('layouts.app')

@section('title', 'Sewa Hospitality — Care, delivered.')
@section('meta_description', 'Corporate relocation, global mobility and hospitality services across India — Sewa Verified housing, immigration support and a named consultant for every move.')

@push('head')
    <link rel="canonical" href="{{ rtrim(config('app.url', 'https://sewahospitality.com'), '/') }}/">
    <x-site.hreflang />
    {{-- JSON-LD precomputed; the @json directive with a multi-line array
         literal breaks the chained Livewire/Blade compilers. --}}
    @php
        $identity = \App\Support\Cms\Identity::current();
        $homeSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => rtrim(config('app.url', 'https://sewahospitality.com'), '/').'/#organization',
                    'name' => $identity['legalName'] ?? 'SEWA HOSPITALITY SERVICES PVT. LTD.',
                    'alternateName' => $identity['brand'] ?? 'Sewa Hospitality',
                    'url' => rtrim(config('app.url', 'https://sewahospitality.com'), '/').'/',
                    'logo' => rtrim(config('app.url', 'https://sewahospitality.com'), '/').'/favicon.svg',
                    'slogan' => $identity['slogan'] ?? 'Care, delivered.',
                    'email' => $identity['email'] ?? 'hello@sewahospitality.com',
                    'telephone' => $identity['telephone_e164'] ?? '+919873255531',
                    'foundingDate' => $identity['foundingDate'] ?? '2026',
                    'address' => [
                        '@type' => 'PostalAddress',
                        'streetAddress' => $identity['address']['street'] ?? 'MS0228, 2nd Floor, DT Mega Mall, DLF Phase 1',
                        'addressLocality' => $identity['address']['city'] ?? 'Gurugram',
                        'addressRegion' => $identity['address']['state'] ?? 'Haryana',
                        'postalCode' => $identity['address']['postalCode'] ?? '122002',
                        'addressCountry' => $identity['address']['country'] ?? 'IN',
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => rtrim(config('app.url', 'https://sewahospitality.com'), '/').'/#website',
                    'url' => rtrim(config('app.url', 'https://sewahospitality.com'), '/').'/',
                    'name' => $identity['brand'] ?? 'Sewa Hospitality',
                    'publisher' => ['@id' => rtrim(config('app.url', 'https://sewahospitality.com'), '/').'/#organization'],
                    'inLanguage' => app()->getLocale(),
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => ['@type' => 'EntryPoint', 'urlTemplate' => rtrim(config('app.url', 'https://sewahospitality.com'), '/').'/search?q={search_term_string}'],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">
        {!! json_encode($homeSchema, 15) !!}
    </script>
@endpush

@section('content')
    {{-- M0 FOUNDATION PLACEHOLDER — honest, no invented numbers or claims.
         Stats/counters arrive with the CMS block system (stats, cta_band) in
         M1/M2 and render only live, dated values. Exactly one <h1> per page. --}}

    {{-- Hero — brand immersion section (theme-engine §4): the bg-brand /
         text-brand-ink pair auto-inverts to light-on-teal inside this theme. --}}
    <section data-theme="brand" class="px-4 md:px-6 py-20 md:py-28">
        <div class="container mx-auto max-w-6xl">
            <p class="eyebrow">SEWA HOSPITALITY</p>
            <h1 class="font-display mt-3 text-4xl md:text-6xl max-w-3xl">Care, delivered.</h1>
            <p class="mt-4 text-lg text-ink-soft max-w-2xl">Corporate relocation, global mobility and hospitality services across India — for teams, families and the people who move them.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="tel:+919873255531" class="min-h-[44px] inline-flex items-center px-6 rounded-full bg-brand text-brand-ink">Talk to a consultant</a>
                <a href="tel:+919873255531" class="min-h-[44px] inline-flex items-center px-6 rounded-full border border-line text-ink">Call +91 98732 55531</a>
            </div>
            <p class="mt-3 text-sm text-ink-muted">Foundation build — the CMS-composed homepage lands in Milestone 1.</p>
        </div>
    </section>

    <section data-theme="light" class="px-4 md:px-6 py-16">
        <div class="container mx-auto max-w-6xl grid gap-8 md:grid-cols-3">
            <div class="bg-paper-2 border border-line rounded-xl p-6">
                <p class="eyebrow text-ink-muted">Mobility</p>
                <h2 class="font-display mt-2 text-2xl">Global mobility</h2>
                <p class="mt-3 text-ink-soft">Immigration, relocation and settling-in support for international teams.</p>
            </div>
            <div class="bg-paper-2 border border-line rounded-xl p-6">
                <p class="eyebrow text-ink-muted">Housing</p>
                <h2 class="font-display mt-2 text-2xl">Corporate housing</h2>
                <p class="mt-3 text-ink-soft">Serviced apartments and managed stays, verified by our own standard.</p>
            </div>
            <div class="bg-paper-2 border border-line rounded-xl p-6">
                <p class="eyebrow text-ink-muted">Coverage</p>
                <h2 class="font-display mt-2 text-2xl">Cities &amp; housing program</h2>
                <p class="mt-3 text-ink-soft">An all-India city program with honest, dated local information.</p>
            </div>
        </div>
    </section>

    <section data-theme="deep" class="px-4 md:px-6 py-16">
        <div class="container mx-auto max-w-4xl text-center">
            <h2 class="font-display text-3xl">Moving to India?</h2>
            <p class="mt-3 text-ink-soft">Talk to a consultant about your move.</p>
            <a href="tel:+919873255531" class="mt-6 inline-flex min-h-[44px] items-center rounded-full bg-brand text-brand-ink px-6">Talk to a consultant</a>
        </div>
    </section>
@endsection
