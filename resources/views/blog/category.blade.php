{{-- Category archive (07-blog-news §3): editable intro, cards,
     thin-archive noindex rule. --}}
@extends('layouts.app')

@section('title', $category->meta_title ?: $category->name.' — Sewa Hospitality')
@section('meta_description', $category->meta_description ?: str($category->description ?? '')->limit(160))

@push('head')
    <link rel="canonical" href="{{ rtrim(config('app.url', 'https://sewahospitality.com'), '/') }}{{ $category->publicPath() }}">
    <x-site.hreflang />
    <meta name="robots" content="{{ $thin ? 'noindex, follow' : 'index, follow, max-image-preview:large' }}">
@endpush

@section('content')
    <section data-theme="light" class="px-4 py-12 md:px-6 md:py-16">
        <div class="container mx-auto max-w-4xl">
            <p class="eyebrow text-ink-muted">Category</p>
            <h1 class="font-display mt-2 text-4xl">{{ $category->name }}</h1>
            @if ($category->description)
                <p class="mt-3 max-w-2xl text-ink-soft">{{ $category->description }}</p>
            @endif

            <div class="mt-10 flex flex-col gap-5">
                @forelse ($posts as $post)
                    <article class="rounded-2xl border border-line bg-paper-2 p-5">
                        <a href="{{ $post->publicPath() }}" class="font-display text-xl hover:text-brand">{{ $post->title }}</a>
                        <p class="mt-2 text-sm text-ink-soft">{{ str($post->excerpt)->limit(160) }}</p>
                        <p class="mt-2 text-xs text-ink-muted">{{ $post->author?->name }} · {{ $post->published_at?->format('d M Y') }} · {{ $post->reading_time }} min</p>
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-line p-8 text-center text-ink-soft">Nothing published here yet.</p>
                @endforelse
            </div>

            <div class="mt-6">{{ $posts->onEachSide(1)->links() }}</div>
        </div>
    </section>
@endsection
