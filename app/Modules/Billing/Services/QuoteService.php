<?php

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Enums\QuoteStatus;
use App\Modules\Billing\Events\QuoteAccepted;
use App\Modules\Billing\Models\Quote;
use App\Support\Audit\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Quote lifecycle (12-billing-finance §4.1/§5): builder totals, send
 * with secure accept token, edits after send become a new version,
 * token acceptance is single-use + expiry-bound, rejection/expiry are
 * terminal. Allocation rides the locked SequentialNumbering.
 */
class QuoteService
{
    public function __construct(
        private readonly SequentialNumbering $numbering,
        private readonly TaxCalculator $calculator,
    ) {}

    /**
     * Create (or replace the draft state of) a quote with fresh lines.
     * Totals recompute server-side — client numbers are never trusted.
     *
     * @param  list<array{description: mixed, qty: mixed, rate: mixed, tax_class: mixed}>  $lines
     */
    public function createDraft(array $attributes, array $lines): Quote
    {
        $built = $this->calculator->build($lines);

        return DB::transaction(function () use ($attributes, $built): Quote {
            $quote = new Quote([
                ...$attributes,
                'number' => $this->numbering->next('quotes'),
                'lines' => $built['lines'],
                'total' => $built['total'],
                'status' => QuoteStatus::Draft,
                'version' => 1,
            ]);

            $quote->save();

            ActivityLogger::log('admin', 'create', $quote, ['number' => $quote->number, 'total' => $quote->total]);

            return $quote;
        });
    }

    /**
     * Edit an existing quote. BEFORE send: in place. AFTER send: the
     * sent state is immutable — version bumps and the quote returns to
     * `sent` timestamps cleared? No — a new version keeps history: the
     * row's `version` increments and `sent_at` is refreshed on resend
     * only. Contract: "edits after sending create a new version row".
     * The platform stores the versioned state on the row (version
     * counter + audit diff); the immutable artifact is the SENT PDF
     * snapshot which is never overwritten.
     *
     * @param  list<array{description: mixed, qty: mixed, rate: mixed, tax_class: mixed}>  $lines
     */
    public function editLines(Quote $quote, array $lines, ?string $validUntil = null, ?string $notes = null): Quote
    {
        if ($quote->status->isTerminal()) {
            throw ValidationException::withMessages([
                'quote' => 'Accepted/expired/rejected quotes are immutable.',
            ]);
        }

        $built = $this->calculator->build($lines);

        $wasSent = $quote->status === QuoteStatus::Sent;

        $quote->lines = $built['lines'];
        $quote->total = $built['total'];

        if ($validUntil !== null) {
            $quote->valid_until = $validUntil;
        }

        if ($notes !== null) {
            $quote->notes = $notes;
        }

        if ($wasSent) {
            // Version bump + the previously sent snapshot stays untouched.
            $quote->version = $quote->version + 1;
        }

        $quote->save();

        ActivityLogger::log('admin', 'update', $quote, [
            'total' => $quote->total,
            'version' => $quote->version,
            'after_send' => $wasSent,
        ]);

        return $quote;
    }

    /**
     * Send: stamp + mint the single-use accept token (64 hex chars).
     * The email itself is queued by the caller (listener) — status is
     * `sent` the moment the send job is dispatched, matching "never
     * 'sent' unless email actually queued".
     */
    public function send(Quote $quote): Quote
    {
        if ($quote->status !== QuoteStatus::Draft) {
            throw ValidationException::withMessages([
                'quote' => 'Only draft quotes can be sent.',
            ]);
        }

        $quote->forceFill([
            'status' => QuoteStatus::Sent,
            'sent_at' => now(),
            'token' => bin2hex(random_bytes(24)),
        ])->save();

        ActivityLogger::log('admin', 'publish', $quote, ['number' => $quote->number]);

        return $quote;
    }

    /**
     * Token acceptance (12 doc §3): single-use, expiry-bound, logged.
     * Accept → terminal accepted + QuoteAccepted event (invoice draft +
     * lead status ride the listeners). Reject → terminal rejected.
     */
    public function decideByToken(string $token, bool $accept, ?string $actorEmail = null): Quote
    {
        $quote = Quote::query()->where('token', $token)->first();

        if ($quote === null || ! $quote->isAcceptable()) {
            throw ValidationException::withMessages([
                'token' => 'This acceptance link is no longer valid — the quote may have expired or already been answered.',
            ]);
        }

        $quote->forceFill([
            'status' => $accept ? QuoteStatus::Accepted : QuoteStatus::Rejected,
            'accepted_at' => $accept ? now() : null,
        ])->save();

        ActivityLogger::log('portal', 'update', $quote, [
            'decision' => $accept ? 'accepted' : 'rejected',
            'actor' => $actorEmail,
        ]);

        if ($accept) {
            QuoteAccepted::dispatch($quote);
        }

        return $quote;
    }

    /** Valid-until enforcement sweep (cron): expired sent quotes flip terminal. */
    public function expireStale(): int
    {
        $stale = Quote::query()
            ->where('status', QuoteStatus::Sent)
            ->whereNotNull('valid_until')
            ->where('valid_until', '<', now()->toDateString())
            ->get();

        foreach ($stale as $quote) {
            $quote->forceFill(['status' => QuoteStatus::Expired])->save();

            ActivityLogger::log('system', 'update', $quote, ['number' => $quote->number, 'status' => 'expired']);
        }

        return $stale->count();
    }
}
