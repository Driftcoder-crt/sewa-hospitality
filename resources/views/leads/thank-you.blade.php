{{-- /thank-you — SLA promise + what happens next + portal teaser.
     Noindex: utility surface, never an SEO target. --}}
@extends('layouts.app')

@section('title', 'Thank you — Sewa Hospitality')
@section('meta_description', 'Your request reached Sewa Hospitality. Here is exactly what happens next.')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <section data-theme="light" class="px-4 py-16 md:px-6 md:py-24">
        <div class="container mx-auto max-w-2xl">
            <p class="eyebrow text-ink-muted">{{ ['contact' => 'Message received', 'quote' => 'Quote request received', 'callback' => 'Callback booked'][$source] }}</p>

            <h1 class="font-display mt-3 text-4xl md:text-5xl">Thank you — you're in good hands.</h1>

            <div class="mt-8 flex flex-col gap-4">
                <div class="flex items-start gap-4 rounded-2xl border border-line bg-paper-2 p-5">
                    <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand text-brand-ink" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd"/></svg>
                    </span>
                    <div>
                        <h2 class="font-semibold text-ink">The promise</h2>
                        <p class="mt-1 text-ink-soft">{{ $promise }}</p>
                    </div>
                </div>

                <div class="flex items-start gap-4 rounded-2xl border border-line bg-paper-2 p-5">
                    <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand text-brand-ink" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                    </span>
                    <div>
                        <h2 class="font-semibold text-ink">What happens now</h2>
                        <ol class="mt-1 list-inside list-decimal text-ink-soft">
                            <li>We review what you shared.</li>
                            <li>A consultant is assigned and reaches out — no scripts, no pressure.</li>
                            <li>You get a clear, honest plan for your move.</li>
                        </ol>
                    </div>
                </div>

                <div class="flex items-start gap-4 rounded-2xl border border-line bg-paper-2 p-5">
                    <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand text-brand-ink" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3.5 2.75a.75.75 0 0 0-1.5 0v14.5a.75.75 0 0 0 1.5 0v-4.392l1.657-.348a6.449 6.449 0 0 1 4.271.572 7.948 7.948 0 0 0 5.965.524l2.078-.64A.75.75 0 0 0 18 12.25v-8.5a.75.75 0 0 0-.904-.734l-2.38.501a7.25 7.25 0 0 1-4.186-.363l-.502-.2a8.75 8.75 0 0 0-5.053-.439l-1.475.31V2.75Z" clip-rule="evenodd"/></svg>
                    </span>
                    <div>
                        <h2 class="font-semibold text-ink">Track your move later</h2>
                        <p class="mt-1 text-ink-soft">When your engagement starts you'll get a client portal invite — timeline, documents and your consultant, all in one place.</p>
                    </div>
                </div>
            </div>

            <p class="mt-8 text-sm text-ink-muted">
                Urgent? Call <a href="tel:+919873255531" class="font-semibold text-brand">+91 98732 55531</a>
                — or <a href="{{ route('home') }}" class="font-semibold text-brand">head back home</a>.
            </p>

            @if ($reference !== '')
                <p class="mt-2 text-xs text-ink-muted">Reference: {{ $reference }}</p>
            @endif
        </div>
    </section>
@endsection
