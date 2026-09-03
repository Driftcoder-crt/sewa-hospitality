<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Audit trail row (03-technical-specs/03-database-schema.md §11), written
 * exclusively by App\Support\Audit\ActivityLogger — not the
 * spatie/laravel-activitylog package. `changes` is already redacted before
 * it reaches this model; retention is 7 years with hard deletes handled by
 * the retention command, so no soft deletes here.
 */
class ActivityLog extends Model
{
    use HasUlids;

    protected $table = 'activity_log';

    protected $fillable = [
        'user_id',
        'context',
        'action',
        'subject_type',
        'subject_id',
        'changes',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    /** Acting staff/portal user (nullable — system context has none). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Polymorphic subject of the audited action. */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
