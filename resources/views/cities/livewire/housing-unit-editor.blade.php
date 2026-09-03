<div class="admin-screen">
@section('title', 'Edit housing unit — Sewa Admin')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">{{ $unit->name }}</h1>
            <p class="eyebrow mt-1 text-ink-muted">Housing · {{ $unit->city?->name }} · {{ $unit->tier->label() }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="save" class="inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm font-semibold text-ink hover:bg-paper-3">Save</button>
            @if ($canVerify)
                <button type="button" wire:click="verify" class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink">
                    {{ $unit->isVerified() ? 'Re-verify today' : 'Set Sewa Verified' }}
                </button>
            @endif
        </div>
    </div>

    <div class="mt-3" aria-live="polite">
        @if ($autosaveState === 'error')
            <div class="flex items-center justify-between gap-3 rounded-xl border border-danger-500/40 bg-danger-500/10 p-4 text-sm">
                <span class="text-ink">{{ $autosaveError }}</span>
                <button type="button" wire:click="save" class="inline-flex min-h-[36px] items-center rounded-lg border border-line bg-paper px-3 text-xs font-semibold">Retry now</button>
            </div>
        @elseif ($autosaveState === 'dirty')
            <p class="rounded-xl border border-warning-500/40 bg-warning-500/10 p-3 text-sm text-ink">Unsaved changes — autosaving every 10 s…</p>
        @elseif ($autosaveState === 'saved')
            <p class="rounded-xl border border-success-500/40 bg-success-500/10 p-3 text-sm text-ink" role="status">All changes saved.</p>
        @endif
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <div class="rounded-xl border border-line bg-paper-2 p-4">
            <h2 class="font-display text-lg">Listing</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <label class="block text-sm sm:col-span-2"><span class="font-semibold text-ink-soft">Name</span>
                    <input type="text" wire:model.live.debounce.300ms="name" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink"></label>
                <label class="block text-sm"><span class="font-semibold text-ink-soft">City</span>
                    <select wire:model.live.debounce.300ms="city_id" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                        @foreach ($cities as $city)
                            <option value="{{ $city->getKey() }}">{{ $city->name }}</option>
                        @endforeach
                    </select></label>
                <label class="block text-sm"><span class="font-semibold text-ink-soft">Type</span>
                    <select wire:model.live.debounce.300ms="type" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select></label>
                <label class="block text-sm"><span class="font-semibold text-ink-soft">Locality</span>
                    <input type="text" wire:model.live.debounce.300ms="locality" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink"></label>
                <label class="block text-sm"><span class="font-semibold text-ink-soft">Area name</span>
                    <input type="text" wire:model.live.debounce.300ms="area" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink"></label>
                <label class="block text-sm"><span class="font-semibold text-ink-soft">Bedrooms</span>
                    <input type="number" min="0" max="9" wire:model.live.debounce.300ms="bedrooms" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink"></label>
                <label class="block text-sm"><span class="font-semibold text-ink-soft">Area (sq ft)</span>
                    <input type="number" min="0" wire:model.live.debounce.300ms="area_sqft" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink"></label>
                <label class="block text-sm"><span class="font-semibold text-ink-soft">Tier</span>
                    <select wire:model.live.debounce.300ms="tier" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                        @foreach ($tiers as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select></label>
                <label class="block text-sm"><span class="font-semibold text-ink-soft">From-rate (₹)</span>
                    <input type="number" min="0" wire:model.live.debounce.300ms="from_rate" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink"></label>
                <label class="block text-sm"><span class="font-semibold text-ink-soft">Rate unit</span>
                    <select wire:model.live.debounce.300ms="rate_unit" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink">
                        @foreach ($units as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select></label>
                <label class="block text-sm sm:col-span-2"><span class="font-semibold text-ink-soft">Managed by (vendor, optional)</span>
                    <input type="text" wire:model.live.debounce.300ms="managed_by" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm text-ink"></label>
                <label class="block text-sm sm:col-span-2"><span class="font-semibold text-ink-soft">Notes</span>
                    <textarea wire:model.live.debounce.300ms="notes" rows="3" class="mt-1 w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm text-ink"></textarea></label>
                <label class="flex min-h-[44px] items-center gap-2 text-sm sm:col-span-2">
                    <input type="checkbox" wire:model.live="published" class="h-4 w-4 rounded border-line"> <span class="font-semibold text-ink">Published (live on /housing)</span>
                </label>
            </div>
        </div>

        <div class="rounded-xl border border-line bg-paper-2 p-4">
            <h2 class="font-display text-lg">Amenities</h2>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($suggested as $suggestion)
                    <button type="button" wire:click="addAmenity('{{ $suggestion }}')"
                            class="inline-flex min-h-[36px] items-center rounded-full border border-dashed border-line px-3 text-xs font-semibold text-ink-soft hover:bg-paper-3">+ {{ $suggestion }}</button>
                @endforeach
            </div>
            <ul class="mt-3 flex flex-wrap gap-2">
                @foreach ($amenities as $i => $amenity)
                    <li class="flex items-center gap-1 rounded-full border border-line bg-paper px-3 py-1.5 text-xs text-ink-soft">
                        {{ $amenity }}
                        <button type="button" wire:click="removeAmenity({{ $i }})" class="text-ink-muted hover:text-ink" aria-label="Remove {{ $amenity }}">✕</button>
                    </li>
                @endforeach
            </ul>
            <p class="mt-3 text-xs text-ink-muted">Verification: {{ $unit->verified_at?->format('d M Y') ?? 'not verified — badge hidden' }}. The badge is a trust claim: admin+ only, dated, re-verified every 6 months.</p>
        </div>
    </div>
</div>
