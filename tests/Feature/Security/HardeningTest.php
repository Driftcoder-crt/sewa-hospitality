<?php

use App\Modules\Ai\Livewire\AiAdmin;
use App\Modules\Ai\Services\AiGateway;
use App\Modules\Cms\Services\SettingsRepository;
use App\Modules\I18n\Enums\TranslationStatus;
use App\Modules\I18n\Livewire\I18nManager;
use App\Modules\I18n\Models\Locale;
use App\Modules\I18n\Models\Translation;
use App\Support\Security\NotBreachedPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, SettingsSeeder::class, LocalesSeeder::class]);
    Locale::flushRegistry();
});

/* ── HIBP breached-password rule (reset path, k-anonymity) ─────────── */

function hibpRangeResponse(string $suffix, int $count): string
{
    return "{$suffix}:{$count}\nAAAA0000000000000000000000000000AAAA:1";
}

it('rejects passwords whose hash appears in the breach range', function () {
    $hash = strtoupper(sha1('breached-password'));
    $suffix = substr($hash, 5);

    Http::fake([
        'api.pwnedpasswords.com/*' => Http::response(hibpRangeResponse($suffix, 42)),
    ]);

    $messages = [];
    (new NotBreachedPassword)->validate()('password', 'breached-password', function ($m) use (&$messages): void {
        $messages[] = $m;
    });

    expect($messages)->not()->toBeEmpty()
        ->and($messages[0])->toContain('data breach');
});

it('accepts clean passwords and only ever sends the 5-char hash prefix', function () {
    Http::fake([
        'api.pwnedpasswords.com/*' => Http::response('AAAA0000000000000000000000000000AAAA:1', 200),
    ]);

    $messages = [];
    (new NotBreachedPassword)->validate()('password', 'a-completely-clean-passphrase', function ($m) use (&$messages): void {
        $messages[] = $m;
    });

    expect($messages)->toBeEmpty();

    // k-anonymity: exactly the 5-char prefix hit the API — never the hash.
    Http::assertSent(function ($request): bool {
        $prefix = strtoupper(substr(sha1('a-completely-clean-passphrase'), 0, 5));

        return str_ends_with($request->url(), '/range/'.substr($prefix, 0, 5))
            && ! str_contains($request->url(), strtoupper(sha1('a-completely-clean-passphrase')));
    });
});

it('fails open when HIBP is unreachable — a dependency outage never locks a user out', function () {
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 500)]);

    $messages = [];
    (new NotBreachedPassword)->validate()('password', 'any-password-goes', function ($m) use (&$messages): void {
        $messages[] = $m;
    });

    expect($messages)->toBeEmpty();
});

/* ── Admin hardening screens: authorization + behavior ────────────── */

it('guards the AI console to admin roles', function () {
    actingAsStaff(['editor'])->get('/admin/ai')->assertForbidden();
    actingAsStaff(['admin'])->get('/admin/ai')->assertOk();
});

it('guards the I18n screens to editor+', function () {
    actingAsStaff(['consultant'])->get('/admin/i18n')->assertForbidden();
    actingAsStaff(['editor'])->get('/admin/i18n')->assertOk();
});

it('guards the data-subject tool to admin roles', function () {
    actingAsStaff(['editor'])->get('/admin/privacy/data-subject')->assertForbidden();
    actingAsStaff(['admin'])->get('/admin/privacy/data-subject')->assertOk();
});

it('toggles the AI kill switch from the console and reflects it in the gateway', function () {
    config(['ai.enabled' => true]);
    actingAsStaff(['admin']);

    Livewire::test(AiAdmin::class)->call('toggleGlobalKill');

    $settings = app(SettingsRepository::class);

    expect($settings->get('ai.enabled'))->toBeFalse()
        ->and(AiGateway::globallyEnabled())->toBeFalse();
});

it('approves a machine UI string with reviewer attribution', function () {
    actingAsStaff(['editor'])->get('/admin/i18n')->assertOk();

    $string = Translation::factory()->forLocale('ja')->create([
        'namespace' => 'site',
        'status' => 'machine',
    ]);

    Livewire::test(I18nManager::class)
        ->call('approve', $string->id);

    $string->refresh();

    expect($string->status)->toBe(TranslationStatus::HumanReviewed)
        ->and($string->reviewed_by)->not()->toBeNull();
});
