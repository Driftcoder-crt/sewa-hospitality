<?php

namespace App\Modules\Billing\Models;

use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Portal\Models\PortalMove;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An invoice (schema §9 + 12-billing-finance §2/§5): integer-paise
 * amounts, sequential numbering under lock, void keeps the number.
 * Status transitions derive from payments — partial/paid recorded by
 * PaymentRecorder; overdue flips on schedule (billing:mark-overdue).
 */
class Invoice extends Model
{
    use HasFactory;
    use HasUlids;

    protected static function newFactory(): Factory
    {
        return InvoiceFactory::new();
    }

    protected $fillable = [
        'number', 'quote_id', 'organization_id', 'move_record_id', 'status',
        'lines', 'subtotal', 'tax_breakdown', 'total', 'currency', 'due_at',
        'sent_at', 'paid_at', 'reminders_sent', 'last_reminder_at',
        'void_reason', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'lines' => 'array',
            'tax_breakdown' => 'array',
            'subtotal' => 'integer',
            'total' => 'integer',
            'due_at' => 'date',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'last_reminder_at' => 'datetime',
        ];
    }

    /* ── Relations ─────────────────────────────────────────────────── */

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function move(): BelongsTo
    {
        return $this->belongsTo(PortalMove::class, 'move_record_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    /* ── Scopes (portal tenant isolation) ──────────────────────────── */

    public function scopeForOrganization(Builder $query, string $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Partial, InvoiceStatus::Overdue]);
    }

    /* ── Money (integer-paise only) ────────────────────────────────── */

    public function amountPaid(): int
    {
        return (int) $this->payments()->sum('amount');
    }

    public function amountDue(): int
    {
        return max(0, $this->total - $this->amountPaid());
    }

    public function isFullyPaid(): bool
    {
        return $this->amountDue() === 0;
    }

    public function formattedTotal(): string
    {
        return '₹'.number_format($this->total / 100, 2);
    }

    public function formattedDue(): string
    {
        return '₹'.number_format($this->amountDue() / 100, 2);
    }

    /* ── Status behavior ───────────────────────────────────────────── */

    public function isVoid(): bool
    {
        return $this->status === InvoiceStatus::Void;
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null
            && $this->due_at->isPast()
            && $this->status->isOpen();
    }

    /** Max 3 reminders, then a human outreach task (12 doc §5 etiquette). */
    public function canRemind(): bool
    {
        return $this->status->isOpen() && $this->reminders_sent < 3;
    }
}
