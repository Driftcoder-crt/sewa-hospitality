<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * CMS Revision Model
 * 
 * Tracks changes to pages and blocks for audit and rollback.
 */
class Revision extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected $table = 'cms_revisions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'revisionable_type',
        'revisionable_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
        'reason',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    /**
     * Get the parent revisionable model.
     */
    public function revisionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who made the revision.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get available action types.
     */
    public static function getActions(): array
    {
        return [
            'created' => 'Created',
            'updated' => 'Updated',
            'published' => 'Published',
            'unpublished' => 'Unpublished',
            'archived' => 'Archived',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
            'copied' => 'Copied',
        ];
    }

    /**
     * Check if revision has changes.
     */
    public function hasChanges(): bool
    {
        return !empty($this->new_values) && $this->new_values !== $this->old_values;
    }

    /**
     * Get changed fields.
     */
    public function getChangedFields(): array
    {
        if (!$this->hasChanges()) {
            return [];
        }

        return array_keys(array_diff_assoc(
            $this->new_values ?? [],
            $this->old_values ?? []
        ));
    }

    /**
     * Rollback to this revision.
     */
    public function rollback(?string $reason = null): bool
    {
        $model = $this->revisionable;
        
        if (!$model) {
            return false;
        }

        $model->update($this->old_values);

        // Create new revision for rollback action
        static::create([
            'revisionable_type' => get_class($model),
            'revisionable_id' => $model->id,
            'user_id' => auth()->id(),
            'action' => 'rollback',
            'old_values' => $model->getOriginal(),
            'new_values' => $this->old_values,
            'reason' => $reason ?? "Rollback to revision #{$this->id}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return true;
    }

    /**
     * Get diff summary.
     */
    public function getDiffSummary(): string
    {
        $changedFields = $this->getChangedFields();
        
        if (empty($changedFields)) {
            return 'No changes';
        }

        return count($changedFields) . ' field(s) modified: ' . implode(', ', $changedFields);
    }

    /**
     * Scope to get revisions by action.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to get revisions by user.
     */
    public function scopeUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get recent revisions.
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->latest()->limit($limit);
    }
}
