{{-- F6 · Category Cloud (§7): category cards with live counts. --}}
@props(['data' => [], 'isLead' => false])
@php
    $data = is_array($data) ? $data : [];
    $categories = \App\Modules\Blog\Models\Category::query()->orderBy('sort')->get()
        ->map(fn ($c) => ['c' => $c, 'count' => $c->publishedCount()])->filter(fn ($r) => $r['count'] > 0);
@endphp
<section {{ $attributes }} data-theme="light"><div class="px-4 py-12 md:px-6 md:py-16"><div class="container mx-auto max-w-4xl">
@if (($data['heading'] ?? '') !== '') <h2 class="font-display text-2xl md:text-3xl">{{ $data['heading'] }}</h2> @endif
<div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
@foreach ($categories as $row)
    <a href="{{ $row['c']->publicPath() }}" class="flex items-center justify-between rounded-xl border border-line bg-paper-2 p-4 text-sm hover:bg-paper-3">
        <span class="font-medium">{{ $row['c']->name }}</span>
        <span class="text-xs text-ink-muted">{{ $row['count'] }}</span>
    </a>
@endforeach
</div>
</div></div></section>
