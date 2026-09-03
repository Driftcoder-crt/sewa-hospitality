{{-- /csr (09 doc §3): program intro, partner cards (real link-outs,
     claims with as-of), stories feed, past associations honesty list. --}}
@extends('layouts.app')

@section('title', 'CSR — Sewa Hospitality')
@section('meta_description', "Sewa Hospitality's community program: named NGO partners with real link-outs, measurable claims with as-of dating, and citable impact stories.")

@push('head')
    <link rel="canonical" href="{{ rtrim(config('app.url', 'https://sewahospitality.com'), '/') }}/csr">
    <x-site.hreflang />
@endpush

@section('content')
    <section data-theme="light" class="px-4 py-12 md:px-6 md:py-16">
        <div class="container mx-auto max-w-5xl">
            <p class="eyebrow text-ink-muted">Community</p>
            <h1 class="font-display mt-2 text-4xl md:text-5xl">Care, delivered — beyond the move.</h1>
            <p class="mt-4 max-w-2xl text-lg text-ink-soft">
                Our name means service. A share of what we earn goes back into the cities we
                work in — with named partners, real numbers and dates you can check.
            </p>

            <h2 class="font-display mt-12 text-2xl">Partners</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($active as $partner)
                    <article class="flex flex-col rounded-2xl border border-line bg-paper-2 p-5">
                        <h3 class="font-display text-xl">{{ $partner->name }}</h3>
                        <p class="mt-2 text-sm text-ink-soft">{{ str($partner->description ?? '')->limit(140) }}</p>

                        @if ($partner->claim)
                            <p class="mt-3 rounded-lg bg-paper-3 px-3 py-2 text-sm">
                                <strong>{{ $partner->claim }}</strong>
                                @if ($partner->claim_as_of) <span class="text-xs text-ink-muted">as of {{ $partner->claim_as_of }}</span> @endif
                            </p>
                        @endif

                        <div class="mt-auto flex items-center justify-between pt-4 text-sm">
                            @if ($partner->website)
                                <a href="{{ $partner->website }}" target="_blank" rel="noopener" class="font-semibold text-brand hover:underline">
                                    Official site ↗
                                </a>
                            @endif
                            @if ($partner->since) <span class="text-xs text-ink-muted">Partner since {{ $partner->since }}</span> @endif
                        </div>
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-line p-8 text-center text-ink-soft sm:col-span-2 lg:col-span-3">Partner announcements land here with our first verified claims.</p>
                @endforelse
            </div>

            <h2 class="font-display mt-12 text-2xl">Impact stories</h2>
            <div class="mt-4 flex flex-col gap-3">
                @forelse ($stories as $story)
                    <a href="{{ $story->publicPath() }}" class="rounded-xl border border-line bg-paper-2 p-4 hover:bg-paper-3">
                        <span class="font-semibold">{{ $story->title }}</span>
                        <span class="ms-2 text-xs text-ink-muted">{{ $story->partner?->name }} · {{ $story->published_at?->format('M Y') }}</span>
                    </a>
                @empty
                    <p class="text-sm text-ink-muted">Stories are being written with our partners.</p>
                @endforelse
            </div>

            @if ($past->isNotEmpty())
                <details class="mt-10 rounded-xl border border-line bg-paper-2 p-4">
                    <summary class="cursor-pointer text-sm font-semibold">Past associations ({{ $past->count() }})</summary>
                    <ul class="mt-3 flex flex-wrap gap-2 text-sm">
                        @foreach ($past as $partner)
                            <li class="rounded-full border border-line px-3 py-1 text-ink-muted">{{ $partner->name }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </div>
    </section>
@endsection
