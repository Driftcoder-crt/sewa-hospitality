{{-- CSR story page (09 doc §3): citable, partner attribution, Article schema. --}}
@extends('layouts.app')

@section('title', $story->title.' — Sewa Hospitality')
@section('meta_description', str($story->title.' — impact story from the Sewa Hospitality community program.')->limit(160))

@push('head')
    <link rel="canonical" href="{{ rtrim(config('app.url', 'https://sewahospitality.com'), '/') }}{{ $story->publicPath() }}">
    <x-site.hreflang :alternates="\App\Modules\I18n\Services\ContentVariants::alternatesFor($story)" />
    {{-- JSON-LD precomputed; the @json directive with a multi-line array
         literal breaks the chained Livewire/Blade compilers. --}}
    @php
        $articleSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $story->title,
            'datePublished' => $story->published_at?->toIso8601String(),
        ];
    @endphp
    <script type="application/ld+json">
        {!! json_encode($articleSchema, 15) !!}
    </script>
@endpush

@section('content')
    <article data-theme="light" class="px-4 py-12 md:px-6 md:py-16">
        <div class="container mx-auto max-w-3xl">
            <a href="/csr" class="text-sm font-medium text-brand hover:underline">← CSR program</a>
            <h1 class="font-display mt-4 text-4xl">{{ $story->title }}</h1>
            <p class="mt-3 text-sm text-ink-muted">
                @if ($story->partner) with {{ $story->partner->name }} · @endif
                {{ $story->published_at?->format('d M Y') }}
            </p>
            <div class="prose mt-8 max-w-none">{!! \App\Support\Cms\HtmlSanitizer::clean($story->body) !!}</div>
        </div>
    </article>
@endsection
