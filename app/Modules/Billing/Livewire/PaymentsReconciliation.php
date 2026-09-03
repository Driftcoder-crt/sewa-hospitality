<?php

namespace App\Modules\Billing\Livewire;

use App\Modules\Billing\Models\InvoicePayment;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payments reconciliation (12 doc §4.3): all payment rows, unmatched
 * references surfaced (never auto-matched), CSV export (finance, audited).
 */
#[Layout('layouts.admin')]
class PaymentsReconciliation extends Component
{
    public function render(): View
    {
        $this->authorize('viewAny', InvoicePayment::class);

        $payments = InvoicePayment::query()
            ->with(['invoice.organization', 'recorder'])
            ->orderByDesc('paid_at')
            ->limit(200)
            ->get();

        $unmatched = $payments->filter(fn (InvoicePayment $payment) => $payment->reference === null);
        $total = $payments->sum('amount');

        return view('billing.livewire.payments-reconciliation', [
            'payments' => $payments,
            'unmatched' => $unmatched,
            'total' => $total,
        ]);
    }

    /** CSV export — audited (12 doc §4.3 + error lock #7). */
    public function export(): StreamedResponse
    {
        $this->authorize('viewAny', InvoicePayment::class);

        ActivityLogger::log('admin', 'export', null, ['action' => 'payments_csv']);

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['invoice', 'organization', 'method', 'amount_inr', 'paid_at', 'reference', 'recorded_by']);

            InvoicePayment::query()->with(['invoice.organization', 'recorder'])
                ->orderByDesc('paid_at')
                ->chunk(500, function ($rows) use ($out): void {
                    foreach ($rows as $payment) {
                        fputcsv($out, [
                            $payment->invoice?->number,
                            $payment->invoice?->organization?->name,
                            $payment->method->value,
                            number_format($payment->amount / 100, 2, '.', ''),
                            $payment->paid_at->format('Y-m-d'),
                            $payment->reference,
                            $payment->recorder?->email,
                        ]);
                    }
                });

            fclose($out);
        }, 'sewa-payments-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }
}
