<?php

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Events\InvoiceIssued;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\Quote;
use App\Support\Audit\ActivityLogger;
use App\Support\Cms\Identity;
use App\Support\Pdf\PdfBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Invoice lifecycle (12-billing-finance §4.2/§5): issue from quote or
 * standalone, branded PDF snapshot (immutable on the private disk —
 * the "sent PDF" can never drift), queued send, payments derive
 * partial/paid, void keeps its number (statutory hygiene), reminders
 * capped at 3.
 */
class InvoiceService
{
    public function __construct(
        private readonly SequentialNumbering $numbering,
        private readonly TaxCalculator $calculator,
    ) {}

    /** Private-disk snapshot location — deterministic from the ULID key. */
    public static function snapshotPath(Invoice $invoice): string
    {
        return 'pdfs/invoices/'.$invoice->getKey().'.pdf';
    }

    /** Issue standalone (lines given) or from a quote (lines copied). */
    public function issue(array $attributes, ?array $lines = null, ?Quote $fromQuote = null): Invoice
    {
        if ($fromQuote !== null) {
            $built = [
                'lines' => (array) $fromQuote->lines,
            ] + $this->calculator->totals((array) $fromQuote->lines);
        } else {
            $built = $this->calculator->build($lines ?? []);
        }

        return DB::transaction(function () use ($attributes, $built, $fromQuote): Invoice {
            $invoice = new Invoice([
                ...$attributes,
                'number' => $this->numbering->next('invoices'),
                'quote_id' => $fromQuote?->getKey(),
                'lines' => $built['lines'],
                'subtotal' => $built['subtotal'],
                'tax_breakdown' => $built['tax'],
                'total' => $built['total'],
                'status' => InvoiceStatus::Draft,
            ]);

            $invoice->save();

            if ($fromQuote !== null) {
                ActivityLogger::log('admin', 'create', $invoice, [
                    'number' => $invoice->number,
                    'from_quote' => $fromQuote->number,
                ]);
            } else {
                ActivityLogger::log('admin', 'create', $invoice, ['number' => $invoice->number]);
            }

            return $invoice;
        });
    }

    /**
     * Branded PDF (12 doc §4.2: GST fields, brand header, integer-paise
     * math). Generated fresh and stored once — later calls return the
     * stored bytes (immutability, 12 doc §5).
     */
    public function renderPdf(Invoice $invoice, bool $forceNew = false): string
    {
        $path = 'pdfs/invoices/'.$invoice->getKey().'.pdf';
        $disk = Storage::disk('local');

        if (! $forceNew && $disk->exists($path)) {
            return (string) $disk->get($path);
        }

        $pdf = $this->buildPdf($invoice);
        $disk->put($path, $pdf);

        return $pdf;
    }

