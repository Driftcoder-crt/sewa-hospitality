<div class="admin-screen">
@section('title', 'Payments — Sewa Admin')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl text-ink">Payments</h1>
            <p class="eyebrow mt-1 text-ink-muted">Billing · reconciliation view — unmatched references surface, never auto-match</p>
        </div>
        <button type="button" wire:click="export" class="inline-flex min-h-[44px] items-center rounded-full border border-line px-5 text-sm font-semibold text-ink-soft hover:bg-paper-3">
            Export CSV
        </button>
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-line bg-paper-2 p-4">
            <p class="eyebrow text-ink-muted">Recorded (recent 200)</p>
            <p class="mt-1 font-display text-2xl">{{ \App\Modules\Billing\Services\TaxCalculator::money($total) }}</p>
        </div>
        <div class="rounded-xl border border-line bg-paper-2 p-4">
            <p class="eyebrow text-ink-muted">Unmatched references</p>
            <p class="mt-1 font-display text-2xl {{ $unmatched->isNotEmpty() ? 'text-danger' : '' }}">{{ $unmatched->count() }}</p>
        </div>
        <div class="rounded-xl border border-line bg-paper-2 p-4">
            <p class="eyebrow text-ink-muted">Rows</p>
            <p class="mt-1 font-display text-2xl">{{ $payments->count() }}</p>
        </div>
    </div>

    @if ($unmatched->isNotEmpty())
        <div class="mt-4 rounded-xl border border-danger/30 bg-danger/5 p-4 text-sm text-danger" role="alert">
            {{ $unmatched->count() }} payment{{ $unmatched->count() === 1 ? '' : 's' }} without a reference — reconcile by hand before they count toward trust.
        </div>
    @endif

    <div class="mt-4 overflow-x-auto rounded-xl border border-line bg-paper-2">
        <table class="w-full min-w-[720px] text-sm">
            <thead>
                <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-muted">
                    <th class="px-4 py-3 font-semibold">Paid at</th>
                    <th class="px-4 py-3 font-semibold">Invoice</th>
                    <th class="px-4 py-3 font-semibold">Organization</th>
                    <th class="px-4 py-3 font-semibold">Method</th>
                    <th class="px-4 py-3 text-right font-semibold">Amount</th>
                    <th class="px-4 py-3 font-semibold">Reference</th>
                    <th class="px-4 py-3 font-semibold">Recorded by</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr class="border-b border-line last:border-0">
                        <td class="px-4 py-3">{{ $payment->paid_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 font-medium">{{ $payment->invoice?->number ?? '—' }}</td>
                        <td class="px-4 py-3 text-ink-soft">{{ $payment->invoice?->organization?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-ink-soft">{{ $payment->method->label() }}</td>
                        <td class="px-4 py-3 text-right">{{ $payment->formattedAmount() }}</td>
                        <td class="px-4 py-3 {{ $payment->reference ? 'text-ink-soft' : 'font-semibold text-danger' }}">{{ $payment->reference ?? 'unmatched' }}</td>
                        <td class="px-4 py-3 text-ink-soft">{{ $payment->recorder?->name ?? 'system' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-ink-soft">No payments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
