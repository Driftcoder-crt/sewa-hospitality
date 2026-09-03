<?php

namespace App\Modules\Portal\Models;

use App\Models\Concerns\HasSewaMedia;
use App\Models\User;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\Quote;
use App\Modules\Cities\Models\City;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Portal\Enums\MoveStage;
use App\Modules\Portal\Enums\MoveStatus;
use Database\Factories\PortalMoveFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;

/**
 * A relocation engagement (03-database-schema.md §8): the portal's
 * tenant-scoped root. Tenant scoping on EVERY query (04 doc §2):
 * manager sees org-wide, employee sees own move only — scopes
 * forOrganization()/forEmployee() encode the matrix.
 */
class PortalMove extends Model implements HasMedia
{
    use HasFactory;
    use HasSewaMedia;
    use HasUlids;

    // Contract table name (03-database-schema.md §8 "portal_move_records");
    // Laravel's convention would guess portal_moves, but the migration
    // created the spec's name — the model must match it.
    protected $table = 'portal_move_records';

    protected static function newFactory(): Factory
    {
        return PortalMoveFactory::new();
    }

    protected $fillable = [
        'reference', 'organization_id', 'employee_user_id', 'primary_consultant_user_id',
        'assignee_name', 'assignee_email', 'origin_city', 'destination_city_id',
        'move_date', 'stage', 'status', 'summary', 'service_ids', 'timeline',
    ];

    protected function casts(): array
    {
        return [
            'stage' => MoveStage::class,
            'status' => MoveStatus::class,
            'move_date' => 'date',
            'service_ids' => 'array',
            'timeline' => 'array',
        ];
    }

    /* ── Relations ─────────────────────────────────────────────────── */

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_consultant_user_id');
    }

    public function destinationCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'destination_city_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PortalDocument::class, 'move_record_id');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(PortalChecklistItem::class, 'move_record_id')->orderBy('sort');
    }

    public function threads(): HasMany
    {
        return $this->hasMany(PortalThread::class, 'move_record_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'move_record_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'move_record_id');
    }

    /* ── Tenant scoping (04 doc §2 — the isolation matrix) ─────────── */

    /** Everything in one organization (manager/billing role view). */
    public function scopeForOrganization(Builder $query, string $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    /** Only the employee's own moves. */
    public function scopeForEmployee(Builder $query, string $userId): Builder
    {
        return $query->where('employee_user_id', $userId);
    }

    /** Consultant cockpit: moves assigned to me (admin sees all). */
    public function scopeAssignedTo(Builder $query, string $userId): Builder
    {
        return $query->where('primary_consultant_user_id', $userId);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', MoveStatus::Active)
            ->whereNotIn('stage', [MoveStage::Closed]);
    }

    /* ── Stage helpers ─────────────────────────────────────────────── */

    /** Timeline progress for the portal /moves view (0–100). */
    public function stageProgress(): int
    {
        $pipeline = MoveStage::pipeline();

        return (int) round((($this->stage?->position() ?? 0) + 1) / count($pipeline) * 100);
    }

    /** Checklist summary for the dashboard card. */
    public function nextChecklistItems(int $limit = 3): HasMany
    {
        return $this->checklistItems()
            ->where('status', 'pending')
            ->orderByRaw('due_at is null, due_at asc')
            ->limit($limit);
    }
}
