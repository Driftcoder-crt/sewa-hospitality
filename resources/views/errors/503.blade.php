{{-- 503 — maintenance page (deploy window or queue stall), honest copy,
     never dead-end. --}}
@extends('layouts.app')

@section('title', 'Briefly offline — Sewa Hospitality')

@section('content')
    <section data-theme="light" class="px-4 py-20 md:py-28">
        <div class="container mx-auto max-w-2xl text-center">
            <p class="eyebrow text-ink-muted">503</p>
            <h1 class="font-display mt-3 text-4xl md:text-5xl">We are briefly offline.</h1>
            <p class="mt-4 text-lg text-ink-soft">Scheduled maintenance is in progress. This page will be back shortly — call us if your need is urgent.</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <x-button href="tel:+919873255531" variant="primary">Call +91 98732 55531</x-button>
            </div>
        </div>
    </section>
@endsection
