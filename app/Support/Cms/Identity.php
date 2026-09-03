<?php

namespace App\Support\Cms;

use App\Modules\Cms\Services\SettingsRepository;
use Throwable;

/**
 * Identity resolver (brand doc §9 NAP lock): settings-backed with the
 * byte-identical fallback when the database is unavailable (error
 * pages must render during an outage — never dead-end, never crash).
 */
final class Identity
{
    /** @return array<string, mixed> */
    public static function current(): array
    {
        try {
            $identity = app(SettingsRepository::class)->identity();
            if (is_array($identity) && $identity !== []) {
                return $identity;
            }
        } catch (Throwable) {
            // fall through to the NAP-locked fallback
        }

        return SettingsRepository::IDENTITY_FALLBACK;
    }
}
