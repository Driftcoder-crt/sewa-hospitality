<?php

namespace App\Modules\Billing\Models;

use App\Models\User;
use App\Modules\Billing\Enums\PaymentMethod;
use Database\Factories\InvoicePaymentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A recorded payment (schema §9): financial audit row — never soft
 * deleted; unknown references go to the reconciliation queue, never
 * auto-matched (12 doc §6). Amounts integer paise.
 */
class InvoicePayment extends Model
{
    use HasFactory;
    use HasUlids;

    protected static function newFactory(): Factory
    {
        return InvoicePaymentFactory::new();
    }

    protected $fillable = ['invoice_id', 'method', 'amount', 'paid_at', 'reference', 'recorded_by'];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'amount' => 'integer',
            'paid_at' => 'date',
        ];
    }

    /* ── Relations ─────────────────────────────────────────────────── */

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function formattedAmount(): string
    {
        return '₹'.number_format($this->amount / 100, 2);
    }
}
