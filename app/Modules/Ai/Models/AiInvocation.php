<?php

namespace App\Modules\Ai\Models;

use App\Models\User;
use App\Modules\Ai\Enums\AiFeature;
use App\Modules\Ai\Enums\AiInvocationStatus;
use Database\Factories\AiInvocationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One gateway call in the budget + audit ledger
 * (03-technical-specs/03-database-schema.md §10 + 08-ai-system/01 §3).
 * Append-only (created_at only — no updates), 90-day retention, PII
 * guard applied before write: metadata + hashes, never prompts or
 * client PII (01-ai-architecture §5).
 *
 * Monthly budget gauges read this table (scopeMonthly); the ops digest
 * reads error/fallback rates.
 */
class AiInvocation extends Model
{
    use HasFactory;
    use HasUlids;

    protected static function newFactory(): Factory
    {
        return AiInvocationFactory::new();
    }

    public const UPDATED_AT = null;

    /** 08-ai-system/01 §5 DPDP posture: 90-day purge. */
    public const RETENTION_DAYS = 90;

    protected $fillable = [
        'user_id', 'feature', 'provider', 'model', 'tokens_in', 'tokens_out',
        'cost_estimate', 'status', 'latency_ms', 'meta', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'feature' => AiFeature::class,
            'status' => AiInvocationStatus::class,
            'tokens_in' => 'integer',
            'tokens_out' => 'integer',
            'cost_estimate' => 'integer',
            'latency_ms' => 'integer',
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $invocation): void {
            $invocation->created_at ??= now();
        });
    }

    /* ── Relations ─────────────────────────────────────────────────── */

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ── Scopes ────────────────────────────────────────────────────── */

    public function scopeForFeature(Builder $query, string|AiFeature $feature): Builder
    {
        return $query->where('feature', $feature instanceof AiFeature ? $feature->value : $feature);
    }

    public function scopeWithStatus(Builder $query, string|AiInvocationStatus $status): Builder
    {
        return $query->where('status', $status instanceof AiInvocationStatus ? $status->value : $status);
    }

    /** Budget gauge window: calls since $since (local midnight of the 1st). */
    public function scopeSince(Builder $query, Carbon $since): Builder
    {
        return $query->where('created_at', '>=', $since);
    }

    /* ── Behavior ──────────────────────────────────────────────────── */

    public function totalTokens(): int
    {
        return $this->tokens_in + $this->tokens_out;
    }
}
