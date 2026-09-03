{{-- /housing/verified — the published checklist behind the Sewa
    Verified badge (cities doc §5 "verified standard published" — the
    transparency play the reference only implies). --}}
@extends('layouts.app')

@section('title', 'The Sewa Verified standard — Sewa Hospitality')
@section('meta_description', 'What "Sewa Verified" means: the published inspection checklist every listed home passes — safety, working systems, housekeeping and honest rates.')

@push('head')
    <link rel="canonical" href="{{ rtrim(config('app.url', 'https://sewahospitality.com'), '/') }}/housing/verified">
    <x-site.hreflang />
    <meta name="robots" content="index, follow, max-image-preview:large">
@endpush

@section('content')
    <section data-theme="light" class="px-4 py-14 md:px-6 md:py-20">
        <div class="container mx-auto max-w-3xl">
            <p class="eyebrow text-ink-muted">TRANSPARENCY</p>
            <h1 class="font-display mt-3 text-4xl md:text-5xl">What Sewa Verified means.</h1>
            <p class="mt-4 text-lg text-ink-soft">A listed home carries the badge only after our own team inspects it against this checklist. Verification is dated, and homes are re-verified every six months — a stale badge is not allowed to stand.</p>
        </div>
    </section>

    <section data-theme="light" class="px-4 pb-16 md:px-6">
        <div class="container mx-auto max-w-3xl">
            <x-blocks.accordion :data="[
                'first_open' => true,
                'items' => [
                    ['title' => '01 · Safety basics', 'body_html' => '<p>Working smoke detection where required, safe electrics, secure locks on doors and windows, first-aid kit present, and clear emergency exits.</p>'],
                    ['title' => '02 · Working systems', 'body_html' => '<p>Air conditioning, water heating, kitchen appliances and internet tested on inspection day — with service contacts on file.</p>'],
                    ['title' => '03 · Housekeeping standard', 'body_html' => '<p>Deep-clean before move-in, scheduled housekeeping, linen and consumables replaced to the published schedule.</p>'],
                    ['title' => '04 · Honest presentation', 'body_html' => '<p>Photos, amenities and rates reflect the home as it is. Rates are published as "from" ranges with an as-of date — never bait pricing.</p>'],
                    ['title' => '05 · Accountable management', 'body_html' => '<p>One named Sewa contact per tenancy, documented handover and move-out condition reports.</p>'],
                ],
            ]" />

            <p class="mt-8 text-sm text-ink-soft">Found a listing that doesn't match its badge? <a href="/contact" class="font-semibold text-brand underline underline-offset-2">Tell us</a> — we investigate within two business days.</p>
        </div>
    </section>
@endsection
