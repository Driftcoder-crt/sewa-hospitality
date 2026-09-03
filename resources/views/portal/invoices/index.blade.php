@extends('layouts.portal')

@section('title', 'Invoices — Sewa Hospitality Portal')

@section('content')
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-1">
            <p class="eyebrow text-ink-muted">Client portal</p>
            <h1 class="font-display text-3xl">Invoices</h1>
            <p class="text-sm text-ink-soft">Invoices for {{ app(\App\Modules\Portal\Services\PortalContext::class)->organization()->name }}.</p>
        </div>

        <section aria-label="Invoices" class="overflow-x-auto rounded-xl border border-line bg-paper-2">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-muted">
                        <th class="px-4 py-3 font-semibold">Number</th>
                        <th class="px-4 py-3 font-semibold">Move</th>
                        <th class="px-4 py-3 font-semibold">Due</th>
                        <th class="px-4 py-3 font-semibold">Total</th>
                        <th class="px-4 py-3 font-semibold">Balance</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-3 font-medium">{{ $invoice->number }}</td>
                            <td class="px-4 py-3 text-ink-soft">{{ $invoice->move?->reference ?? '—' }}</td>
                            <td class="px-4 py-3 text-ink-soft">{{ $invoice->due_at?->format('d M Y') ?? 'On receipt' }}</td>
                            <td class="px-4 py-3">{{ $invoice->formattedTotal() }}</td>
                            <td class="px-4 py-3 {{ $invoice->amountDue() > 0 && $invoice->isOverdue() ? 'font-semibold text-danger' : '' }}">
                                {{ $invoice->isVoid() ? '—' : $invoice->formattedDue() }}
                            </td>
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
                                <a href="{{ route('portal.invoices.show', $invoice) }}" class="text-sm font-medium text-brand hover:underline">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-ink-soft">No invoices yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{ $invoices->links() }}
    </div>
@endsection
