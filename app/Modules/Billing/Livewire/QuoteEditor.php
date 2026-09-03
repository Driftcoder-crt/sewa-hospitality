<?php

namespace App\Modules\Billing\Livewire;

use App\Modules\Billing\Models\Quote;
use App\Modules\Billing\Services\QuoteService;
use App\Modules\Billing\Services\TaxCalculator;
use App\Modules\Leads\Models\Lead;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Portal\Models\PortalMove;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Quote builder (12 doc §4.1): org/move/lead links, line items with
 * tax classes (server-side totals — client numbers never trusted),
 * validity window, send with token, edit-after-send versioning.
 */
#[Layout('layouts.admin')]
class QuoteEditor extends Component
{
    public ?Quote $quote = null;

    public string $organizationId = '';

    public string $moveId = '';

    public string $leadId = '';

    public string $validUntil = '';

    public string $notes = '';

    /** @var list<array{description: string, qty: string, rate: string, tax_class: string}> */
    public array $lines = [];

    public function mount(?Quote $quote = null): void
    {
        $this->authorize('viewAny', Quote::class);

        if ($quote !== null && $quote->exists) {
            $this->authorize('view', $quote);
            $this->quote = $quote;
            $this->organizationId = (string) $quote->organization_id;
            $this->moveId = (string) ($quote->move_record_id ?? '');
            $this->leadId = (string) ($quote->lead_id ?? '');
            $this->validUntil = $quote->valid_until?->format('Y-m-d') ?? '';
            $this->notes = (string) $quote->notes;

            $this->lines = collect($quote->lines)
                ->map(fn (array $line): array => [
                    'description' => (string) $line['description'],
                    // Paise → rupees for the editor; conversion back is server-side.
                    'qty' => (string) $line['qty'],
                    'rate' => number_format(((int) $line['rate']) / 100, 2, '.', ''),
                    'tax_class' => (string) $line['tax_class'],
                ])
                ->all();
        } else {
            $this->lines = [
                ['description' => '', 'qty' => '1', 'rate' => '', 'tax_class' => '18'],
            ];
        }
    }

    public function render(): View
    {
        $preview = null;

        if ($this->lines !== [] && collect($this->lines)->every(fn (array $line) => is_numeric($line['rate']) && is_numeric($line['qty']))) {
            try {
                $paiseLines = $this->toPaiseLines($this->lines);
                $preview = app(TaxCalculator::class)->totals($paiseLines);
            } catch (\InvalidArgumentException) {
                $preview = null;
            }
        }

        return view('billing.livewire.quote-editor', [
            'organizations' => Organization::query()->orderBy('name')->get(['id', 'name']),
            'moves' => PortalMove::query()->with('organization:id,name')->orderByDesc('created_at')->limit(50)->get(),
            'leads' => Lead::query()->where('status', 'qualified')->orWhere('status', 'proposal')->orderByDesc('created_at')->limit(50)->get(['id', 'name', 'email']),
            'taxClasses' => TaxCalculator::CLASSES,
            'preview' => $preview,
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

    public function save(): void
    {
        if ($this->quote === null) {
            $this->authorize('create', Quote::class);
        } else {
            $this->authorize('update', $this->quote);
        }

        $this->validate([
            'organizationId' => ['required', 'exists:organizations,id'],
            'moveId' => ['nullable', 'exists:portal_move_records,id'],
            'validUntil' => ['nullable', 'date', 'after:today'],
            'lines' => ['required', 'array', 'min:1'],
        ]);

        $attributes = [
            'organization_id' => $this->organizationId,
            'move_record_id' => $this->moveId ?: null,
            'lead_id' => $this->leadId ?: null,
            'valid_until' => $this->validUntil ?: null,
            'notes' => $this->notes ?: null,
            'created_by' => auth()->id(),
        ];

        try {
            if ($this->quote === null) {
                $this->quote = app(QuoteService::class)->createDraft($attributes, $this->toPaiseLines($this->lines));
            } else {
                $this->quote = app(QuoteService::class)->editLines(
                    $this->quote,
                    $this->toPaiseLines($this->lines),
                    $this->validUntil ?: null,
                    $this->notes ?: null,
                );
            }
        } catch (\InvalidArgumentException $e) {
            $this->addError('lines', $e->getMessage());

            return;
        }

        ActivityLogger::log('admin', 'update', $this->quote, ['version' => $this->quote->version]);

        $this->dispatch('notify', tone: 'success', message: 'Quote saved — version '.$this->quote->version.'.');

        $this->redirectRoute('admin.quotes.edit', $this->quote, navigate: true);
    }

    /** Send: status draft → sent + token minted; the email rides the listener. */
    public function send(): void
    {
        $this->authorize('update', $this->quote);

        try {
            app(QuoteService::class)->send($this->quote);
        } catch (ValidationException $e) {
            foreach ($e->errors()['quote'] ?? [] as $message) {
                $this->addError('lines', $message);
            }

            return;
        }

        ActivityLogger::log('admin', 'publish', $this->quote, ['number' => $this->quote->number]);

        $this->dispatch('notify', tone: 'success', message: 'Quote sent — acceptance link emailed to the organization contact.');
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
