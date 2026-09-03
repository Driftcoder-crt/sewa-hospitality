{{-- 500 — honest system error, never dead-end. Identity renders from
     the NAP-locked fallback so this page works even during a database
     outage (05-security-reliability §6). --}}
@extends('layouts.app')

@section('title', 'Something went wrong — Sewa Hospitality')

@section('content')
    <section data-theme="light" class="px-4 py-20 md:py-28">
        <div class="container mx-auto max-w-2xl text-center">
            <p class="eyebrow text-ink-muted">500</p>
            <h1 class="font-display mt-3 text-4xl md:text-5xl">Something went wrong on our side.</h1>
            <p class="mt-4 text-lg text-ink-soft">The team has been notified automatically. Please try again in a moment — or call us and we will handle it personally.</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <x-button href="{{ route('home') }}">Back to home</x-button>
                <x-button href="tel:+919873255531" variant="secondary">Call +91 98732 55531</x-button>
            </div>
            <p class="mt-6 text-sm text-ink-muted">Live system health: <a href="{{ route('status') }}" class="font-semibold text-brand underline underline-offset-2">sewahospitality.com/status</a></p>
        </div>
    </section>
@endsection
