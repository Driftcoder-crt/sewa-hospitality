@extends('layouts.portal')

@section('title', 'Invoice '.$invoice->number.' — Sewa Hospitality Portal')

@section('content')
    <div class="mx-auto flex max-w-3xl flex-col gap-6">
        <div>
            <a href="{{ route('portal.invoices') }}" class="text-sm font-medium text-brand hover:underline">← All invoices</a>
            <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
                <h1 class="font-display text-3xl">{{ $invoice->number }}</h1>
                <a href="{{ route('portal.invoices.download', $invoice) }}"
                   class="inline-flex min-h-[44px] items-center rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:opacity-90">
                    Download PDF
                </a>
            </div>
        </div>

        <section class="grid gap-4 rounded-xl border border-line bg-paper-2 p-5 sm:grid-cols-3" aria-label="Invoice summary">
            <div>
                <p class="eyebrow text-ink-muted">Status</p>
                <p class="mt-1 font-medium">{{ $invoice->status->label() }}</p>
            </div>
            <div>
                <p class="eyebrow text-ink-muted">Due date</p>
                <p class="mt-1 font-medium">{{ $invoice->due_at?->format('d M Y') ?? 'On receipt' }}</p>
            </div>
            <div>
                <p class="eyebrow text-ink-muted">Balance due</p>
                <p class="mt-1 font-medium">{{ $invoice->isVoid() ? '—' : $invoice->formattedDue() }}</p>
            </div>
        </section>

        @if ($invoice->void_reason)
            <p class="rounded-xl border border-line bg-paper-3 p-4 text-sm text-ink-soft">
                This invoice was voided: {{ $invoice->void_reason }}
            </p>
        @endif

        <section aria-label="Line items" class="overflow-x-auto rounded-xl border border-line bg-paper-2">
            <table class="w-full min-w-[520px] text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-muted">
                        <th class="px-4 py-3 font-semibold">Description</th>
                        <th class="px-4 py-3 text-right font-semibold">Qty</th>
                        <th class="px-4 py-3 text-right font-semibold">Rate</th>
                        <th class="px-4 py-3 text-right font-semibold">GST</th>
                        <th class="px-4 py-3 text-right font-semibold">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->lines as $line)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-3">{{ $line['description'] }}</td>
                            <td class="px-4 py-3 text-right text-ink-soft">{{ $line['qty'] }}</td>
                            <td class="px-4 py-3 text-right text-ink-soft">{{ \App\Modules\Billing\Services\TaxCalculator::money((int) $line['rate']) }}</td>
                            <td class="px-4 py-3 text-right text-ink-soft">{{ $line['tax_class'] }}%</td>
                            <td class="px-4 py-3 text-right font-medium">{{ \App\Modules\Billing\Services\TaxCalculator::money((int) $line['amount']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-line text-ink-soft">
                        <td colspan="4" class="px-4 py-2 text-right">Subtotal</td>
                        <td class="px-4 py-2 text-right">{{ \App\Modules\Billing\Services\TaxCalculator::money($invoice->subtotal) }}</td>
                    </tr>
                    @foreach ($invoice->tax_breakdown ?? [] as $class => $amount)
                        <tr class="text-ink-soft">
                            <td colspan="4" class="px-4 py-2 text-right">GST {{ $class }}%</td>
                            <td class="px-4 py-2 text-right">{{ \App\Modules\Billing\Services\TaxCalculator::money((int) $amount) }}</td>
                        </tr>
                    @endforeach
                    <tr class="text-base font-semibold">
                        <td colspan="4" class="px-4 py-3 text-right">Total</td>
                        <td class="px-4 py-3 text-right">{{ $invoice->formattedTotal() }}</td>
                    </tr>
                </tfoot>
            </table>
        </section>

        @if ($invoice->payments->isNotEmpty())
            <section aria-label="Payments" class="rounded-xl border border-line bg-paper-2">
                <h2 class="border-b border-line p-4 font-display text-lg">Payments</h2>
                <ul role="list">
                    @foreach ($invoice->payments as $payment)
                        <li class="flex items-center justify-between border-b border-line p-4 last:border-0 text-sm">
                            <div>
                                <p class="font-medium">{{ $payment->formattedAmount() }} · {{ $payment->method->label() }}</p>
                                <p class="mt-0.5 text-xs text-ink-muted">
                                    {{ $payment->paid_at->format('d M Y') }}@if($payment->reference) · ref {{ $payment->reference }}@endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
@endsection
