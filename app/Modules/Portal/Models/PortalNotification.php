<?php

namespace App\Modules\Portal\Models;

use App\Models\User;
use Database\Factories\PortalNotificationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One notification-center row (04 doc §3): mark-read list + realtime
 * badge (poll 30s native transport — 11-realtime §3). Rows are
 * created through PortalNotificationCenter so the NotificationCreated
 * event always rides along.
 */
class PortalNotification extends Model
{
    use HasFactory;
    use HasUlids;

    protected static function newFactory(): Factory
    {
        return PortalNotificationFactory::new();
    }

    public $timestamps = false;

    protected $fillable = ['user_id', 'title', 'body', 'url', 'kind', 'read_at', 'created_at'];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $notification): void {
            $notification->created_at ??= now();
        });
    }

    /* ── Relations ─────────────────────────────────────────────────── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ── Scopes ────────────────────────────────────────────────────── */

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /* ── Behavior ──────────────────────────────────────────────────── */

    public function markRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }
}
