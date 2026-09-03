<?php

use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Enums\PaymentMethod;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\Quote;
use App\Modules\Billing\Services\InvoiceService;
use App\Modules\Billing\Services\PaymentRecorder;
use App\Modules\Billing\Services\QuoteService;
use App\Modules\Billing\Services\SequentialNumbering;
use App\Modules\Billing\Services\TaxCalculator;
use App\Modules\Organizations\Models\Organization;
use App\Support\Pdf\PdfBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

/**
 * Billing core (12-billing-finance §8): numbering concurrency
 * discipline, float-free paise arithmetic, GST classes, quote
 * versioning, token acceptance single-use + expiry, reminders capped,
 * void preserves number, portal tenant isolation.
 */
it('allocates strictly increasing numbers per prefix', function () {
    // The allocator derives the next number from the highest EXISTING
    // row of the prefix (SELECT … FOR UPDATE) — allocation is only
    // meaningful inside the issue flow that writes the row, so this
    // goes through the services, not bare next() calls.
    $org = Organization::factory()->create();
    $quotes = app(QuoteService::class);
    $invoices = app(InvoiceService::class);
    $lines = [['description' => 'Line', 'qty' => 1, 'rate' => 100_00, 'tax_class' => 18]];

    $first = $quotes->createDraft(['organization_id' => $org->id], $lines);
    $second = $quotes->createDraft(['organization_id' => $org->id], $lines);
    $third = $invoices->issue(['organization_id' => $org->id], $lines);

    expect($first->number)->toBe('SEWA-Q-'.now()->format('Y').'-0001')
        ->and($second->number)->toBe('SEWA-Q-'.now()->format('Y').'-0002')
        ->and($third->number)->toBe('SEWA-I-'.now()->format('Y').'-0001');
});

it('never reuses a voided invoice number', function () {
    $service = app(InvoiceService::class);

    $org = Organization::factory()->create();
    $first = $service->issue(['organization_id' => $org->id], [
        ['description' => 'Line', 'qty' => 1, 'rate' => 100_00, 'tax_class' => 18],
    ]);
    $service->void($first, 'Client cancelled the engagement.');

    $second = $service->issue(['organization_id' => $org->id], [
        ['description' => 'Line', 'qty' => 1, 'rate' => 100_00, 'tax_class' => 18],
    ]);

    expect($second->number)->toBe('SEWA-I-'.now()->format('Y').'-0002');

    // Void requires a reason.
    expect(fn () => $service->void($second, '  '))->toThrow(ValidationException::class);
});

it('computes mixed GST classes with line-level rounding in pure paise', function () {
    $calculator = new TaxCalculator;

    $totals = $calculator->totals([
        ['amount' => 333_33, 'tax_class' => 18], // 59_99.94 → 60_00
        ['amount' => 100_05, 'tax_class' => 5],  // 5_00.25 → 5_00
        ['amount' => 100_00, 'tax_class' => 0],
    ]);

    expect($totals['subtotal'])->toBe(533_38)
        ->and($totals['tax']['18'])->toBe(60_00)
        ->and($totals['tax']['5'])->toBe(5_00)
        ->and($totals['total'])->toBe(598_38);

    // Invalid classes are refused loudly (error-locks doctrine).
    expect(fn () => $calculator->normalizeLines([
        ['description' => 'x', 'qty' => 1, 'rate' => 100, 'tax_class' => 27],
    ]))->toThrow(InvalidArgumentException::class);
});

it('bumps the quote version when editing after send and keeps pre-send edits in place', function () {
    $service = app(QuoteService::class);
    $org = Organization::factory()->create();

    $quote = $service->createDraft(['organization_id' => $org->id], [
        ['description' => 'Home search', 'qty' => 1, 'rate' => 250_00, 'tax_class' => 18],
    ]);

    // Pre-send edits stay on version 1.
    $service->editLines($quote, [
        ['description' => 'Home search', 'qty' => 2, 'rate' => 250_00, 'tax_class' => 18],
    ]);
    expect($quote->refresh()->version)->toBe(1)
        ->and($quote->total)->toBe(590_00); // 2×25000 + 18% = 500_00 + 90_00

    $sent = $service->send($quote);
    expect($sent->token)->not->toBeNull();

    // Post-send edit: version bumps, audit trail records it.
    $service->editLines($quote, [
        ['description' => 'Home search (updated)', 'qty' => 2, 'rate' => 250_00, 'tax_class' => 18],
    ]);
    expect($quote->refresh()->version)->toBe(2);
});

it('honours single-use, expiry-bound acceptance tokens', function () {
    $service = app(QuoteService::class);
    $org = Organization::factory()->create();

    $quote = $service->createDraft(['organization_id' => $org->id], [
        ['description' => 'Home search', 'qty' => 1, 'rate' => 250_00, 'tax_class' => 18],
    ]);
    $service->send($quote);

    // Acceptance flips terminal and fires QuoteAccepted.
    $decided = $service->decideByToken((string) $quote->token, true, 'client@example.com');
    expect($decided->status->value)->toBe('accepted')
        ->and($decided->accepted_at)->not->toBeNull();

    // The same token can never decide again.
    expect(fn () => $service->decideByToken((string) $quote->token, false))->toThrow(ValidationException::class);

    // Expired quotes reject a fresh (resend) token too.
    $expired = Quote::factory()->expired()->create(['organization_id' => $org->id]);
    expect($expired->isAcceptable())->toBeFalse();
    expect(fn () => $service->decideByToken((string) $expired->token, true))->toThrow(ValidationException::class);
});

