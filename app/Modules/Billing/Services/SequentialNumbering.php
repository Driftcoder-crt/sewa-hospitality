<?php

namespace App\Modules\Billing\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Concurrency-safe sequential document numbering (12-billing-finance
 * §2 + 05-security-reliability §2.1 + schema §12):
 *
 *     SEWA-Q-2026-0001 / SEWA-I-2026-0001
 *
 * Allocation runs INSIDE a transaction with `SELECT … FOR UPDATE` on
 * the latest row of the prefix — two concurrent issues serialise, and
 * a duplicate number is structurally impossible (UNIQUE index is the
 * backstop that turns a race into an error instead of corruption).
 * Void keeps its number — the sequence never reuses (statutory rule).
 *
 * The monotonic suffix is derived from the highest existing number for
 * the same prefix; the UNIQUE index guarantees integrity even under
 * storage engines where gap locking cannot (e.g. sqlite in tests).
 */
class SequentialNumbering
{
    /** @var array<string, string> model table => prefix template part */
    private const TABLES = [
        'quotes' => 'SEWA-Q-',
        'invoices' => 'SEWA-I-',
        'portal_move_records' => 'SEWA-M-',
    ];

    /**
     * Allocate the next number for the table (call inside a transaction
     * with other writes; the method opens its own only when standalone).
     */
    public function next(string $table, ?int $year = null): string
    {
        if (! isset(self::TABLES[$table])) {
            throw new InvalidArgumentException("Sequential numbering not configured for table [{$table}].");
        }

        $year ??= (int) now()->format('Y');
        $prefix = self::TABLES[$table].$year.'-';
        $number = DB::transaction(function () use ($table, $prefix): string {
            $last = DB::table($table)
                ->where('number', 'like', $prefix.'%')
                ->orderByDesc('number')
                ->lockForUpdate()
                ->value('number');

            $sequence = $last === null
                ? 1
                : ((int) substr((string) $last, strlen($prefix))) + 1;

            return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        });

        return $number;
    }

    /** Format preview without allocating (admin builder drafts). */
    public function preview(string $table, ?int $year = null): string
    {
        if (! isset(self::TABLES[$table])) {
            throw new InvalidArgumentException("Sequential numbering not configured for table [{$table}].");
        }

        $year ??= (int) now()->format('Y');

        return self::TABLES[$table].$year.'-####';
    }
}
