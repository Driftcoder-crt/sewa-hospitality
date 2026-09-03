<?php

namespace App\Modules\Cms\Services;

use App\Modules\Cms\Events\SettingsUpdated;
use App\Modules\Cms\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Cached settings repository (shared kernel role of the CMS module).
 * Reads are cached forever and flushed on write — shared-hosting CPU
 * discipline (05-security-reliability §2.5).
 */
class SettingsRepository
{
    public const CACHE_KEY = 'sewa.settings.all';

    /**
     * NAP fallback used only if settings are not yet seeded (e.g. error
     * pages during a failed deploy). Values are byte-identical to the
     * NAP lock rule (01-platform-vision/02-brand-sewa-hospitality.md §9).
     */
    public const IDENTITY_FALLBACK = [
        'legalName' => 'SEWA HOSPITALITY SERVICES PVT. LTD.',
        'brand' => 'Sewa Hospitality',
        'url' => 'https://sewahospitality.com',
        'telephone' => '+91 98732 55531',
        'telephone_e164' => '+919873255531',
        'email' => 'hello@sewahospitality.com',
        'address' => [
            'street' => 'MS0228, 2nd Floor, DT Mega Mall, A Block, DLF Phase 1',
            'city' => 'Gurugram',
            'state' => 'Haryana',
            'postalCode' => '122002',
            'country' => 'IN',
        ],
        'geo' => ['lat' => 28.4949, 'lng' => 77.0886],
        'slogan' => 'Care, delivered.',
        'foundingDate' => '2026',
    ];

    /** @return Collection<string, Setting> */
    public function all()
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()->get()->keyBy('key'));
    }

    /** @return mixed */
    public function get(string $key, mixed $default = null)
    {
        $setting = $this->all()->get($key);

        return $setting?->value ?? $default;
    }

    /** @return array<string, mixed> */
    public function group(string $group): array
    {
        return $this->all()
            ->filter(fn (Setting $setting): bool => $setting->group === $group)
            ->mapWithKeys(fn (Setting $setting): array => [$setting->key => $setting->value])
            ->all();
    }

    /** Organization identity (brand JSON §9) with NAP fallback beneath it. */
    public function identity(): array
    {
        $identity = $this->get('organization.identity', []);

        return is_array($identity) && $identity !== []
            ? array_merge(self::IDENTITY_FALLBACK, $identity)
            : self::IDENTITY_FALLBACK;
    }

    public function set(string $key, mixed $value, string $group = 'brand'): Setting
    {
        $setting = Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group],
        );

        $this->flush();
        event(new SettingsUpdated($setting));

        return $setting;
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
