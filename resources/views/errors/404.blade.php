{{-- 404 — never dead-end (ux-interactions §1): search arrives M2; until
     then menus + phone + top services links keep the visitor moving. --}}
@extends('layouts.app')

@section('title', 'Page not found — Sewa Hospitality')

@section('content')
    <section data-theme="light" class="px-4 py-20 md:px-6 md:py-28">
        <div class="container mx-auto max-w-2xl text-center">
            <p class="eyebrow text-ink-muted">404</p>
            <h1 class="font-display mt-3 text-4xl md:text-5xl">This page has moved on.</h1>
            <p class="mt-4 text-lg text-ink-soft">The link may be outdated — here is where most people are headed:</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <x-button href="{{ route('home') }}">Back to home</x-button>
                <x-button href="{{ route('contact') }}" variant="secondary">Contact us</x-button>
            </div>
            <p class="mt-6 text-sm text-ink-muted">
                Prefer to talk? Call
                <a href="tel:+919873255531" class="font-semibold text-brand underline underline-offset-2">+91 98732 55531</a>
                — a consultant answers.
            </p>
        </div>
    </section>

    <section data-theme="brand" class="px-4 py-12 md:px-6">
        <div class="container mx-auto">
            <h2 class="font-display text-2xl text-paper">Popular destinations</h2>
            <ul class="mt-4 flex flex-wrap gap-3">
                <li><a href="{{ route('home') }}" class="inline-flex min-h-[44px] items-center rounded-full border border-paper/40 px-5 text-sm font-semibold text-paper hover:bg-paper/10">Home</a></li>
                @try
                    <li><a href="{{ route('about') }}" class="inline-flex min-h-[44px] items-center rounded-full border border-paper/40 px-5 text-sm font-semibold text-paper hover:bg-paper/10">About</a></li>
                    <li><a href="{{ route('contact') }}" class="inline-flex min-h-[44px] items-center rounded-full border border-paper/40 px-5 text-sm font-semibold text-paper hover:bg-paper/10">Contact</a></li>
                @endtry
            </ul>
        </div>
    </section>
@endsection
