{{-- A8 · Spacer/Divider (§2): rhythm + ornament. --}}
@props(['data' => [], 'isLead' => false])
@php
    $data = is_array($data) ? $data : [];
    $py = match ($data['height'] ?? 'md') { 'sm' => 'py-6', 'lg' => 'py-16', default => 'py-10' };
@endphp
<section {{ $attributes }} data-theme="light" class="{{ $py }}">
    <div class="container mx-auto max-w-5xl px-4 md:px-6">
        @if (($data['ornament'] ?? 'rule') === 'rule') <hr class="border-line">
        @elseif (($data['ornament'] ?? '') === 'quote') <p class="text-center font-display text-3xl text-brand/50" aria-hidden="true">"</p>
        @endif
    </div>
</section>
