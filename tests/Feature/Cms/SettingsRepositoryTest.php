<?php

use App\Modules\Cms\Models\Setting;
use App\Modules\Cms\Services\SettingsRepository;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('serves the seeded brand identity through the repository', function () {
    $this->seed(SettingsSeeder::class);

    $repository = app(SettingsRepository::class);

    expect($repository->get('organization.identity')['legalName'])
        ->toBe('SEWA HOSPITALITY SERVICES PVT. LTD.')
        ->and($repository->get('organization.identity')['telephone'])
        ->toBe('+91 98732 55531')
        ->and($repository->get('organization.identity')['telephoneE164'])
        ->toBe('+919873255531')
        ->and($repository->identity()['legalName'])
        ->toBe('SEWA HOSPITALITY SERVICES PVT. LTD.')
        ->and($repository->identity()['telephone_e164'])
        ->toBe('+919873255531');
});

it('groups contact settings with the NAP lock intact', function () {
    $this->seed(SettingsSeeder::class);

    $contact = app(SettingsRepository::class)->group('contact');

    expect($contact['contact.nap']['legalName'])
        ->toBe('SEWA HOSPITALITY SERVICES PVT. LTD.')
        ->and($contact['contact.nap']['phoneDisplay'])
        ->toBe('+91 98732 55531')
        ->and($contact['contact.nap']['phoneE164'])
        ->toBe('+919873255531')
        ->and($contact['contact.emails']['noReply'])
        ->toBe('no-reply@sewahospitality.com');
});

it('writes a setting, flushes the cache and serves the new value', function () {
    $this->seed(SettingsSeeder::class);

    $repository = app(SettingsRepository::class);

    // Warm the read-through cache first.
    $repository->get('organization.identity');
    expect(Cache::has(SettingsRepository::CACHE_KEY))->toBeTrue();

    $setting = $repository->set('organization.identity', ['legalName' => 'TEST CO'], 'brand');

    expect($setting)->toBeInstanceOf(Setting::class)
        // set() flushes the cache before the next read re-caches.
        ->and(Cache::has(SettingsRepository::CACHE_KEY))->toBeFalse();

    expect($repository->get('organization.identity'))
        ->toBe(['legalName' => 'TEST CO']);

    // The cache now reflects the freshly written value.
    expect(Cache::get(SettingsRepository::CACHE_KEY)->get('organization.identity')->value)
        ->toBe(['legalName' => 'TEST CO']);
});

it('falls back to the locked NAP identity when settings are empty', function () {
    // No seeding: RefreshDatabase rolls the previous tests back, and this
    // delete + flush guarantees an empty state under any test DB config.
    DB::table('settings')->delete();
    $repository = app(SettingsRepository::class);
    $repository->flush();

    $identity = $repository->identity();

    expect($identity['legalName'])
        ->toBe('SEWA HOSPITALITY SERVICES PVT. LTD.')
        ->and($identity['telephone'])
        ->toBe('+91 98732 55531')
        ->and($identity['telephone_e164'])
        ->toBe('+919873255531')
        ->and($identity['slogan'])
        ->toBe('Care, delivered.');
});
