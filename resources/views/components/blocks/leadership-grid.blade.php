{{-- F8 · Leadership Grid (§7): is_public employees, tap-to-bio. --}}
@props(['data' => [], 'isLead' => false])
@php
    $data = is_array($data) ? $data : [];
    $people = \App\Modules\Careers\Models\Employee::query()->public()->with(['photo', 'officeCity'])->orderBy('sort')->take((int) ($data['limit'] ?? 8))->get();
@endphp
<section {{ $attributes }} data-theme="light"><div class="px-4 py-12 md:px-6 md:py-16"><div class="container mx-auto">
@if (($data['heading'] ?? '') !== '') <h2 class="font-display text-2xl md:text-3xl">{{ $data['heading'] }}</h2> @endif
<ul class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
@foreach ($people as $person)
    <li><a href="{{ $person->publicPath() }}" class="block overflow-hidden rounded-2xl border border-line bg-paper-2 hover:shadow-md">
        <div class="flex aspect-square items-center justify-center bg-paper-3 font-display text-3xl text-ink-muted">
            @php($initials = collect(explode(' ', $person->full_name))->map(fn (string $p) => mb_substr($p, 0, 1))->implode(''))
            @if ($person->photo) <x-media :media="$person->photo" conversion="card" class="h-full w-full [&>img]:h-full [&>img]:w-full [&>img]:object-cover" />
            @else {{ $initials }} @endif
        </div>
        <div class="p-3"><span class="block text-sm font-semibold">{{ $person->full_name }}</span><span class="block text-xs text-ink-muted">{{ $person->designation }}</span></div>
    </a></li>
@endforeach
</ul>
@if ($people->isEmpty())
    <p class="mt-6 rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">Leadership profiles are being prepared — real people, no placeholder headshots.</p>
@endif
</div></div></section>
