<?php

namespace App\Modules\Portal\Models;

use App\Models\User;
use App\Modules\Portal\Enums\SenderRole;
use Database\Factories\PortalMessageFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One message in a thread (schema §8): append-only — created_at only,
 * no updates (edit history is chat misconduct; corrections are new
 * messages). Chat send failures retry client-side with the body kept.
 */
class PortalMessage extends Model
{
    use HasFactory;
    use HasUlids;

    protected static function newFactory(): Factory
    {
        return PortalMessageFactory::new();
    }

    public $timestamps = false;

    protected $fillable = ['thread_id', 'sender_user_id', 'sender_role', 'body', 'media_ids', 'read_at', 'created_at'];

    protected function casts(): array
    {
        return [
            'sender_role' => SenderRole::class,
            'media_ids' => 'array',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $message): void {
            $message->created_at ??= now();
        });
    }

    /* ── Relations ─────────────────────────────────────────────────── */

    public function thread(): BelongsTo
    {
        return $this->belongsTo(PortalThread::class, 'thread_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function isSystem(): bool
    {
        return $this->sender_role === SenderRole::System;
    }
}
