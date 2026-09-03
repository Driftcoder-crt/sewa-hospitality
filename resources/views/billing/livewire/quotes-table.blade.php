<div class="admin-screen">
@section('title', 'Quotes — Sewa Admin')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">Quotes</h1>
            <p class="eyebrow mt-1 text-ink-muted">Billing · SEWA-Q-YYYY-#### under lock</p>
        </div>
        <a href="{{ route('admin.quotes.create') }}" wire:navigate
           class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">
            New quote
        </a>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-2">
        <input type="search" wire:model.live.debounce.400ms="q" placeholder="Number or organization…"
               class="min-h-[44px] w-64 rounded-full border border-line bg-paper-2 px-4 text-sm focus:border-brand focus:outline-none">
        <select wire:model.live="status" class="min-h-[44px] rounded-full border border-line bg-paper-2 px-3 text-sm focus:border-brand focus:outline-none">
            <option value="">All statuses</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-line bg-paper-2">
        <table class="w-full min-w-[720px] text-sm">
            <thead>
                <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-muted">
                    <th class="px-4 py-3 font-semibold">Number</th>
                    <th class="px-4 py-3 font-semibold">Organization</th>
                    <th class="px-4 py-3 font-semibold">Move</th>
                    <th class="px-4 py-3 text-right font-semibold">Total</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Valid until</th>
                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($quotes as $quote)
                    <tr class="border-b border-line last:border-0">
                        <td class="px-4 py-3 font-medium">{{ $quote->number }} <span class="text-xs text-ink-muted">v{{ $quote->version }}</span></td>
                        <td class="px-4 py-3 text-ink-soft">{{ $quote->organization?->name }}</td>
                        <td class="px-4 py-3 text-ink-soft">{{ $quote->move?->reference ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">{{ $quote->formattedTotal() }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-3 py-0.5 text-xs font-semibold {{ $quote->status->value === 'accepted' ? 'bg-brand/10 text-brand' : ($quote->status->value === 'rejected' || $quote->status->value === 'expired' ? 'bg-paper-3 text-ink-muted' : 'bg-warning/10 text-warning') }}">
                                {{ $quote->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-ink-soft">{{ $quote->valid_until?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.quotes.edit', $quote) }}" wire:navigate class="text-sm font-medium text-brand hover:underline">Edit</a>
                            @if ($quote->status->value === 'accepted')
                                <button type="button" wire:click="toInvoice('{{ $quote->id }}')" wire:loading.attr="disabled"
                                        class="ml-3 text-sm font-medium text-brand hover:underline">→ Invoice</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-ink-soft">No quotes yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $quotes->links() }}
</div>
