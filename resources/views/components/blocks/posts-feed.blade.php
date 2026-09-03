{{-- F5 · Posts Feed (§7): module-fed editorial cards. --}}
@props(['data' => [], 'isLead' => false])
@php
    $data = is_array($data) ? $data : [];
    $limit = max(1, min(9, (int) ($data['limit'] ?? 3)));
    $type = (string) ($data['type'] ?? 'all');
    $query = \App\Modules\Blog\Models\Post::query()->published()->with(['author', 'cover']);
    if (in_array($type, ['blog', 'news'], true)) { $query->type(\App\Modules\Blog\Enums\PostType::from($type)); }
    $posts = $query->orderByDesc('published_at')->take($limit)->get();
@endphp
<section {{ $attributes }} data-theme="light"><div class="px-4 py-12 md:px-6 md:py-16"><div class="container mx-auto max-w-5xl">
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
@foreach ($posts as $post)
    <a href="{{ $post->publicPath() }}" class="rounded-2xl border border-line bg-paper-2 p-5 hover:shadow-md">
        <h3 class="font-display text-lg">{{ $post->title }}</h3>
        <p class="mt-2 text-sm text-ink-soft">{{ str($post->excerpt)->limit(110) }}</p>
        <p class="mt-3 text-xs text-ink-muted">{{ $post->author?->name }} · {{ $post->reading_time }} min</p>
    </a>
@endforeach
</div>
@if ($posts->isEmpty())
    <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">Fresh stories land here once published.</p>
@endif
</div></div></section>
