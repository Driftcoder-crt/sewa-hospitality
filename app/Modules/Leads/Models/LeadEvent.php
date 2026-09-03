<?php

namespace App\Modules\Leads\Models;

use App\Models\User;
use App\Modules\Leads\Enums\LeadEventType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One timeline row on a lead (notes, status changes, emails, calls,
 * system events). Append-only — edits never happen; corrections are new
 * notes (03-leads-crm §4.2).
 */
class LeadEvent extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = ['lead_id', 'user_id', 'type', 'payload', 'created_at'];

    protected function casts(): array
    {
        return [
            'type' => LeadEventType::class,
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /** The staff member who performed the event (system rows: null). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
