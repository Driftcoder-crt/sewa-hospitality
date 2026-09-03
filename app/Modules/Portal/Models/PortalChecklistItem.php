<?php

namespace App\Modules\Portal\Models;

use App\Models\User;
use App\Modules\Portal\Enums\ChecklistStatus;
use Database\Factories\PortalChecklistItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A task on a move checklist (schema §8): admin OR portal side marks
 * done (04 doc §4.2) — done_by records who; ChecklistItemDone fires.
 */
class PortalChecklistItem extends Model
{
    use HasFactory;
    use HasUlids;

    protected static function newFactory(): Factory
    {
        return PortalChecklistItemFactory::new();
    }

    protected $fillable = ['move_record_id', 'title', 'detail', 'due_at', 'done_at', 'done_by', 'sort', 'status'];

    protected function casts(): array
    {
        return [
            'status' => ChecklistStatus::class,
            'due_at' => 'datetime',
            'done_at' => 'datetime',
        ];
    }

    /* ── Relations ─────────────────────────────────────────────────── */

    public function move(): BelongsTo
    {
        return $this->belongsTo(PortalMove::class, 'move_record_id');
    }

    public function doneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }

    /* ── Behavior ──────────────────────────────────────────────────── */

    public function isOverdue(): bool
    {
        return $this->status === ChecklistStatus::Pending
            && $this->due_at !== null
            && $this->due_at->isPast();
    }
}
