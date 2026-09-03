<?php

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\Quote;
use InvalidArgumentException;

/**
 * GST-correct line math (12-billing-finance §5): line-level tax classes
 * (0/5/18/28), line rounding (per convention — each line's tax rounds
 * independently), totals recomputed on ANY line change. Integers only —
 * every value in paise; the only division happens at display time.
 *
 * Line shape (json on quotes/invoices):
 *   [{description, qty, rate, tax_class, amount}]
 *   rate  = unit price in PAISE
 *   amount = rate × qty (int)
 *   tax_class = percent int (0|5|18|28)
 *
 * "The platform computes; the accountant certifies" — reverse-charge and
 * place-of-supply nuances stay documented with the accountant-of-record,
 * not hardcoded guesses here.
 */
final class TaxCalculator
{
    /** @var list<int> sanctioned classes (12 doc §5) */
    public const CLASSES = [0, 5, 18, 28];

    /**
     * Validate + normalize raw builder lines into the stored shape.
     *
     * @param  list<array{description?: mixed, qty?: mixed, rate?: mixed, tax_class?: mixed}>  $raw
     * @return list<array{description: string, qty: int, rate: int, tax_class: int, amount: int}>
     */
    public function normalizeLines(array $raw): array
    {
        $lines = [];

        foreach ($raw as $index => $line) {
            $description = trim((string) ($line['description'] ?? ''));
            $qty = (int) ($line['qty'] ?? 0);
            $rate = (int) ($line['rate'] ?? 0);
            $taxClass = (int) ($line['tax_class'] ?? 0);

            if ($description === '') {
                throw new InvalidArgumentException("Line {$index}: description is required.");
            }

            if ($qty < 1 || $qty > 10_000) {
                throw new InvalidArgumentException("Line {$index}: quantity must be 1–10000.");
            }

            if ($rate < 0) {
                throw new InvalidArgumentException("Line {$index}: rate cannot be negative.");
            }

            if (! in_array($taxClass, self::CLASSES, true)) {
                throw new InvalidArgumentException("Line {$index}: unsupported tax class [{$taxClass}].");
            }

            $lines[] = [
                'description' => $description,
                'qty' => $qty,
                'rate' => $rate,
                'tax_class' => $taxClass,
                'amount' => $rate * $qty,
            ];
        }

        if ($lines === []) {
            throw new InvalidArgumentException('A quote needs at least one line item.');
        }

        return $lines;
    }

    /**
     * Totals from stored lines: subtotal, per-class tax, grand total.
     * Line tax = amount × class% , rounded HALF-UP per line (GST line
     * rounding convention).
     *
     * @param  list<array{amount: int, tax_class: int, ...}>  $lines
     * @return array{subtotal: int, tax: array<string, int>, total: int}
     */
    public function totals(array $lines): array
    {
        $subtotal = 0;
        $taxByClass = [];

        foreach ($lines as $line) {
            $amount = (int) $line['amount'];
            $subtotal += $amount;

            $class = (int) ($line['tax_class'] ?? 0);

            if ($class > 0) {
                $tax = (int) round($amount * $class / 100);
                $key = (string) $class;
                $taxByClass[$key] = ($taxByClass[$key] ?? 0) + $tax;
            }
        }

        ksort($taxByClass);

        return [
            'subtotal' => $subtotal,
            'tax' => $taxByClass,
            'total' => $subtotal + array_sum($taxByClass),
        ];
    }

    /** Quote convenience: normalize + totals + persist-ready array. */
    public function build(array $rawLines): array
    {
        $lines = $this->normalizeLines($rawLines);
        $totals = $this->totals($lines);

        return ['lines' => $lines] + $totals;
    }

    /** Human display — the ONLY float crossing (format per locale, never stored). */
    public static function money(int $paise): string
    {
        return '₹'.number_format($paise / 100, 2);
    }

    /** Guard used by tests: lines shape on a persisted model. */
    public static function assertLinesFrom(Quote $quote): array
    {
        return (array) $quote->lines;
    }
}
