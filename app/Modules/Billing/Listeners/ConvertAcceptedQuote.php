<?php

namespace App\Modules\Billing\Listeners;

use App\Modules\Billing\Events\QuoteAccepted;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Services\InvoiceService;
use App\Modules\Leads\Enums\LeadStatus;
use App\Modules\Leads\Models\Lead;
use App\Modules\Leads\Services\LeadStatusMachine;
use App\Support\Audit\ActivityLogger;
use App\Support\Queue\QueueHardenedListener;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * QuoteAccepted (12 doc §3/§7): the acceptance trail converts —
 *
 *  1. a DRAFT invoice is created from the accepted lines (the finance
 *     team reviews, fills the due date and sends — nothing emails the
 *     client automatically from here);
 *  2. the originating lead (if any) flips to `won` with the quote
 *     number as the deal reference — the M3 invariant "won requires a
 *     deal_reference" is finally satisfiable with real paper.
 *
 * DB-only work, synchronous — the web request from the token link pays
 * for it and the tests assert the rows directly.
 */
class ConvertAcceptedQuote implements ShouldQueue
{
    use QueueHardenedListener;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600];

    public function __construct(private readonly InvoiceService $invoices) {}

    public function handle(QuoteAccepted $event): void
    {
        $quote = $event->quote->refresh();

        // 1. Invoice draft from the accepted lines (idempotent-ish: a
        // quote can only be accepted once — status is terminal).
        $existing = Invoice::query()
            ->where('quote_id', $quote->getKey())
            ->exists();

        if (! $existing) {
            $invoice = $this->invoices->issue(
                ['organization_id' => $quote->organization_id, 'move_record_id' => $quote->move_record_id],
                null,
                $quote,
            );

            ActivityLogger::log('system', 'create', $invoice, [
                'trigger' => 'quote_accepted',
                'quote' => $quote->number,
            ]);
        }

        // 2. Lead → won with the quote number as deal_reference.
        $lead = $quote->lead;

        if ($lead !== null && ! in_array($lead->status->value, [LeadStatus::Won->value, LeadStatus::Lost->value], true)) {
            if (LeadStatusMachine::canTransition($lead->status, LeadStatus::Won)) {
                $enrichment = is_array($lead->enrichment) ? $lead->enrichment : [];
                $enrichment['deal_reference'] = $quote->number;

                $lead->forceFill([
                    'status' => LeadStatus::Won,
                    'enrichment' => $enrichment,
                ])->save();

                ActivityLogger::log('system', 'update', $lead, [
                    'status' => 'won',
                    'deal_reference' => $quote->number,
                ]);
            }
        }
    }
}
