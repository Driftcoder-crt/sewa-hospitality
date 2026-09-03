{{-- F3 · Housing Grid (§7): module-fed verified units + honest zero-state. --}}
@props(['data' => [], 'isLead' => false])
@php
    $data = is_array($data) ? $data : [];
    $limit = max(1, min(12, (int) ($data['limit'] ?? 6)));
    $units = \App\Modules\Cities\Models\HousingUnit::query()->published()->with('city')
        ->when(($data['city_id'] ?? '') !== '', fn ($q) => $q->where('city_id', $data['city_id']))
        ->orderByDesc('verified_at')->take($limit)->get();
@endphp
<section {{ $attributes }} data-theme="light"><div class="px-4 py-12 md:px-6 md:py-16"><div class="container mx-auto">
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
@foreach ($units as $unit)
    <x-housing-card :unit="$unit" />
@endforeach
</div>
@if ($units->isEmpty())
    <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">Published inventory appears here — honest from-rates with as-of dating.</p>
@endif
</div></div></section>
