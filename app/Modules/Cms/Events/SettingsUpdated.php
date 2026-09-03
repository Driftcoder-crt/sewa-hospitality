<?php

namespace App\Modules\Cms\Events;

use App\Modules\Cms\Models\Setting;

/**
 * Fired by SettingsRepository::set() after a settings row is persisted
 * (04-modules/00-module-system.md event catalog). Carries the written
 * Setting for listeners arriving in M1 (meta/sitemap revalidation).
 * No broadcast here — the read-through cache flush is handled inside
 * SettingsRepository itself.
 */
final readonly class SettingsUpdated
{
    public function __construct(
        public Setting $setting,
    ) {}
}
