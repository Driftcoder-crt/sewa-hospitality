{{-- <x-housing-card> — tier badge (Sewa Verified with date), from-rate,
     amenities chips, gallery thumb (ui-components doc). Honest rates
     with staleness badge per cities doc §6. --}}
@props(['unit'])

@php
    /** @var \App\Modules\Cities\Models\HousingUnit $unit */
    $verified = $unit->isVerified();
    $stale = $unit->isRateStale();
@endphp

<article class="flex min-h-[44px] flex-col rounded-2xl border border-line bg-paper p-6">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h3 class="font-display text-lg text-ink">{{ $unit->name }}</h3>
            <p class="mt-1 text-sm text-ink-soft">{{ $unit->locality ?: ($unit->area ?: $unit->city?->name) }}{{ $unit->city?->name ? ', '.$unit->city->name : '' }}</p>
        </div>
        <span class="inline-flex items-center rounded-full bg-brand/10 px-2.5 py-1 text-xs font-semibold text-ink">{{ $unit->tier->label() }}</span>
    </div>

    <div class="mt-3 flex flex-wrap gap-2 text-xs text-ink-muted">
        <span class="rounded-full bg-paper-2 px-2.5 py-1">{{ $unit->type->label() }}</span>
        <span class="rounded-full bg-paper-2 px-2.5 py-1">{{ $unit->bedrooms }} {{ str('bedroom')->plural($unit->bedrooms) }}</span>
        @if ($unit->area_sqft) <span class="rounded-full bg-paper-2 px-2.5 py-1">≈ {{ number_format($unit->area_sqft) }} sq ft</span> @endif
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-2">
        @if ($unit->rateLabel())
            <span class="font-display text-lg text-ink">{{ $unit->rateLabel() }}</span>
            @if ($stale)
                <span class="inline-flex items-center rounded-full bg-warning-500/15 px-2 py-0.5 text-xs font-semibold text-ink" title="Rate last updated more than 90 days ago">confirm current rate</span>
            @endif
        @else
            <span class="text-sm text-ink-soft">Rate on request</span>
        @endif
    </div>

    <p class="mt-1 text-xs text-ink-muted">Rates vary by season and term — request an exact quote.</p>

    @if ($verified)
        <p class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-ink">
            <x-icon name="shield-check" class="h-4 w-4 text-brand" /> Sewa Verified
            @if ($unit->verified_at) <span class="font-normal text-ink-muted">· checked {{ $unit->verified_at->format('M Y') }}</span> @endif
        </p>
    @endif

    <a href="{{ $unit->publicPath() }}" class="mt-4 inline-flex min-h-[44px] items-center gap-1 text-sm font-semibold text-brand hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
        View details <span aria-hidden="true">→</span>
    </a>
</article>
