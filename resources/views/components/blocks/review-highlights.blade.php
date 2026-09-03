{{-- D2 · Review Highlights (section-library §5) — live GBP rating +
    recent reviews with "as of" date. The GBP sync engine lands in M4
    (reviews:sync-gbp); until real synced data exists the block renders
    its honest zero-state — NEVER a self-declared AggregateRating
    (formula-reference defect ban). --}}
@props([
    'data' => [],
    'isLead' => false,
])

@php
    $data = is_array($data) ? $data : [];
    // M4: \App\Modules\Testimonials\Services\GbpStats::latest() supplies
    // rating + as_of + recent reviews. Guarded until then.
    $gbp = null;
    if (class_exists(\App\Modules\Testimonials\Services\GbpStats::class)) {
        $gbp = app(\App\Modules\Testimonials\Services\GbpStats::class)->latest();
    }
@endphp

<div {{ $attributes }}>
    <div data-theme="light" class="px-4 py-10 md:px-6 md:py-12">
        <div class="container mx-auto max-w-4xl">
            @if ($gbp !== null)
                {{-- Live GBP payload (M4) --}}
                <div class="rounded-2xl border border-line bg-paper-2 p-6 text-center">
                    <p class="font-display text-4xl">{{ $gbp['rating'] }}</p>
                    <p class="mt-1 text-sm text-ink-soft">Google rating · as of {{ $gbp['as_of'] }}</p>
                    <a href="/reviews" class="mt-3 inline-flex min-h-[44px] items-center text-sm font-semibold text-brand hover:underline">Read reviews →</a>
                </div>
            @else
                <div class="rounded-2xl border border-line bg-paper-2 p-6 text-center">
                    <p class="text-sm text-ink-soft">We display our live Google rating here, with its "as of" date — synced automatically. Want it now?</p>
                    <a href="/reviews" class="mt-2 inline-flex min-h-[44px] items-center text-sm font-semibold text-brand hover:underline">Where to find our reviews →</a>
                </div>
            @endif
        </div>
    </div>
</div>
