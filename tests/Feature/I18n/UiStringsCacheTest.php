<?php

use App\Models\User;
use App\Modules\Cms\Services\SettingsRepository;
use App\Modules\I18n\Models\Translation;
use App\Modules\I18n\Services\UiStrings;
use Database\Seeders\LocalesSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([SettingsSeeder::class, LocalesSeeder::class]);
    Cache::flush();
});

/*
 * UiStrings cache invalidation contract (11-multilingual §6.2): the
 * cache is generation-keyed (sewa.i18n.string.v{N}...) and EVERY
 * Translation write path rotates the generation — an approved string
 * must be served on the very next lookup, never after a TTL ride.
 */

it('never serves a machine draft on a public namespace without the auto-publish policy', function () {
    Translation::factory()->forLocale('ja')->create([
        'namespace' => 'site',
        'key' => 'hero.title',
        'value' => 'マシン下書き',
    ]);

    expect(UiStrings::get('site', 'hero.title', 'ja', 'Meet Sewa'))->toBe('Meet Sewa');
});

it('serves the approved string on the very next lookup — no stale TTL window', function () {
    // Auto-publish OFF: the machine draft is gated, so the EN default
    // serves. Approval must flip the served value immediately — this is
    // the exact regression the generation-keyed cache fixes.
    $row = Translation::factory()->forLocale('ja')->create([
        'namespace' => 'site',
        'key' => 'cta.label',
        'value' => '見積りを依頼',
    ]);

    expect(UiStrings::get('site', 'cta.label', 'ja', 'Get a quote'))->toBe('Get a quote');

    $row->approve(User::factory()->create());

    expect(UiStrings::get('site', 'cta.label', 'ja', 'Get a quote'))->toBe('見積りを依頼');
});

it('serves edited values immediately after approveWith and reverts on reject', function () {
    app(SettingsRepository::class)->set('i18n.auto_publish_site', true);

    $row = Translation::factory()->forLocale('ja')->create([
        'namespace' => 'site',
        'key' => 'banner.line',
        'value' => 'ドラフト版',
    ]);

    expect(UiStrings::get('site', 'banner.line', 'ja', 'Banner'))->toBe('ドラフト版');

    $row->approveWith(User::factory()->create(), '承認版バナー');
    expect(UiStrings::get('site', 'banner.line', 'ja', 'Banner'))->toBe('承認版バナー');

    $row->reject(User::factory()->create());
    // Back to machine — auto-publish still on, so the row serves again,
    // but the machine value after rotation, never the stale approval.
    expect(UiStrings::get('site', 'banner.line', 'ja', 'Banner'))->toBe('ドラフト版');
});

it('re-importing a machine string replaces the served value immediately', function () {
    app(SettingsRepository::class)->set('i18n.auto_publish_site', true);

    Translation::importOne('ja', 'site', 'nav.move', '引っ越し');
    expect(UiStrings::get('site', 'nav.move', 'ja', 'Move'))->toBe('引っ越し');

    Translation::importOne('ja', 'site', 'nav.move', '国際引っ越し');
    expect(UiStrings::get('site', 'nav.move', 'ja', 'Move'))->toBe('国際引っ越し');
});

it('never overwrites a human-reviewed string on re-import', function () {
    $row = Translation::factory()->forLocale('ja')->create([
        'namespace' => 'site',
        'key' => 'nav.move',
        'value' => '承認済み',
    ]);
    $row->approve(User::factory()->create());

    Translation::importOne('ja', 'site', 'nav.move', '上書き試行');

    expect(UiStrings::get('site', 'nav.move', 'ja', 'Move'))->toBe('承認済み');
});

it('rotates the cache generation on writes and skips no-op imports', function () {
    expect(UiStrings::version())->toBe(1);

    Translation::importOne('ja', 'site', 'k.a', 'one');
    expect(UiStrings::version())->toBe(2);

    Translation::importOne('ja', 'site', 'k.a', 'one'); // identical — no bump
    expect(UiStrings::version())->toBe(2);

    Translation::importOne('ja', 'site', 'k.a', 'two');
    expect(UiStrings::version())->toBe(3);
});
