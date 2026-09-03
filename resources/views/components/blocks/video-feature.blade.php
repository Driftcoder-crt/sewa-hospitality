{{-- C4 · Video Feature (section-library §4) — facade (poster + play):
    the YouTube iframe loads ON INTENT only (ux-interactions budget —
    zero third-party JS pre-consent rule). --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    $poster = ($data['poster_media_id'] ?? null) ? \App\Models\Media::query()->find($data['poster_media_id']) : null;
    $youtubeId = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($data['youtube_id'] ?? ''));
@endphp

<div {{ $attributes }}>
    <div data-theme="light" class="px-4 py-10 md:px-6 md:py-12">
        <div class="container mx-auto max-w-4xl">
            @if ($data['title'] ?? null)
                <h2 class="font-display text-2xl md:text-3xl">{{ $data['title'] }}</h2>
            @endif

            @if ($youtubeId !== '')
                <div class="mt-4" x-data="{ playing: false }">
                    <div class="aspect-video overflow-hidden rounded-2xl bg-paper-3">
                        <button type="button" x-show="! playing" @click="playing = true"
                                class="group relative flex h-full w-full items-center justify-center focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                                aria-label="Play video{{ isset($data['title']) ? ': '.$data['title'] : '' }}">
                            @if ($poster)
                                <x-media :media="$poster" conversion="hero" class="absolute inset-0 h-full w-full [&>img]:h-full [&>img]:w-full [&>img]:object-cover" />
                            @endif
                            <span class="relative inline-flex h-16 w-16 items-center justify-center rounded-full bg-paper/90 text-ink shadow-lg transition-transform group-hover:scale-105" aria-hidden="true">
                                <svg class="ms-1 h-7 w-7" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.14v13.72L19 12 8 5.14Z"/></svg>
                            </span>
                        </button>

                        <iframe x-show="playing" x-cloak
                                src="" :src="'https://www.youtube-nocookie.com/embed/{{ $youtubeId }}?autoplay=1&rel=0'"
                                class="h-full w-full" title="{{ $data['title'] ?? 'Video' }}"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen loading="lazy"></iframe>
                    </div>
                </div>
            @endif

            @if ($data['caption'] ?? null)
                <p class="mt-2 text-xs text-ink-muted">{{ $data['caption'] }}</p>
            @endif
        </div>
    </div>
</div>
