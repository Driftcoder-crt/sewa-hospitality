{{-- /blog (07-blog-news §3): hero + cards + sidebar (recent, categories,
     this-list's tags, newsletter box — never a sitewide tag cloud). --}}
@extends('layouts.app')

@section('title', 'Blog — Sewa Hospitality')
@section('meta_description', 'Relocation guides, immigration explainers, city notes and company news from the Sewa Hospitality team.')

@push('head')
    <link rel="canonical" href="{{ rtrim(config('app.url', 'https://sewahospitality.com'), '/') }}/blog">
    <x-site.hreflang />
    <link rel="alternate" type="application/rss+xml" title="Sewa Hospitality — Journal" href="{{ route('blog.feed') }}">
@endpush

@section('content')
    <section data-theme="light" class="px-4 py-12 md:px-6 md:py-16">
        <div class="container mx-auto max-w-6xl">
            <p class="eyebrow text-ink-muted">Editorial</p>
            <h1 class="font-display mt-2 text-4xl md:text-5xl">The Sewa notebook</h1>
            <p class="mt-3 max-w-2xl text-lg text-ink-soft">Practical, dated, honest writing about moving to India — by the people who do the moving.</p>

            <div class="mt-10 grid gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    @forelse ($posts as $post)
                        <article class="mb-6 rounded-2xl border border-line bg-paper-2 p-5">
                            <a href="{{ $post->publicPath() }}" class="group">
                                <h2 class="font-display text-2xl group-hover:text-brand">{{ $post->title }}</h2>
                            </a>
                            <p class="mt-2 text-sm text-ink-soft">{{ str($post->excerpt)->limit(180) }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-ink-muted">
                                <span class="font-medium text-ink-soft">{{ $post->author?->name }}</span>
                                <span>{{ $post->published_at?->format('d M Y') }}</span>
                                <span>{{ $post->reading_time }} min read</span>
                                @foreach ($post->tags as $tag)
                                    <a href="{{ $tag->publicPath() }}" class="rounded-full border border-line px-2 py-0.5">#{{ $tag->name }}</a>
                                @endforeach
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-line bg-paper-2 p-10 text-center text-ink-soft">
                            The first stories are being written — check back shortly.
                        </div>
                    @endforelse

                    <div>{{ $posts->onEachSide(1)->links() }}</div>
                </div>

                <aside class="flex flex-col gap-6">
                    <div class="rounded-2xl border border-line bg-paper-2 p-5">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Recent</h2>
                        <ul class="mt-3 flex flex-col gap-2 text-sm">
                            @foreach ($recent as $item)
                                <li><a href="{{ $item->publicPath() }}" class="hover:text-brand">{{ str($item->title)->limit(60) }}</a></li>
                            @endforeach
                        </ul>
                    </div>

                    @if ($categories->isNotEmpty())
                        <div class="rounded-2xl border border-line bg-paper-2 p-5">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Categories</h2>
                            <ul class="mt-3 flex flex-col gap-2 text-sm">
                                @foreach ($categories as $row)
                                    <li class="flex items-center justify-between">
                                        <a href="{{ $row['category']->publicPath() }}" class="hover:text-brand">{{ $row['category']->name }}</a>
                                        <span class="text-xs text-ink-muted">{{ $row['count'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="rounded-2xl border border-line bg-paper-2 p-5">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Newsletter</h2>
                        <div class="mt-3">
                            <livewire:leads.newsletter-signup />
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection
