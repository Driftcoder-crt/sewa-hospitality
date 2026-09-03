<?php

namespace App\Modules\I18n\Models;

use Database\Factories\LocaleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * Locale (03-technical-specs/03-database-schema.md §10). The locale
 * code IS the primary key — 'en|hi|ja|ko|tr|ar' — a stable public key
 * used in URL prefixes (/ja/services/…), hreflang alternates and the
 * translation pipeline. NOT a ULID (see the M0 migration docblock).
 *
 * `en` is the x-default translation source; `direction` drives RTL
 * serving (ar) and `auto_translate` gates the TranslateContent pipeline
 * per locale (11-multilingual §1/§4).
 */
class Locale extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return LocaleFactory::new();
    }

    /** The x-default source locale every translation group roots in. */
    public const DEFAULT = 'en';

    public const DIRECTION_LTR = 'ltr';

    public const DIRECTION_RTL = 'rtl';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code', 'name', 'native_name', 'direction', 'fallback_for',
        'auto_translate', 'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'auto_translate' => 'boolean',
        ];
    }

    /* ── Relations ─────────────────────────────────────────────────── */

    /** @return HasMany<Translation, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class, 'locale', 'code');
    }

    /* ── Scopes ────────────────────────────────────────────────────── */

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public function scopeRtl(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_RTL);
    }

    public function scopeTranslatable(Builder $query): Builder
    {
        return $query->where('auto_translate', true);
    }

    /* ── Behavior ──────────────────────────────────────────────────── */

    public function isRtl(): bool
    {
        return $this->direction === self::DIRECTION_RTL;
    }

    public function isDefault(): bool
    {
        return $this->code === self::DEFAULT;
    }

    /**
     * Enabled locale codes, memoized per request and cached until the
     * table changes. Consumers: the {locale} route constraint, the
     * LocaleResolver, hreflang generation. Cache is invalidated on
     * save/delete so admin toggles take effect immediately.
     *
     * @return list<string>
     */
    public static function enabledCodes(): array
    {
        try {
            return Cache::rememberForever('sewa.i18n.enabled_codes', function (): array {
                // The locales table may not exist yet during the very first
                // migration/seed run — the try/catch keeps bootstrap safe.
                try {
                    return self::query()->enabled()->orderBy('code')->pluck('code')->all();
                } catch (\Throwable) {
                    return [self::DEFAULT];
                }
            });
        } catch (\Throwable) {
            // The cache STORE itself is unreachable (database store with no
            // cache table — DB-less artisan boot, deploy-time config:cache
            // while MySQL is down). Resolve WITHOUT caching; the query's
            // own guard returns the safe default. Route registration in
            // routes/web.php builds its locale regex from this method, so
            // a throwing boot path would take down every artisan command.
            try {
                return self::query()->enabled()->orderBy('code')->pluck('code')->all();
            } catch (\Throwable) {
                return [self::DEFAULT];
            }
        }
    }

    /** @return array<string, string> code → native_name (switcher + banner). */
    public static function enabledSwitcher(): array
    {
        try {
            return Cache::rememberForever('sewa.i18n.switcher', function (): array {
                try {
                    return self::query()->enabled()->orderBy('code')->pluck('native_name', 'code')->all();
                } catch (\Throwable) {
                    return [self::DEFAULT => 'English'];
                }
            });
        } catch (\Throwable) {
            // Mirror of enabledCodes(): cache-store outage must never kill
            // the boot path — resolve uncached, fall back to the default.
            try {
                return self::query()->enabled()->orderBy('code')->pluck('native_name', 'code')->all();
            } catch (\Throwable) {
                return [self::DEFAULT => 'English'];
            }
        }
    }

    /** Does an enabled locale exist for this code? */
    public static function isEnabled(string $code): bool
    {
        return in_array($code, self::enabledCodes(), true);
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::flushRegistry());
        static::deleted(fn () => self::flushRegistry());
    }

    public static function flushRegistry(): void
    {
        Cache::forget('sewa.i18n.enabled_codes');
        Cache::forget('sewa.i18n.switcher');
    }
}
