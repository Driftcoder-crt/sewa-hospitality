<?php

namespace App\Modules\Leads\Models;

use App\Models\User;
use App\Modules\Cities\Models\City;
use App\Modules\Leads\Enums\LeadEventType;
use App\Modules\Leads\Enums\LeadSource;
use App\Modules\Leads\Enums\LeadStatus;
use App\Modules\Leads\Enums\LeadType;
use App\Modules\Services\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A lead — one inbound opportunity (04-modules/03-leads-crm.md). The
 * write path is LeadIntakeService (transactional, idempotent); this
 * model owns reads, the timeline and the SLA state chips.
 */
class Lead extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'source', 'type', 'name', 'email', 'phone', 'company', 'message',
        'service_id', 'city_id', 'locale', 'status', 'lost_reason',
        'assigned_user_id', 'score', 'enrichment', 'merged_into_lead_id',
        'idempotency_key', 'consent_at', 'consent_version', 'ip_hash',
        'user_agent', 'sla_due_at', 'first_response_at', 'next_action_at',
        'archived_at', 'utm',
    ];

    protected function casts(): array
    {
        return [
            'source' => LeadSource::class,
            'type' => LeadType::class,
            'status' => LeadStatus::class,
            'consent_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'first_response_at' => 'datetime',
            'next_action_at' => 'datetime',
            'archived_at' => 'datetime',
            'enrichment' => 'array',
            'utm' => 'array',
            'score' => 'integer',
        ];
    }

    /* ── Relations ─────────────────────────────────────────────────── */

    public function events(): HasMany
    {
        return $this->hasMany(LeadEvent::class)->orderBy('created_at');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_lead_id');
    }

    /* ── Scopes ────────────────────────────────────────────────────── */

    /** Inbox default: active pipeline rows (archived is a review pile). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    /** Assigned-only view for consultants (03-leads-crm §4 permissions). */
    public function scopeAssignedTo(Builder $query, string $userId): Builder
    {
        return $query->where('assigned_user_id', $userId);
    }

    /** SLA clock still running (not yet first-responded, not closed out). */
    public function scopeSlaPending(Builder $query): Builder
    {
        return $query
            ->whereNull('first_response_at')
            ->whereIn('status', [LeadStatus::New, LeadStatus::Contacted])
            ->whereNotNull('sla_due_at');
    }

    /* ── Timeline helper ───────────────────────────────────────────── */

    /**
     * Append a timeline event (append-only audit of the lead's journey).
     *
     * @param  array<string, mixed>  $payload
     */
    public function logEvent(LeadEventType $type, array $payload = [], ?string $userId = null): LeadEvent
    {
        return $this->events()->create([
            'type' => $type->value,
            'user_id' => $userId ?? auth()->id(),
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }

    /* ── SLA state (inbox chips: ok / amber / red) ─────────────────── */

    /**
     * @return 'done'|'ok'|'warning'|'breached' amber at ≤25% of the
     *                                          window left, red past the deadline, done once responded.
     */
    public function slaState(): string
    {
        if ($this->first_response_at !== null || in_array($this->status, [LeadStatus::Won, LeadStatus::Lost], true)) {
            return 'done';
        }

        $due = $this->sla_due_at;
        if ($due === null) {
            return 'ok';
        }

        if (now()->gte($due)) {
            return 'breached';
        }

        // Amber when less than 25% of the original window remains.
        $windowMinutes = max(1, $this->created_at->diffInMinutes($due, false));
        $remaining = now()->diffInMinutes($due, false);

        return $remaining <= max(1.0, $windowMinutes * 0.25) ? 'warning' : 'ok';
    }

    /** Human SLA countdown for the inbox ("45m left", "overdue 2h"). */
    public function slaLabel(): string
    {
        return match ($this->slaState()) {
            'done' => 'Responded',
            'breached' => 'Overdue '.($this->sla_due_at?->diffForHumans(short: true)),
            default => ($this->sla_due_at?->diffForHumans(['parts' => 1], short: true) ?? '').' left',
        };
    }
}
