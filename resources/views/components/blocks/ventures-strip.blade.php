{{-- F9 · Ventures Strip (§7): on-domain service-line cross-links. --}}
@props(['data' => [], 'isLead' => false])
@php
    $data = is_array($data) ? $data : [];
    $lines = [
        ['label' => 'Employee mobility', 'url' => '/services/employee-mobility'],
        ['label' => 'Business mobility', 'url' => '/services/business-mobility'],
        ['label' => 'Corporate housing', 'url' => '/housing'],
        ['label' => 'City coverage', 'url' => '/cities'],
    ];
@endphp
<section {{ $attributes }} data-theme="light"><div class="px-4 py-12 md:px-6"><div class="container mx-auto max-w-5xl">
@if (($data['heading'] ?? '') !== '') <h2 class="font-display text-xl">{{ $data['heading'] }}</h2> @endif
<div class="mt-4 flex flex-wrap gap-3">
@foreach ($lines as $line)
    <a href="{{ $line['url'] }}" class="inline-flex min-h-[44px] items-center rounded-full border border-line px-5 text-sm font-medium hover:bg-paper-3">{{ $line['label'] }}</a>
@endforeach
</div>
</div></div></section>
