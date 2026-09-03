<?php

namespace App\Modules\Portal\Services;

use App\Modules\Billing\Services\SequentialNumbering;

/**
 * Move references (SEWA-M-YYYY-####): delegates to the shared locked
 * sequence service (same FOR UPDATE discipline as quotes/invoices) —
 * the reference is the natural key that anchors the one-request-per-
 * move review invariant (08 doc §4.3).
 */
class MoveReferenceGenerator
{
    public function next(): string
    {
        return app(SequentialNumbering::class)->next('portal_move_records');
    }
}
