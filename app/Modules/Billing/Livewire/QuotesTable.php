<?php

namespace App\Modules\Billing\Livewire;

use App\Modules\Billing\Enums\QuoteStatus;
use App\Modules\Billing\Models\Quote;
use App\Modules\Billing\Services\InvoiceService;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Quotes admin (12-billing-finance §4.1): list + duplicate-to-invoice.
 * The builder lives on the editor screen.
 */
#[Layout('layouts.admin')]
class QuotesTable extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    #[Url]
    public string $q = '';

    public function render(): View
    {
        $this->authorize('viewAny', Quote::class);

        $quotes = Quote::query()
            ->with(['organization', 'move', 'creator'])
            ->when($this->status !== '', fn ($q2) => $q2->where('status', $this->status))
            ->when($this->q !== '', fn ($q2) => $q2->where(fn ($inner) => $inner
                ->where('number', 'like', '%'.$this->q.'%')
                ->orWhereHas('organization', fn ($o) => $o->where('name', 'like', '%'.$this->q.'%'))))
            ->latest()
            ->paginate(15);

        return view('billing.livewire.quotes-table', [
            'quotes' => $quotes,
            'statuses' => QuoteStatus::options(),
        ]);
    }

    /** Duplicate-to-invoice action (12 doc §4.1) — draft invoice copied from lines. */
    public function toInvoice(string $quoteId): void
    {
        $quote = Quote::query()->findOrFail($quoteId);
        $this->authorize('update', $quote);

        if ($quote->status !== QuoteStatus::Accepted) {
            $this->dispatch('notify', tone: 'error', message: 'Only ACCEPTED quotes convert to invoices — the acceptance trail is the paper.');

            return;
        }

        $invoice = app(InvoiceService::class)->issue(
            ['organization_id' => $quote->organization_id, 'move_record_id' => $quote->move_record_id],
            null,
            $quote,
        );

        ActivityLogger::log('admin', 'create', $invoice, ['from_quote' => $quote->number]);

        $this->redirectRoute('admin.invoices.edit', $invoice, navigate: true);
    }
}
