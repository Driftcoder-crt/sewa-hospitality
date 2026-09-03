{{-- F2 · Service Detail Accordion (§7): scope accordions from the tree. --}}
@props(['data' => [], 'isLead' => false])
@php
    $data = is_array($data) ? $data : [];
    $services = \App\Modules\Services\Models\Service::query()->published()->with('children')->whereNull('parent_id')->orderBy('sort')->take(9)->get();
@endphp
<section {{ $attributes }} data-theme="light"><div class="px-4 py-12 md:px-6 md:py-16"><div class="container mx-auto max-w-3xl">
@if (($data['heading'] ?? '') !== '') <h2 class="font-display text-2xl md:text-3xl">{{ $data['heading'] }}</h2> @endif
<div class="mt-6 flex flex-col gap-3">
@foreach ($services as $service)
    <details class="rounded-xl border border-line bg-paper-2 p-4">
        <summary class="cursor-pointer font-semibold">{{ $service->name }}</summary>
        <p class="mt-2 text-sm text-ink-soft">{{ str($service->short_desc)->limit(200) }}</p>
        @if ($service->children->isNotEmpty())
            <ul class="mt-2 list-inside list-disc text-sm text-ink-soft">
                @foreach ($service->children as $child) <li>{{ $child->name }}</li> @endforeach
            </ul>
        @endif
    </details>
@endforeach
</div>
</div></div></section>
