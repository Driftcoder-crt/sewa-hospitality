{{-- F4 · City Coverage Strip (§7): hub cities, "we operate in…". --}}
@props(['data' => [], 'isLead' => false])
@php
    $data = is_array($data) ? $data : [];
    $cities = \App\Modules\Cities\Models\City::query()->where('status', 'published')->where('is_hub', true)->orderBy('name')->take((int) ($data['limit'] ?? 7))->get();
@endphp
<section {{ $attributes }} data-theme="light"><div class="px-4 py-12 md:px-6 md:py-16"><div class="container mx-auto max-w-5xl">
@if (($data['heading'] ?? '') !== '') <h2 class="font-display text-2xl md:text-3xl">{{ $data['heading'] }}</h2> @endif
<div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
@foreach ($cities as $city)
    <a href="{{ $city->publicPath() }}" class="rounded-xl border border-line bg-paper-2 p-4 text-sm font-medium hover:bg-paper-3">{{ $city->name }}</a>
@endforeach
</div>
</div></div></section>
