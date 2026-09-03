<?php

namespace App\Modules\I18n\Services;

use App\Modules\Cms\Services\SettingsRepository;
use App\Modules\I18n\Enums\TranslationStatus;
use App\Modules\I18n\Models\Locale;
use App\Modules\I18n\Models\Translation;
use Illuminate\Support\Facades\Cache;

/**
 * UI-string resolver over the translations table (03-database-schema
 * §10 + 11-multilingual §4/§6.3). The gate is structural: a machine
 * draft NEVER serves a public surface (site/portal/email) unless the
 * per-namespace auto-publish policy has been deliberately switched on
 * in settings; the admin namespace is internal and may serve drafts.
 *
 * Falls back to the caller-supplied EN default — missing strings are
 * always honest English, never blank.
 */
final class UiStrings
{
    /**
     * Cache-generation tag. Every Translation write path (approve /
     * approveWith / reject / importOne) rotates this, so a reviewed
     * string is served on the NEXT lookup — a stale draft can never
     * outlive its approval by riding a 10-minute TTL (11-multilingual
     * §6.2 review-guarantee). Old-generation keys simply age out.
     */
    public static function version(): int
    {
        return (int) Cache::get('sewa.i18n.strings.version', 1);
    }

    /** Invalidate every cached UI string by rotating the generation tag. */
    public static function bumpVersion(): void
    {
        Cache::forever('sewa.i18n.strings.version', self::version() + 1);
        self::flush();
    }

    public static function get(
        string $namespace,
        string $key,
        ?string $locale = null,
        ?string $default = null,
    ): string {
        $locale ??= app()->getLocale();

        if ($locale === Locale::DEFAULT) {
            return $default ?? '';
        }

        $cacheKey = sprintf('sewa.i18n.string.v%d.%s.%s.%s', self::version(), $namespace, $locale, $key);

        return Cache::remember($cacheKey, 600, function () use ($namespace, $locale, $key, $default): string {
            $row = Translation::query()
                ->where('locale', $locale)
                ->where('namespace', $namespace)
                ->where('key', $key)
                ->first();

            if ($row === null) {
                return $default ?? '';
            }

            if ($row->status === TranslationStatus::HumanReviewed) {
                return (string) $row->value;
            }

            // Machine draft: public namespaces need the explicit
            // auto-publish policy; admin serves drafts (internal only).
            $autoPublish = in_array($namespace, ['admin'], true)
                || (bool) app(SettingsRepository::class)->get(
                    "i18n.auto_publish_{$namespace}",
                    false,
                );

            return $autoPublish ? (string) $row->value : ($default ?? '');
        });
    }

    /** Flush the registry caches (locale codes + switcher payload). */
    public static function flush(): void
    {
        Cache::forget('sewa.i18n.enabled_codes');
        Cache::forget('sewa.i18n.switcher');
    }
}
