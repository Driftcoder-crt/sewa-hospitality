<?php

namespace App\Modules\Billing\Commands;

use App\Modules\Billing\Services\InvoiceService;
use App\Modules\Billing\Services\QuoteService;
use Illuminate\Console\Command;

/**
 * Nightly billing hygiene (12-billing-finance §4.2/§5): open invoices
 * past due flip to `overdue`; sent quotes past `valid_until` flip to
 * `expired` (terminal). Reminders are a separate schedule command.
 */
class MarkOverdueInvoices extends Command
{
    protected $signature = 'billing:mark-overdue';

    protected $description = 'Flip past-due invoices to overdue and past-validity quotes to expired (terminal states).';

    public function handle(InvoiceService $invoices, QuoteService $quotes): int
    {
        $overdue = $invoices->markOverdue();
        $expired = $quotes->expireStale();

        $this->info("Overdue invoices marked: {$overdue}. Quotes expired: {$expired}.");

        return self::SUCCESS;
    }
}