    /**
     * Send: queue the invoice.issued email with the PDF attached; the
     * status only becomes `sent` once the queued send exists (12 doc
     * §6 — a failed PDF generation never marks sent).
     */
    public function send(Invoice $invoice, string $toEmail, ?string $toName = null): Invoice
    {
        if (! in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Sent], true)) {
            throw ValidationException::withMessages([
                'invoice' => 'Only draft invoices can be sent.',
            ]);
        }

        // Snapshot BEFORE dispatch — failure here throws and the state
        // stays draft (the queue retry ladder owns the rest).
        $this->renderPdf($invoice);

        $invoice->forceFill([
            'status' => InvoiceStatus::Sent,
            'sent_at' => now(),
        ])->save();

        InvoiceIssued::dispatch($invoice);

        ActivityLogger::log('admin', 'publish', $invoice, [
            'number' => $invoice->number,
            'recipient' => $toEmail,
        ]);

        return $invoice;
    }

    /** Void with a reason (audit); the number is never reused. */
    public function void(Invoice $invoice, string $reason): Invoice
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A void reason is required.']);
        }

        if ($invoice->isFullyPaid() && $invoice->amountPaid() > 0) {
            throw ValidationException::withMessages([
                'invoice' => 'A fully paid invoice cannot be voided — issue a credit note instead.',
            ]);
        }

        $invoice->forceFill([
            'status' => InvoiceStatus::Void,
            'void_reason' => mb_substr(trim($reason), 0, 300),
        ])->save();

        ActivityLogger::log('admin', 'update', $invoice, [
            'number' => $invoice->number,
            'action' => 'void',
            'reason' => $invoice->void_reason,
        ]);

        return $invoice;
    }

    /** Cron sweep: open + past-due → overdue (12 doc §4.2 automation). */
    public function markOverdue(): int
    {
        $invoices = Invoice::query()
            ->outstanding()
            ->whereNotNull('due_at')
            ->where('due_at', '<', now()->toDateString())
            ->get();

        $swept = 0;

        // One transaction for the whole sweep — a crash mid-run must not
        // leave half the batch transitioned (error lock #1: single
        // transaction writes).
        DB::transaction(function () use ($invoices, &$swept): void {
            foreach ($invoices as $invoice) {
                if ($invoice->status === InvoiceStatus::Overdue) {
                    continue; // already overdue — no redundant re-save
                }

                $invoice->forceFill(['status' => InvoiceStatus::Overdue])->save();

                ActivityLogger::log('system', 'update', $invoice, [
                    'number' => $invoice->number,
                    'status' => 'overdue',
                ]);
                $swept++;
            }
        });

        return $swept;
    }

    /**
     * The branded document. Layout: brand band → invoice meta → bill-to
     * (org GST fields) → line table → tax breakdown → totals → notes →
     * footer. All amounts printed via TaxCalculator::money (INR prefix —
     * core PDF fonts have no ₹ glyph).
     */
    private function buildPdf(Invoice $invoice): string
    {
        $invoice->load(['organization', 'move', 'payments']);
        $identity = Identity::current();

        $pdf = new PdfBuilder;
        $teal = [14, 124, 102];
        $ink = [26, 26, 26];
        $muted = [110, 110, 110];
        $stripe = [244, 248, 246];

        $left = 48.0;
        $right = PdfBuilder::WIDTH - 48.0;

        // Brand band
        $pdf->rect(0, 0, PdfBuilder::WIDTH, 96, $teal);
        $pdf->text($left, 30, strtoupper((string) ($identity['brand'] ?? 'Sewa Hospitality')), 20, true, [255, 255, 255]);
        $pdf->text($left, 60, (string) ($identity['slogan'] ?? 'Care, delivered.'), 10, false, [235, 245, 242]);
        $pdf->text($right, 34, 'TAX INVOICE', 14, true, [255, 255, 255], rightAlign: true);
        $pdf->text($right, 56, $invoice->number, 11, false, [235, 245, 242], rightAlign: true);

        $y = 128;

        // Bill-to block
        $pdf->text($left, $y, 'Billed to', 9, true, $muted);
        $pdf->text($left, $y + 18, (string) $invoice->organization?->name, 12, true, $ink);
        $billTo = $invoice->organization?->billing_address ?? [];
        if (is_array($billTo) && $billTo !== []) {
            $addressLine = collect([
                $billTo['line1'] ?? $billTo['street'] ?? null,
                $billTo['city'] ?? null,
                $billTo['state'] ?? null,
                $billTo['postal_code'] ?? $billTo['postalCode'] ?? null,
            ])->filter()->implode(', ');
            if ($addressLine !== '') {
                $pdf->text($left, $y + 34, $addressLine, 9, false, $muted);
            }
        }
        if ($invoice->organization?->gstin) {
            $pdf->text($left, $y + 50, 'GSTIN: '.$invoice->organization->gstin, 9, false, $muted);
        }
        if ($invoice->organization?->pan) {
            $pdf->text($left, $y + 64, 'PAN: '.$invoice->organization->pan, 9, false, $muted);
        }

        // Meta block (right)
        $pdf->text($right, $y, 'Invoice date', 9, true, $muted, rightAlign: true);
        $pdf->text($right, $y + 18, optional($invoice->sent_at ?? $invoice->created_at)->format('d M Y') ?? '-', 10, false, $ink, rightAlign: true);
        $pdf->text($right, $y + 40, 'Due date', 9, true, $muted, rightAlign: true);
        $pdf->text($right, $y + 58, optional($invoice->due_at)->format('d M Y') ?? '-', 10, false, $ink, rightAlign: true);

        if ($invoice->move !== null) {
            $pdf->text($right, $y + 80, 'Move', 9, true, $muted, rightAlign: true);
            $pdf->text($right, $y + 98, $invoice->move->reference, 10, false, $ink, rightAlign: true);
        }

        $y = 240;

        // Table header
        $pdf->rect($left, $y, $right - $left, 24, $stripe);
        $pdf->text($left + 8, $y + 6, 'Description', 9, true, $ink);
        $pdf->text($right - 250, $y + 6, 'Qty', 9, true, $ink);
        $pdf->text($right - 190, $y + 6, 'Rate', 9, true, $ink);
        $pdf->text($right - 110, $y + 6, 'GST', 9, true, $ink);
        $pdf->text($right - 8, $y + 6, 'Amount', 9, true, $ink, rightAlign: true);

        $y += 24;

        foreach ((array) $invoice->lines as $line) {
            if ($y > 640) {
                $pdf->addPage();
                $y = 64;
            }

            $pdf->text($left + 8, $y + 6, $this->truncate((string) $line['description'], 52), 9, false, $ink);
            $pdf->text($right - 250, $y + 6, (string) $line['qty'], 9, false, $ink);
            $pdf->text($right - 190, $y + 6, TaxCalculator::money((int) $line['rate']), 9, false, $ink);
            $pdf->text($right - 110, $y + 6, ((int) $line['tax_class']).'%', 9, false, $ink);
            $pdf->text($right - 8, $y + 6, TaxCalculator::money((int) $line['amount']), 9, false, $ink, rightAlign: true);

            $y += 22;

            $pdf->line($left, $y, $right, [225, 228, 227], 0.4);
        }

        // Tax breakdown + totals
        $y += 16;

        $pdf->text($right - 190, $y, 'Subtotal', 9, false, $muted);
        $pdf->text($right - 8, $y, TaxCalculator::money((int) $invoice->subtotal), 9, false, $ink, rightAlign: true);
        $y += 18;

        foreach ((array) $invoice->tax_breakdown as $class => $amount) {
            $pdf->text($right - 190, $y, "GST {$class}%", 9, false, $muted);
            $pdf->text($right - 8, $y, TaxCalculator::money((int) $amount), 9, false, $ink, rightAlign: true);
            $y += 18;
        }

        $pdf->rect($right - 200, $y - 4, 200, 26, $teal);
        $pdf->text($right - 192, $y + 2, 'TOTAL DUE', 10, true, [255, 255, 255]);
        $pdf->text($right - 8, $y + 2, TaxCalculator::money((int) $invoice->total), 11, true, [255, 255, 255], rightAlign: true);
        $y += 40;

        if ($invoice->payments->isNotEmpty()) {
            $pdf->text($right - 190, $y, 'Paid to date', 9, false, $muted);
            $pdf->text($right - 8, $y, TaxCalculator::money($invoice->amountPaid()), 9, false, $ink, rightAlign: true);
            $y += 18;

            $pdf->text($right - 190, $y, 'Balance due', 9, true, $ink);
            $pdf->text($right - 8, $y, TaxCalculator::money($invoice->amountDue()), 9, true, $ink, rightAlign: true);
            $y += 18;
        }

        if ((string) $invoice->notes !== '') {
            $pdf->text($left, max($y + 12, 700), 'Notes', 9, true, $muted);
            $pdf->text($left, max($y + 28, 716), $this->truncate($invoice->notes, 110), 9, false, $ink);
        }

        // Footer
        $pdf->line($left, 780, $right, [225, 228, 227], 0.5);
        $pdf->text($left, 792, $identity['address']['street'].', '.$identity['address']['city'].', '.$identity['address']['state'].' '.$identity['address']['postalCode'], 8, false, $muted);
        $pdf->text($left, 806, $identity['email'].' · '.$identity['telephone'], 8, false, $muted);
        $pdf->text($right, 806, 'Computer-generated invoice — valid without signature.', 8, false, $muted, rightAlign: true);

        // No trailing addPage() here — output() flushes the current ops
        // buffer into the page list itself; an explicit addPage() would
        // append an empty final page to every invoice.
        return $pdf->output();
    }

    private function truncate(string $text, int $chars): string
    {
        return mb_strlen($text) > $chars ? mb_substr($text, 0, $chars - 1).'…' : $text;
    }
}
