<?php

namespace App\Modules\Portal\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Portal\Enums\ThreadStatus;
use Database\Factories\PortalThreadFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A consultant ↔ client thread (schema §8). Idle-beyond-SLA threads
 * surface in the ops digest (04 doc §5 — chat SLA).
 */
class PortalThread extends Model
{
    use HasFactory;
    use HasUlids;

    protected static function newFactory(): Factory
    {
        return PortalThreadFactory::new();
    }

    protected $fillable = ['move_record_id', 'organization_id', 'subject', 'status', 'created_by'];

    protected function casts(): array
    {
        return [
            'status' => ThreadStatus::class,
        ];
    }

    /* ── Relations ─────────────────────────────────────────────────── */

    public function move(): BelongsTo
    {
        return $this->belongsTo(PortalMove::class, 'move_record_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(PortalMessage::class, 'thread_id')->orderBy('created_at');
    }

    /* ── Scopes ────────────────────────────────────────────────────── */

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', ThreadStatus::Open);
    }

    /** Latest message per thread (inbox list rendering). */
    public function lastMessage(): ?PortalMessage
    {
        return $this->messages()->orderByDesc('created_at')->first();
    }
}
