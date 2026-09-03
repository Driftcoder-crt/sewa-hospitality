<?php

namespace App\Modules\Cms\Models;

use App\Modules\Cms\Enums\RedirectCode;
use App\Modules\Cms\Services\RedirectService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Redirect (03-technical-specs/03-database-schema.md §2): from
 * (unique, normalized path) → to, 301|302, hit counter, note, active.
 */
class Redirect extends Model
{
    use HasUlids;

    protected $fillable = ['from', 'to', 'code', 'hits', 'note', 'active'];

    protected function casts(): array
    {
        return [
            'code' => RedirectCode::class,
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Normalize on the way in so uniqueness is enforced on the
        // canonical form (trailing slashes, case, missing leading /).
        static::saving(function (self $redirect): void {
            $redirect->from = RedirectService::normalize($redirect->from);
        });

        static::saved(fn () => RedirectService::flushMap());
        static::deleted(fn () => RedirectService::flushMap());
    }

    /** Serve this redirect and count the hit (no model events). */
    public function hit(): void
    {
        static::withoutTimestamps(function (): void {
            static::query()
                ->whereKey($this->getKey())
                ->increment('hits');
        });
    }
}
