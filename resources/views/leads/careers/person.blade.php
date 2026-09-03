{{-- /team/{person} — public profile (06-hr §3): real names, real
     bios, tap-to-bio — the anti-"hover-only" pattern. --}}
@extends('layouts.app')

@section('title', $employee->full_name.' — Sewa Hospitality')
@section('meta_description', $employee->full_name.' — '.$employee->designation.' at Sewa Hospitality. Bio, credentials and languages.')

@push('head')
    <link rel="canonical" href="{{ rtrim(config('app.url', 'https://sewahospitality.com'), '/') }}{{ $employee->publicPath() }}">
    <x-site.hreflang />
    <meta name="robots" content="index, follow, max-image-preview:large">
@endpush

@section('content')
    <section data-theme="light" class="px-4 py-12 md:px-6 md:py-16">
        <div class="container mx-auto max-w-3xl">
            <div class="flex flex-col items-start gap-6 sm:flex-row">
                <div class="h-32 w-32 shrink-0 overflow-hidden rounded-2xl border border-line bg-paper-3">
                    @if ($employee->photo)
                        <x-media :media="$employee->photo" conversion="thumb" class="h-full w-full [&>img]:h-full [&>img]:w-full [&>img]:object-cover" eager />
                    @else
                        <div class="flex h-full w-full items-center justify-center font-display text-3xl text-ink-muted">
                            @php($initials = collect(explode(' ', $employee->full_name))->map(fn (string $part) => mb_substr($part, 0, 1))->implode(''))
                            {{ $initials }}
                        </div>
                    @endif
                </div>

                <div>
                    <p class="eyebrow text-ink-muted">{{ $employee->department->label() }}@if ($employee->officeCity) · {{ $employee->officeCity->name }} @endif</p>
                    <h1 class="font-display mt-2 text-3xl md:text-4xl">{{ $employee->full_name }}</h1>
                    <p class="mt-2 text-lg text-ink-soft">{{ $employee->designation }}</p>

                    @if ($employee->languages() !== [])
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($employee->languages() as $language)
                                <span class="rounded-full border border-line px-3 py-1 text-sm">{{ $language }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            @if ($employee->bio)
                <div class="prose mt-8 max-w-none">{{ $employee->bio }}</div>
            @endif

            @php($credentials = $employee->credentials ?? [])
            @if (isset($credentials['certifications']) && $credentials['certifications'] !== [])
                <h2 class="font-display mt-8 text-2xl">Credentials</h2>
                <ul class="mt-3 flex flex-col gap-2">
                    @foreach ($credentials['certifications'] as $credential)
                        <li class="flex items-start gap-2 text-ink-soft">
                            <svg class="mt-1 h-4 w-4 shrink-0 text-brand" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                            {{ $credential }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
@endsection
