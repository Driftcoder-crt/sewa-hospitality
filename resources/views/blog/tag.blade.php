{{-- Tag archive (07-blog-news §3): per-post tags only; noindex when
     the archive carries a single post. --}}
@extends('layouts.app')

@section('title', '#'.$tag->name.' — Sewa Hospitality')
@section('meta_description', 'Posts tagged '.$tag->name.'.')

@push('head')
    <meta name="robots" content="{{ $thin ? 'noindex, follow' : 'index, follow' }}">
@endpush

@section('content')
    <section data-theme="light" class="px-4 py-12 md:px-6 md:py-16">
        <div class="container mx-auto max-w-4xl">
            <h1 class="font-display text-4xl">#{{ $tag->name }}</h1>

            <div class="mt-10 flex flex-col gap-5">
                @forelse ($posts as $post)
                    <article class="rounded-2xl border border-line bg-paper-2 p-5">
                        <a href="{{ $post->publicPath() }}" class="font-display text-xl hover:text-brand">{{ $post->title }}</a>
                        <p class="mt-2 text-sm text-ink-soft">{{ str($post->excerpt)->limit(160) }}</p>
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-line p-8 text-center text-ink-soft">Nothing tagged yet.</p>
                @endforelse
            </div>

            <div class="mt-6">{{ $posts->onEachSide(1)->links() }}</div>
        </div>
    </section>
@endsection
