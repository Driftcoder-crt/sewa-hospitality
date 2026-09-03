{{-- F1 · Services Grid (§7): auto-composed from the catalog. --}}
@props(['data' => [], 'isLead' => false])
@php
    $data = is_array($data) ? $data : [];
    $limit = max(1, min(16, (int) ($data['limit'] ?? 9)));
    $family = (string) ($data['family'] ?? 'all');
    $query = \App\Modules\Services\Models\Service::query()->published()->with('children');
    if ($family !== 'all') { $query->where('family', $family); }
    $services = $query->whereNull('parent_id')->orderBy('sort')->take($limit)->get();
@endphp
<section {{ $attributes }} data-theme="light"><div class="px-4 py-12 md:px-6 md:py-16"><div class="container mx-auto">
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
@foreach ($services as $service)
    <a href="{{ $service->publicPath() }}" class="rounded-2xl border border-line bg-paper-2 p-5 transition-shadow hover:shadow-md">
        <h3 class="font-semibold group-hover:text-brand">{{ $service->name }}</h3>
        <p class="mt-2 text-sm text-ink-soft">{{ str($service->short_desc)->limit(110) }}</p>
    </a>
@endforeach
</div>
@if ($services->isEmpty())
    <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">Services publish through the catalog admin.</p>
@endif
</div></div></section>
