<?php

namespace App\Modules\Billing\Livewire;

use App\Modules\Billing\Enums\PaymentMethod;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Services\InvoiceService;
use App\Modules\Billing\Services\PaymentRecorder;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Portal\Models\PortalMove;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Invoice detail + standalone creation (12 doc §4.2): send (queued
 * with the immutable PDF), payments derive partial/paid, void with
 * reason keeps the number, reminders state visible.
 */
#[Layout('layouts.admin')]
class InvoiceEditor extends Component
{
    public ?Invoice $invoice = null;

    /** Create-mode fields */
    public string $organizationId = '';

    public string $moveId = '';

    public string $dueDate = '';

    public string $notes = '';

    /** @var list<array{description: string, qty: string, rate: string, tax_class: string}> */
    public array $lines = [];

    /** Payment form */
    public string $paymentMethod = 'bank';

    public string $paymentAmount = '';

    public string $paymentDate = '';

    public string $paymentReference = '';

    public string $voidReason = '';

    public string $sendTo = '';

    public function mount(?Invoice $invoice = null): void
    {
        if ($invoice !== null && $invoice->exists) {
            $this->authorize('view', $invoice);
            $this->invoice = $invoice->loadMissing(['payments.recorder', 'organization', 'move']);
            $this->sendTo = (string) ($invoice->organization?->billing_address['email'] ?? $invoice->organization?->owner?->email ?? '');
        } else {
            $this->authorize('create', Invoice::class);
            $this->lines = [
                ['description' => '', 'qty' => '1', 'rate' => '', 'tax_class' => '18'],
            ];
        }

        $this->paymentDate = now()->format('Y-m-d');
        $this->dueDate = now()->addDays(15)->format('Y-m-d');
    }

    public function render(): View
    {
        if ($this->invoice !== null) {
            $this->invoice->refresh()->loadMissing(['payments.recorder', 'organization', 'move']);
        }

        return view('billing.livewire.invoice-editor', [
            'methods' => PaymentMethod::options(),
            'organizations' => Organization::query()->orderBy('name')->get(['id', 'name']),
            'moves' => PortalMove::query()->with('organization:id,name')->orderByDesc('created_at')->limit(50)->get(),
        ]);
    }

    public function addLine(): void
    {
        $this->lines[] = ['description' => '', 'qty' => '1', 'rate' => '', 'tax_class' => '18'];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    /** Create the standalone invoice (draft). */
    public function save(): void
    {
        $this->authorize('create', Invoice::class);

        $this->validate([
            'organizationId' => ['required', 'exists:organizations,id'],
            'moveId' => ['nullable', 'exists:portal_move_records,id'],
            'dueDate' => ['nullable', 'date'],
        ]);

        try {
            $this->invoice = app(InvoiceService::class)->issue([
                'organization_id' => $this->organizationId,
                'move_record_id' => $this->moveId ?: null,
                'due_at' => $this->dueDate ?: null,
                'notes' => $this->notes ?: null,
            ], $this->toPaiseLines($this->lines));
        } catch (\InvalidArgumentException $e) {
            $this->addError('lines', $e->getMessage());

            return;
        }

        $this->sendTo = (string) ($this->invoice->organization?->billing_address['email'] ?? $this->invoice->organization?->owner?->email ?? '');

        $this->dispatch('notify', tone: 'success', message: 'Invoice '.$this->invoice->number.' created as draft.');

        $this->redirectRoute('admin.invoices.edit', $this->invoice, navigate: true);
    }

    /** Send with the immutable PDF snapshot (12 doc §6: never 'sent' on PDF failure). */
    public function send(): void
    {
        $this->authorize('update', $this->invoice);

        $this->validate(['sendTo' => ['required', 'email']]);

        try {
            app(InvoiceService::class)->send($this->invoice, $this->sendTo);
        } catch (\Throwable $e) {
            $this->dispatch('notify', tone: 'error', message: 'Send failed before queueing: '.$e->getMessage());

            return;
        }

        ActivityLogger::log('admin', 'publish', $this->invoice, ['recipient' => $this->sendTo]);

        $this->dispatch('notify', tone: 'success', message: 'Invoice queued to '.$this->sendTo.' with the PDF attached.');
    }

    public function recordPayment(): void
    {
        $this->authorize('update', $this->invoice);

        $this->validate([
            'paymentMethod' => ['required', 'in:bank,upi,cheque,gateway'],
            'paymentAmount' => ['required', 'numeric', 'min:0.01'],
            'paymentDate' => ['required', 'date'],
            'paymentReference' => ['nullable', 'string', 'max:190'],
        ]);

        app(PaymentRecorder::class)->record($this->invoice, [
            'method' => $this->paymentMethod,
            'amount' => (int) round(((float) str_replace(',', '', $this->paymentAmount)) * 100),
            'paid_at' => $this->paymentDate,
            'reference' => $this->paymentReference ?: null,
        ]);

        $this->reset(['paymentAmount', 'paymentReference']);
        $this->paymentDate = now()->format('Y-m-d');

        $this->dispatch('notify', tone: 'success', message: 'Payment recorded — status derived automatically.');
    }

    public function void(): void
    {
        $this->authorize('update', $this->invoice);

        $this->validate(['voidReason' => ['required', 'string', 'min:5', 'max:300']]);

        try {
            app(InvoiceService::class)->void($this->invoice, $this->voidReason);
        } catch (ValidationException $e) {
            $this->dispatch('notify', tone: 'error', message: $e->errors()['invoice'][0] ?? $e->getMessage());

            return;
        }

        $this->voidReason = '';
        $this->dispatch('notify', tone: 'success', message: 'Invoice voided — the number is retired, never reused.');
    }

    public function downloadPdf(): StreamedResponse
    {
        $this->authorize('view', $this->invoice);

        app(InvoiceService::class)->renderPdf($this->invoice);
        ActivityLogger::log('admin', 'export', $this->invoice, ['action' => 'pdf_download']);

        return Storage::disk('local')->download(InvoiceService::snapshotPath($this->invoice), $this->invoice->number.'.pdf');
    }

    /** Editor (rupees) → stored shape (paise). */
    private function toPaiseLines(array $lines): array
    {
        return collect($lines)
            ->filter(fn (array $line) => trim((string) $line['description']) !== '')
            ->map(fn (array $line): array => [
                'description' => trim((string) $line['description']),
                'qty' => (int) $line['qty'],
                'rate' => (int) round(((float) str_replace(',', '', (string) $line['rate'])) * 100),
                'tax_class' => (int) $line['tax_class'],
            ])
            ->values()
            ->all();
    }
}
