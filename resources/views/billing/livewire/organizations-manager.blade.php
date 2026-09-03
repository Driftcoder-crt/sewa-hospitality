<div class="admin-screen">
@section('title', 'Organizations — Sewa Admin')

    <h1 class="font-display text-2xl text-ink">Organizations</h1>
    <p class="eyebrow mt-1 text-ink-muted">Billing · client companies & billing profiles</p>

    @if ($winRate !== null)
        <p class="mt-3 text-sm text-ink-soft">Quote win rate: <strong class="text-ink">{{ $winRate }}</strong></p>
    @endif

    <div class="mt-4 flex flex-col gap-3">
        @foreach ($organizations as $organization)
            <div class="rounded-xl border border-line bg-paper-2">
                <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <div>
                        <p class="font-medium">{{ $organization->name }}</p>
                        <p class="mt-0.5 text-xs text-ink-muted">
                            {{ $organization->gstin ? 'GSTIN '.$organization->gstin : 'GSTIN pending' }}
                            · {{ $organization->users_count }} member{{ $organization->users_count === 1 ? '' : 's' }}
                            · {{ $organization->invoices_count }} invoice{{ $organization->invoices_count === 1 ? '' : 's' }}
                            @if (($outstanding[$organization->id] ?? 0) > 0)
                                <span class="font-semibold text-danger">· outstanding {{ \App\Modules\Billing\Services\TaxCalculator::money((int) $outstanding[$organization->id]) }}</span>
                            @endif
                        </p>
                    </div>
                    <button type="button" wire:click="edit('{{ $organization->id }}')" class="min-h-[44px] rounded-full border border-line px-4 text-sm font-semibold text-ink-soft hover:bg-paper-3">
                        {{ $editingId === $organization->id ? 'Close' : 'Edit billing profile' }}
                    </button>
                </div>

                @if ($editingId === $organization->id)
                    <form wire:submit="save" class="grid gap-3 border-t border-line p-4 md:grid-cols-3">
                        <div>
                            <label class="text-xs font-semibold text-ink-muted" for="name-{{ $organization->id }}">Name</label>
                            <input id="name-{{ $organization->id }}" type="text" wire:model="name" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-ink-muted" for="gstin-{{ $organization->id }}">GSTIN</label>
                            <input id="gstin-{{ $organization->id }}" type="text" wire:model="gstin" placeholder="27AAACS1234F1Z5" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm uppercase focus:border-brand focus:outline-none">
                            @error('gstin') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-ink-muted" for="pan-{{ $organization->id }}">PAN</label>
                            <input id="pan-{{ $organization->id }}" type="text" wire:model="pan" placeholder="AAACS1234F" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm uppercase focus:border-brand focus:outline-none">
                            @error('pan') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-ink-muted" for="bemail-{{ $organization->id }}">Billing email</label>
                            <input id="bemail-{{ $organization->id }}" type="email" wire:model="email" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs font-semibold text-ink-muted" for="line1-{{ $organization->id }}">Address line</label>
                            <input id="line1-{{ $organization->id }}" type="text" wire:model="line1" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-ink-muted" for="city-{{ $organization->id }}">City</label>
                            <input id="city-{{ $organization->id }}" type="text" wire:model="city" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-ink-muted" for="state-{{ $organization->id }}">State</label>
                            <input id="state-{{ $organization->id }}" type="text" wire:model="state" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-ink-muted" for="postal-{{ $organization->id }}">PIN</label>
                            <input id="postal-{{ $organization->id }}" type="text" wire:model="postalCode" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        </div>
                        <div class="md:col-span-3 flex items-center justify-between">
                            <span class="text-xs text-ink-muted">The billing email receives invoice.issued + reminders.</span>
                            <button type="submit" class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink hover:opacity-90">Save</button>
                        </div>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
</div>
