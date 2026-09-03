{{-- /careers/{slug} — the per-job page (06-hr §3): role detail + apply
     form (open) OR the honest closed/paused state with "see similar"
     links. Never a 404 once the role existed (history/SEO rule). --}}
@extends('layouts.app')

@section('title', $posting->title.' — Careers — Sewa Hospitality')
@section('meta_description', $posting->title.' — '.$posting->location_text.'. '.($isOpen ? 'Apply now at Sewa Hospitality.' : 'This role is '.$posting->status->label().'. See similar openings.'))

@push('head')
    <link rel="canonical" href="{{ rtrim(config('app.url', 'https://sewahospitality.com'), '/') }}{{ $posting->publicPath() }}">
    <x-site.hreflang />
    <meta name="robots" content="{{ $isOpen ? 'index, follow, max-image-preview:large' : 'noindex, follow' }}">
@endpush

@section('content')
    <section data-theme="light" class="px-4 py-12 md:px-6 md:py-16">
        <div class="container mx-auto max-w-5xl">
            <a href="/careers" class="text-sm font-medium text-brand hover:underline">← All open roles</a>

            <div class="mt-6 grid gap-8 lg:grid-cols-5">
                <div class="lg:col-span-3">
                    <p class="eyebrow text-ink-muted">{{ $posting->department->label() }} · {{ $posting->location_text }}</p>
                    <h1 class="font-display mt-3 text-4xl md:text-5xl">{{ $posting->title }}</h1>

                    <div class="mt-4 flex flex-wrap gap-2 text-sm">
                        <span class="rounded-full border border-line px-3 py-1">{{ $posting->employment_type->label() }}</span>
                        <span class="rounded-full border border-line px-3 py-1">{{ $posting->experienceLabel() }}</span>
                        @if ($posting->closesLabel() && $isOpen)
                            <span class="rounded-full border border-line px-3 py-1">Apply by {{ $posting->closesLabel() }}</span>
                        @endif
                        @unless ($isOpen)
                            <span class="rounded-full border border-line bg-paper-3 px-3 py-1 font-semibold">
                                {{ $posting->status->label() === 'Paused' ? 'Applications paused' : 'Applications closed' }}
                            </span>
                        @endunless
                    </div>

                    @if ($posting->description_html)
                        <div class="prose mt-8 max-w-none">{!! $posting->description_html !!}</div>
                    @endif

                    @if ($posting->responsibilities_html)
                        <h2 class="font-display mt-8 text-2xl">Responsibilities</h2>
                        <div class="prose mt-3 max-w-none">{!! $posting->responsibilities_html !!}</div>
                    @endif

                    @if ($posting->skills_html)
                        <h2 class="font-display mt-8 text-2xl">Skills & qualifications</h2>
                        <div class="prose mt-3 max-w-none">{!! $posting->skills_html !!}</div>
                    @endif
                </div>

                <div class="lg:col-span-2">
                    @if ($isOpen)
                        <div class="lg:sticky lg:top-20">
                            <h2 class="font-display text-2xl">Apply for this role</h2>
                            <div class="mt-4">
                                <livewire:careers.application-form :posting="$posting" :key="'apply-'.$posting->getKey()" />
                            </div>
                        </div>
                    @else
                        <div class="rounded-2xl border border-line bg-paper-2 p-6">
                            <h2 class="font-display text-xl">
                                {{ $posting->status->label() === 'Paused' ? 'This role is paused' : 'This role has closed' }}
                            </h2>
                            <p class="mt-2 text-sm text-ink-soft">
                                We keep closed roles online for reference. Similar openings below —
                                or browse <a href="/careers" class="font-medium text-brand hover:underline">all open roles</a>.
                            </p>

                            @if ($similar->isNotEmpty())
                                <ul class="mt-4 flex flex-col gap-2">
                                    @foreach ($similar as $job)
                                        <li>
                                            <a href="{{ $job->publicPath() }}" class="flex min-h-[44px] items-center justify-between rounded-lg border border-line px-4 text-sm hover:bg-paper-3">
                                                <span class="font-medium">{{ $job->title }}</span>
                                                <span class="text-ink-muted">{{ $job->location_text }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
