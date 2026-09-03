<?php

namespace App\Modules\Billing\Events;

use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\InvoicePayment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** PaymentRecorded → status transitions + thank-you note (12 doc §7). */
class PaymentRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly InvoicePayment $payment,
        public readonly Invoice $invoice,
    ) {}
}
