{{-- /careers (06-hr §3): intro, open roles by department, honest
     zero-state. The "life at Sewa" gallery rides the CMS page blocks
     via the careers content when composed. --}}
@extends('layouts.app')

@section('title', 'Careers — Sewa Hospitality')
@section('meta_description', 'Open roles at Sewa Hospitality — relocation, immigration, housing, fleet and technology teams in Gurugram and across India.')

@push('head')
    <link rel="canonical" href="{{ rtrim(config('app.url', 'https://sewahospitality.com'), '/') }}/careers">
    <x-site.hreflang />
    <meta name="robots" content="index, follow, max-image-preview:large">
@endpush

@section('content')
    <section data-theme="light" class="px-4 py-14 md:px-6 md:py-20">
        <div class="container mx-auto max-w-5xl">
            <p class="eyebrow text-ink-muted">Careers</p>
            <h1 class="font-display mt-3 text-4xl md:text-5xl">Care, delivered — by people like you.</h1>
            <p class="mt-4 max-w-2xl text-lg text-ink-soft">
                We move families, executives and teams across cities and countries. The work is
                logistics, paperwork and, mostly, people. Real roles, real hiring — every listing
                below is live and every application is read.
            </p>

            @forelse ($grouped as $department => $jobs)
                <div class="mt-10">
                    <h2 class="font-display text-2xl">{{ $departments[$department] ?? ucwords($department) }}</h2>

                    <ul class="mt-4 flex flex-col divide-y divide-line rounded-2xl border border-line bg-paper-2">
                        @foreach ($jobs as $job)
                            <li>
                                <a href="{{ $job->publicPath() }}"
                                   class="flex min-h-[72px] flex-col justify-center gap-1 px-5 py-4 transition-colors hover:bg-paper-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <span class="font-semibold text-ink">{{ $job->title }}</span>
                                        <span class="mt-0.5 block text-sm text-ink-muted">
                                            {{ $job->location_text }} · {{ $job->employment_type->label() }} · {{ $job->experienceLabel() }}
                                        </span>
                                    </div>
                                    <span class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-brand">
                                        View role
                                        <svg class="h-4 w-4 rtl:-scale-x-100" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <div class="mt-10 rounded-2xl border border-dashed border-line bg-paper-2 p-10 text-center">
                    <h2 class="font-display text-2xl">No open roles right now</h2>
                    <p class="mx-auto mt-3 max-w-md text-ink-soft">
                        We're a growing team and this list changes — check back soon, or send a
                        general note through the contact page and we'll keep you in mind.
                    </p>
                    <a href="/contact" class="mt-6 inline-flex min-h-[44px] items-center rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink">
                        Get in touch
                    </a>
                </div>
            @endforelse
        </div>
    </section>
@endsection
