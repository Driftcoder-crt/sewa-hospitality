<?php

namespace App\Modules\Portal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Services\InvoiceService;
use App\Modules\Portal\Services\TenantAccess;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class InvoicesController extends Controller
{
    public function __construct(private readonly TenantAccess $access) {}

    /**
     * Org invoices (12 doc §3) — manager + billing org roles only;
     * employees never see money surfaces.
     */
    public function index(): View
    {
        $this->authorizeBillingRole();

        $invoices = Invoice::query()
            ->forOrganization($this->access->context()->organization()->getKey())
            ->with(['payments', 'move'])
            ->latest()
            ->paginate(15);

        return view('portal.invoices.index', ['invoices' => $invoices]);
    }

    /** Detail: lines, tax breakdown, payments (12 doc §3). */
    public function show(string $invoice): View
    {
        $this->authorizeBillingRole();

        $invoice = Invoice::query()
            ->forOrganization($this->access->context()->organization()->getKey())
            ->with(['payments', 'organization', 'move'])
            ->findOrFail($invoice);

        return view('portal.invoices.show', ['invoice' => $invoice]);
    }

    /** Immutable PDF snapshot (generated at send) — signed + audit-logged. */
    public function download(string $invoice): Response
    {
        $this->authorizeBillingRole();

        $invoice = Invoice::query()
            ->forOrganization($this->access->context()->organization()->getKey())
            ->findOrFail($invoice);

        ActivityLogger::log('portal', 'export', $invoice, ['action' => 'invoice_pdf_download']);

        $path = InvoiceService::snapshotPath($invoice);

        abort_unless(Storage::disk('local')->exists($path), 404,
            'The invoice document is not available yet — please use the emailed copy.');

        return Storage::disk('local')->download($path, $invoice->number.'.pdf');
    }

    /** Manager + billing org roles only (12 doc §3 surface table). */
    private function authorizeBillingRole(): void
    {
        abort_unless(in_array($this->access->context()->role(), ['manager', 'billing'], true), 404);
    }
}
