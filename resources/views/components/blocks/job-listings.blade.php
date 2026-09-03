{{-- F7 · Job Listings (§7): module-fed open roles + apply CTAs. --}}
@props(['data' => [], 'isLead' => false])
@php
    $data = is_array($data) ? $data : [];
    $dept = (string) ($data['department'] ?? 'all');
    $jobs = \App\Modules\Careers\Models\JobPosting::query()->open()
        ->when($dept !== 'all', fn ($q) => $q->where('department', $dept))
        ->with('city')->orderBy('sort')->take(6)->get();
@endphp
<section {{ $attributes }} data-theme="light"><div class="px-4 py-12 md:px-6 md:py-16"><div class="container mx-auto max-w-4xl">
@if (($data['heading'] ?? '') !== '') <h2 class="font-display text-2xl md:text-3xl">{{ $data['heading'] }}</h2> @endif
<ul class="mt-6 flex flex-col divide-y divide-line rounded-2xl border border-line bg-paper-2">
@forelse ($jobs as $job)
    <li><a href="{{ $job->publicPath() }}" class="flex min-h-[64px] flex-col justify-center px-5 py-3 hover:bg-paper-3 sm:flex-row sm:items-center sm:justify-between">
        <span class="font-semibold">{{ $job->title }}</span>
        <span class="text-sm text-ink-muted">{{ $job->location_text }} · {{ $job->employment_type->label() }}</span>
    </a></li>
@empty
    <li class="px-5 py-8 text-center text-sm text-ink-muted">No open roles right now — <a href="/careers" class="text-brand hover:underline">check the careers page</a>.</li>
@endforelse
</ul>
</div></div></section>
