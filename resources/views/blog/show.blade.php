{{-- Blog post page (07-blog-news §3): ONE H1, byline with author link
     (replaces "admin"), reading time, sanitized body, categories/tags,
     related 3, Article + Person JSON-LD (schema-matches-visible). --}}
@extends('layouts.app')

@section('title', $post->meta_title ?: $post->title.' — Sewa Hospitality')
@section('meta_description', $post->meta_description ?: str($post->excerpt)->limit(160))

@push('head')
    <link rel="canonical" href="{{ rtrim(config('app.url', 'https://sewahospitality.com'), '/') }}{{ $post->publicPath() }}">
    <x-site.hreflang :alternates="\App\Modules\I18n\Services\ContentVariants::alternatesFor($post)" />
    <link rel="alternate" type="application/rss+xml" title="Sewa Hospitality — Journal" href="{{ route('blog.feed') }}">
    <meta name="robots" content="{{ $post->noindex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large' }}">
    {{-- JSON-LD precomputed; the @json directive with a multi-line array
         literal breaks the chained Livewire/Blade compilers. --}}
    @php
        $articleSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'wordCount' => $post->word_count,
            'inLanguage' => $post->locale,
            'author' => [
                '@type' => 'Person',
                'name' => $post->author?->name,
                'jobTitle' => $post->author?->authorProfile?->title,
            ],
        ];
    @endphp
    <script type="application/ld+json">
        {!! json_encode($articleSchema, 15) !!}
    </script>
@endpush

@section('content')
    <article data-theme="light" class="px-4 py-12 md:px-6 md:py-16">
        <div class="container mx-auto max-w-3xl">
            <p class="eyebrow text-ink-muted">{{ $post->type->label() }}</p>
            <h1 class="font-display mt-3 text-4xl md:text-5xl">{{ $post->title }}</h1>

            <div class="mt-6 flex flex-wrap items-center gap-3 border-y border-line py-4 text-sm">
                @php
                    $authorUrl = $post->author?->authorProfile?->is_public
                        && filled($post->author->employee?->employee_code)
                        ? '/team/'.$post->author->employee->employee_code : '/blog';
                @endphp
                <a href="{{ $authorUrl }}" class="font-semibold text-brand hover:underline">
                    {{ $post->authorLabel() }}
                </a>
                <span class="text-ink-muted">{{ $post->published_at?->format('d M Y') }}</span>
                <span class="text-ink-muted">{{ $post->reading_time }} min read</span>
            </div>

            <div class="prose mt-8 max-w-none">{!! \App\Support\Cms\HtmlSanitizer::clean($post->body) !!}</div>

            <div class="mt-8 flex flex-wrap gap-2">
                @foreach ($post->categories as $category)
                    <a href="{{ $category->publicPath() }}" class="rounded-full bg-paper-3 px-3 py-1 text-sm">{{ $category->name }}</a>
                @endforeach
                @foreach ($post->tags as $tag)
                    <a href="{{ $tag->publicPath() }}" class="rounded-full border border-line px-3 py-1 text-sm">#{{ $tag->name }}</a>
                @endforeach
            </div>

            @if ($related->isNotEmpty())
                <h2 class="font-display mt-12 text-2xl">Keep reading</h2>
                <ul class="mt-4 flex flex-col gap-3">
                    @foreach ($related as $item)
                        <li class="rounded-xl border border-line bg-paper-2 p-4">
                            <a href="{{ $item->publicPath() }}" class="font-semibold hover:text-brand">{{ $item->title }}</a>
                            <p class="mt-1 text-sm text-ink-muted">{{ $item->published_at?->format('M Y') }} · {{ $item->reading_time }} min</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </article>
@endsection
