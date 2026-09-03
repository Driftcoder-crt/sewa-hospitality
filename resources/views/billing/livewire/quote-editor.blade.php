<div class="admin-screen">
@section('title', ($quote?->number ?? 'New quote').' — Sewa Admin')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <a href="{{ route('admin.quotes') }}" wire:navigate class="text-sm text-brand hover:underline">← All quotes</a>
            <h1 class="mt-1 font-display text-2xl text-ink">{{ $quote?->number ?? 'New quote' }}
                @if ($quote && $quote->version > 1) <span class="text-sm font-normal text-ink-muted">v{{ $quote->version }} (edited after send)</span> @endif
            </h1>
            @if ($quote)
                <p class="eyebrow mt-1 text-ink-muted">{{ $quote->organization?->name }} · {{ $quote->status->label() }}
                    @if ($quote->sent_at) · sent {{ $quote->sent_at->format('d M Y') }} @endif</p>
            @endif
        </div>
        @if ($quote && $quote->status->value === 'draft')
            <button type="button" wire:click="send" wire:confirm="Send this quote? The acceptance token is minted and the email queues."
                    class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">
                Send quote
            </button>
        @endif
    </div>

    <form wire:submit="save" class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 flex flex-col gap-4 rounded-xl border border-line bg-paper-2 p-5">
            <h2 class="font-display text-lg">Details</h2>
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="text-xs font-semibold text-ink-muted" for="organizationId">Organization</label>
                    <select id="organizationId" wire:model="organizationId" @if($quote) disabled @endif required
                            class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        <option value="">Choose…</option>
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                        @endforeach
                    </select>
                    @error('organizationId') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-ink-muted" for="moveId">Move record</label>
                    <select id="moveId" wire:model="moveId" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        <option value="">None</option>
                        @foreach ($moves as $move)
                            <option value="{{ $move->id }}">{{ $move->reference }} — {{ $move->organization?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-ink-muted" for="leadId">Originating lead</label>
                    <select id="leadId" wire:model="leadId" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        <option value="">None</option>
                        @foreach ($leads as $lead)
                            <option value="{{ $lead->id }}">{{ $lead->name }} ({{ $lead->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-ink-muted" for="validUntil">Valid until</label>
                    <input id="validUntil" type="date" wire:model="validUntil"
                           class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                </div>
            </div>

            <h2 class="mt-2 font-display text-lg">Line items</h2>
            <p class="text-xs text-ink-muted">Rates in rupees — stored and computed as integer paise, GST per line (0 / 5 / 18 / 28).</p>
            <div class="flex flex-col gap-3">
                @foreach ($lines as $index => $line)
                    <div class="grid gap-2 rounded-lg border border-line p-3 md:grid-cols-12" wire:key="line-{{ $index }}">
                        <div class="md:col-span-5">
                            <label class="sr-only" for="desc-{{ $index }}">Description</label>
                            <input id="desc-{{ $index }}" type="text" wire:model="lines.{{ $index }}.description" placeholder="Description"
                                   class="w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="sr-only" for="qty-{{ $index }}">Qty</label>
                            <input id="qty-{{ $index }}" type="number" min="1" wire:model="lines.{{ $index }}.qty" placeholder="Qty"
                                   class="w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="sr-only" for="rate-{{ $index }}">Rate (₹)</label>
                            <input id="rate-{{ $index }}" type="number" step="0.01" min="0" wire:model="lines.{{ $index }}.rate" placeholder="Rate ₹"
                                   class="w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="sr-only" for="tax-{{ $index }}">GST class</label>
                            <select id="tax-{{ $index }}" wire:model="lines.{{ $index }}.tax_class"
                                    class="w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                                @foreach ($taxClasses as $class)
                                    <option value="{{ $class }}">{{ $class }}%</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-1 flex items-end">
                            <button type="button" wire:click="removeLine({{ $index }})" wire:key="rm-{{ $index }}"
                                    class="min-h-[44px] w-full rounded-lg text-sm font-medium text-ink-muted hover:text-danger">✕</button>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex items-center justify-between">
                <button type="button" wire:click="addLine" class="min-h-[44px] rounded-full border border-line px-5 text-sm font-semibold text-ink-soft hover:bg-paper-3">+ Add line</button>
                <div class="text-right">
                    @if ($preview)
                        <p class="text-xs text-ink-muted">Subtotal {{ \App\Modules\Billing\Services\TaxCalculator::money($preview['subtotal']) }} · GST {{ \App\Modules\Billing\Services\TaxCalculator::money(array_sum($preview['tax'])) }}</p>
                        <p class="font-display text-xl">Total {{ \App\Modules\Billing\Services\TaxCalculator::money($preview['total']) }}</p>
                    @else
                        <p class="text-xs text-ink-muted">Enter rates to see live totals</p>
                    @endif
                </div>
            </div>
            @error('lines') <p class="text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        </div>

        <aside class="flex flex-col gap-4">
            <div class="rounded-xl border border-line bg-paper-2 p-5">
                <h2 class="font-display text-lg">Notes</h2>
                <textarea wire:model="notes" rows="5" placeholder="Payment terms, inclusions, anything the client should know…"
                          class="mt-2 w-full rounded-lg border border-line bg-paper px-3 py-2 text-sm focus:border-brand focus:outline-none"></textarea>
            </div>
            <button type="submit" class="inline-flex min-h-[44px] items-center justify-center rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink hover:opacity-90">
                {{ $quote ? 'Save (bumps version after send)' : 'Create draft' }}
            </button>
            @if ($quote && $quote->token)
                <div class="rounded-xl border border-dashed border-line p-4 text-xs text-ink-muted">
                    <p class="font-semibold text-ink-soft">Acceptance link</p>
                    <p class="mt-1 break-all">{{ route('portal.quotes.accept', ['quote' => $quote->getKey(), 'token' => $quote->token]) }}</p>
                    <p class="mt-2">Single-use, expires with the validity window.</p>
                </div>
            @endif
        </aside>
    </form>
</div>
