<?php

/*
|--------------------------------------------------------------------------
| Foundation smoke tests (M0-b)
|--------------------------------------------------------------------------
| Lock the three primitives every later milestone leans on: the domain map
| in config/sewa.php, the NAP single source of truth in SettingsRepository,
| and the user lifecycle enum. If any of these drift, NAP/SEO/identity
| invariants break silently — so they are pinned here, CI gate 1.
*/

use App\Enums\UserStatus;
use App\Modules\Cms\Services\SettingsRepository;

test('the public domain map is locked to the production default', function () {
    expect(config('sewa.domains.site'))->toBe('sewahospitality.com');
});

test('identity fallback carries the NAP-locked telephone', function () {
    expect(SettingsRepository::IDENTITY_FALLBACK['telephone'])->toBe('+91 98732 55531');
});

test('the user lifecycle enum defines exactly three states', function () {
    expect(UserStatus::cases())->toHaveCount(3);
});
