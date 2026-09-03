<div class="admin-screen">
@section('title', 'Invoices — Sewa Admin')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">Invoices</h1>
            <p class="eyebrow mt-1 text-ink-muted">Billing · void keeps the number, payments derive status</p>
        </div>
        <a href="{{ route('admin.invoices.create') }}" wire:navigate
           class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">
            New invoice
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
        <table class="w-full min-w-[760px] text-sm">
            <thead>
                <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-muted">
                    <th class="px-4 py-3 font-semibold">Number</th>
                    <th class="px-4 py-3 font-semibold">Organization</th>
                    <th class="px-4 py-3 font-semibold">Due</th>
                    <th class="px-4 py-3 text-right font-semibold">Total</th>
                    <th class="px-4 py-3 text-right font-semibold">Balance</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3"><span class="sr-only">Open</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr class="border-b border-line last:border-0">
                        <td class="px-4 py-3 font-medium">{{ $invoice->number }}</td>
                        <td class="px-4 py-3 text-ink-soft">{{ $invoice->organization?->name }}</td>
                        <td class="px-4 py-3 text-ink-soft">{{ $invoice->due_at?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">{{ $invoice->formattedTotal() }}</td>
                        <td class="px-4 py-3 text-right">{{ $invoice->isVoid() ? '—' : $invoice->formattedDue() }}</td>
                        <td class="px-4 py-3">
                            @php($tone = match ($invoice->status->value) {
                                'paid' => 'bg-brand/10 text-brand',
                                'void' => 'bg-paper-3 text-ink-muted',
                                'overdue' => 'bg-danger/10 text-danger',
                                'partial', 'sent' => 'bg-warning/10 text-warning',
                                default => 'bg-paper-3 text-ink-soft',
                            })
                            <span class="rounded-full px-3 py-0.5 text-xs font-semibold {{ $tone }}">{{ $invoice->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.invoices.edit', $invoice) }}" wire:navigate class="text-sm font-medium text-brand hover:underline">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-ink-soft">No invoices yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $invoices->links() }}
</div>