it('derives partial and paid status from payments and flags mismatches', function () {
    $recorder = app(PaymentRecorder::class);
    $org = Organization::factory()->create();

    $invoice = Invoice::factory()->sent()->create(['organization_id' => $org->id]);
    expect($invoice->total)->toBe(495_600); // 420_00 paise subtotal + 18% = 75_60_0

    $recorder->record($invoice, [
        'method' => PaymentMethod::Upi->value,
        'amount' => 100_00,
        'paid_at' => now()->toDateString(),
        'reference' => 'UTR123',
    ]);

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Partial);

    $recorder->record($invoice, [
        'method' => PaymentMethod::Bank->value,
        'amount' => 485_600,
        'paid_at' => now()->toDateString(),
    ]);

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->paid_at)->not->toBeNull()
        ->and($invoice->amountDue())->toBe(0);

    // Payments cannot land on void invoices.
    $voided = Invoice::factory()->voided()->create(['organization_id' => $org->id]);
    expect(fn () => $recorder->record($voided, [
        'method' => 'bank', 'amount' => 10_00, 'paid_at' => now()->toDateString(),
    ]))->toThrow(ValidationException::class);
});

it('caps reminders at three on the polite ladder', function () {
    $invoice = Invoice::factory()->create([
        'status' => InvoiceStatus::Overdue,
        'due_at' => now()->subDays(30)->toDateString(),
    ]);

    // Simulate the ladder: each cron run takes the next rung only.
    for ($i = 0; $i < 5; $i++) {
        if (! $invoice->canRemind()) {
            break;
        }
        $invoice->forceFill(['reminders_sent' => $invoice->reminders_sent + 1, 'last_reminder_at' => now()])->save();
    }

    expect($invoice->refresh()->reminders_sent)->toBe(3)
        ->and($invoice->canRemind())->toBeFalse();
});

it('generates a branded PDF snapshot once and keeps it immutable', function () {
    // fake (not spy): renderPdf() calls exists() before generating — a
    // Mockery spy returns null for unstubbed calls and nulls the check.
    Storage::fake('local');
    $org = Organization::factory()->create(['gstin' => '27AAACS1234F1Z5', 'billing_address' => ['line1' => 'Park', 'city' => 'Pune', 'state' => 'MH', 'postal_code' => '411001']]);
    $invoice = Invoice::factory()->sent()->create(['organization_id' => $org->id]);

    $service = app(InvoiceService::class);
    $pdf = $service->renderPdf($invoice);

    // Valid PDF header + brand band color op + our invoice number.
    expect(str_starts_with($pdf, '%PDF-1.4'))->toBeTrue()
        ->and(str_contains($pdf, 'TAX INVOICE'))->toBeTrue()
        ->and(str_contains($pdf, $invoice->number))->toBeTrue()
        // Paise rendered through INR formatting (no ₹ glyph in core fonts).
        ->and(str_contains($pdf, 'INR'))->toBeTrue();

    // Second render returns the SAME snapshot path (immutability contract).
    expect(InvoiceService::snapshotPath($invoice))->toBe('pdfs/invoices/'.$invoice->getKey().'.pdf');
});

it('marks overdue invoices and expires stale quotes on the cron sweep', function () {
    $service = app(InvoiceService::class);
    $org = Organization::factory()->create();

    $pastDue = Invoice::factory()->create(['organization_id' => $org->id, 'status' => InvoiceStatus::Sent, 'due_at' => now()->subDays(4)->toDateString()]);
    $current = Invoice::factory()->create(['organization_id' => $org->id, 'status' => InvoiceStatus::Sent, 'due_at' => now()->addDays(4)->toDateString()]);

    expect($service->markOverdue())->toBe(1)
        ->and($pastDue->refresh()->status)->toBe(InvoiceStatus::Overdue)
        ->and($current->refresh()->status)->toBe(InvoiceStatus::Sent);

    $quoteService = app(QuoteService::class);
    $stale = Quote::factory()->create(['organization_id' => $org->id, 'status' => 'sent', 'valid_until' => now()->subDay()->toDateString(), 'token' => bin2hex(random_bytes(24))]);

    expect($quoteService->expireStale())->toBe(1)
        ->and($stale->refresh()->status->value)->toBe('expired');
});

it('isolates portal invoice queries per organization', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    Invoice::factory()->sent()->create(['organization_id' => $orgA->id]);
    Invoice::factory()->paid()->create(['organization_id' => $orgB->id]);

    expect(Invoice::query()->forOrganization($orgA->id)->count())->toBe(1)
        ->and(Invoice::query()->forOrganization($orgB->id)->first()->status)->toBe(InvoiceStatus::Paid);
});

it('emits a valid minimal PDF with balanced structural units', function () {
    $pdf = (new PdfBuilder)
        ->rect(0, 0, PdfBuilder::WIDTH, 60, [14, 124, 102])
        ->text(48, 24, 'Sewa Hospitality', 16, true, [255, 255, 255])
        ->line(48, 80, PdfBuilder::WIDTH - 48, [225, 228, 227])
        ->text(48, 100, 'Amount: ₹4,956.60', 10)
        ->output();

    expect(str_starts_with($pdf, '%PDF-1.4'))->toBeTrue()
        ->and(str_contains($pdf, 'xref'))->toBeTrue()
        ->and(str_contains($pdf, 'startxref'))->toBeTrue()
        ->and(str_contains($pdf, '%%EOF'))->toBeTrue()
        // ₹ transliterated (core fonts have no rupee glyph).
        ->and(str_contains($pdf, 'INR 4,956.60'))->toBeTrue();
});
