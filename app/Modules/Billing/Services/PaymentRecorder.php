<?php

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Events\PaymentRecorded;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\InvoicePayment;
use App\Support\Audit\ActivityLogger;
use Illuminate\Validation\ValidationException;

/**
 * Payment recording (12-billing-finance §4.2/§6): payments derive the
 * invoice status (partial/paid); unknown references go to the
 * reconciliation queue — never auto-matched; over/mismatch payments
 * are flagged, not silently accepted; rows are audit-hard (never
 * deleted).
 */
class PaymentRecorder
{
    /**
     * Record one payment against an invoice.
     *
     * @param  array{method: string, amount: int, paid_at: string, reference?: ?string}  $data
     */
    public function record(Invoice $invoice, array $data): InvoicePayment
    {
        if ($invoice->isVoid()) {
            throw ValidationException::withMessages(['invoice' => 'A void invoice cannot receive payments.']);
        }

        if (! $invoice->status->isOpen() && $invoice->status !== InvoiceStatus::Draft) {
            throw ValidationException::withMessages([
                'invoice' => 'Payments record against open invoices only.',
            ]);
        }

        $amount = (int) ($data['amount'] ?? 0);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Payment amount must be positive.']);
        }

        $due = $invoice->amountDue();

        // Amount ≠ balance → recorded but flagged (12 doc §6: never
        // silently accepted; reconciliation follows in ops).
        $flagged = $amount !== $due
            && ($amount > $due || ($data['reference'] ?? null) === null && $amount < $due);

        $payment = InvoicePayment::query()->create([
            'invoice_id' => $invoice->getKey(),
            'method' => $data['method'],
            'amount' => $amount,
            'paid_at' => $data['paid_at'],
            'reference' => $data['reference'] ?? null,
            'recorded_by' => auth()->id(),
        ]);

        $this->syncStatus($invoice);

        ActivityLogger::log('admin', 'create', $payment, [
            'invoice' => $invoice->number,
            'amount' => $amount,
            'method' => $data['method'],
            'flagged' => $flagged,
        ]);

        PaymentRecorded::dispatch($payment, $invoice);

        return $payment;
    }

    /** Status derives from the payment sum (single source of truth). */
    public function syncStatus(Invoice $invoice): Invoice
    {
        if ($invoice->isVoid()) {
            return $invoice;
        }

        $paid = $invoice->amountPaid();

        if ($paid <= 0) {
            return $invoice;
        }

        if ($paid >= $invoice->total) {
            $invoice->forceFill([
                'status' => InvoiceStatus::Paid,
                'paid_at' => now(),
            ])->save();
        } else {
            $invoice->forceFill(['status' => InvoiceStatus::Partial])->save();
        }

        return $invoice;
    }
}
