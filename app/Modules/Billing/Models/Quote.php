<?php

namespace App\Modules\Billing\Models;

use App\Models\User;
use App\Modules\Billing\Enums\QuoteStatus;
use App\Modules\Leads\Models\Lead;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Portal\Models\PortalMove;
use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A formal quote (03-database-schema.md §9 + 12-billing-finance §2/§5):
 * INR integers (paise) — NEVER floats; edits after sending bump
 * `version` (audit trail); the sent PDF is immutable in media storage.
 * Numbering allocated by SequentialNumbering under lock.
 *
 * Line shape (json): [{description, qty, rate, tax_class, amount}] —
 * amounts pre-computed integer paise (rate × qty, line-level rounding).
 */
class Quote extends Model
{
    use HasFactory;
    use HasUlids;

    protected static function newFactory(): Factory
    {
        return QuoteFactory::new();
    }

    protected $fillable = [
        'number', 'organization_id', 'move_record_id', 'lead_id', 'status',
        'lines', 'total', 'currency', 'valid_until', 'sent_at', 'accepted_at',
        'token', 'version', 'created_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'lines' => 'array',
            'total' => 'integer',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /* ── Relations ─────────────────────────────────────────────────── */

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function move(): BelongsTo
    {
        return $this->belongsTo(PortalMove::class, 'move_record_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'quote_id');
    }

    /* ── Scopes ────────────────────────────────────────────────────── */

    public function scopeForOrganization(Builder $query, string $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    /* ── Money (integer-paise only) ────────────────────────────────── */

    /**
     * Recompute total from lines (12 doc §5 — totals recompute on any
     * line change; line rounding happens in the admin builder service).
     *
     * @param  array<int, array{qty: int|float, rate: int, amount?: int}>  $lines
     */
    public function recomputeTotal(): int
    {
        $total = 0;

        foreach ((array) $this->lines as $line) {
            $total += (int) ($line['amount'] ?? ((int) $line['rate'] * (int) $line['qty']));
        }

        $this->total = $total;

        return $total;
    }

    /** INR display formatting is the ONLY place money becomes a float-ish string. */
    public function formattedTotal(): string
    {
        return '₹'.number_format($this->total / 100, 2);
    }

    /* ── Acceptance (12 doc §3: single-use + expiry) ───────────────── */

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }

    public function isAcceptable(): bool
    {
        return $this->status === QuoteStatus::Sent && ! $this->isExpired();
    }
}
