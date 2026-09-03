<?php

namespace App\Modules\Billing\Livewire;

use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Models\Invoice;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Invoices admin (12 doc §4.2): list with status chips + outstanding
 * visibility. Detail/actions on the editor screen.
 */
#[Layout('layouts.admin')]
class InvoicesTable extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    #[Url]
    public string $q = '';

    public function render(): View
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::query()
            ->with(['organization', 'move', 'payments'])
            ->when($this->status !== '', fn ($q2) => $q2->where('status', $this->status))
            ->when($this->q !== '', fn ($q2) => $q2->where(fn ($inner) => $inner
                ->where('number', 'like', '%'.$this->q.'%')
                ->orWhereHas('organization', fn ($o) => $o->where('name', 'like', '%'.$this->q.'%'))))
            ->latest()
            ->paginate(15);

        return view('billing.livewire.invoices-table', [
            'invoices' => $invoices,
            'statuses' => InvoiceStatus::options(),
        ]);
    }
}
