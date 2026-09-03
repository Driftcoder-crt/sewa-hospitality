<?php

namespace App\Modules\Portal\Models;

use App\Models\Media;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Portal\Enums\DocumentCategory;
use App\Modules\Portal\Enums\DocumentVisibility;
use Database\Factories\PortalDocumentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

/**
 * A portal document (schema §8 + 04 doc §5): category-scoped visibility,
 * signed 15-minute download URLs, audit-logged downloads (audited by the
 * controller), originals never on public URLs. Expiry dates feed visa/
 * lease reminders via notification events.
 */
class PortalDocument extends Model
{
    use HasFactory;
    use HasUlids;

    protected static function newFactory(): Factory
    {
        return PortalDocumentFactory::new();
    }

    protected $fillable = [
        'move_record_id', 'organization_id', 'user_id', 'uploaded_by',
        'title', 'media_id', 'category', 'visible_to', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'visible_to' => DocumentVisibility::class,
            'expires_at' => 'datetime',
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

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /* ── Behavior ──────────────────────────────────────────────────── */

    /** Signed, expiring download URL (error-lock: originals never public). */
    public function downloadUrl(): string
    {
        return URL::temporarySignedRoute('portal.documents.download', now()->addMinutes(15), [
            'document' => $this->getKey(),
        ]);
    }

    /** Visa/lease expiry warning surface for the admin + digest. */
    public function isExpiringSoon(): bool
    {
        return $this->expires_at !== null
            && $this->expires_at->isFuture()
            && $this->expires_at->lessThanOrEqualTo(now()->addDays(30));
    }
}
