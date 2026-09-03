<?php

/*
|--------------------------------------------------------------------------
| Security header contract test (05-security-reliability.md §1.3, §5 gate 4)
|--------------------------------------------------------------------------
| SecurityHeaders appends to the whole web middleware group, so the header
| snapshot is taken on the real home response. RefreshDatabase brings up
| the settings table the app layout's NAP footer reads; when the frontend
| agent's pages.home placeholder is later replaced by the CMS tree the
| same assertions must keep holding on a 200 page.
*/

use Database\Seeders\CmsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    // The home route is CMS-composed: without the pages tree the response
    // is a 404 and the header snapshot would be taken on an error page.
    $this->seed([SettingsSeeder::class, CmsSeeder::class]);
});

test('web responses carry the security header contract', function (): void {
    // No built asset manifest in CI — layouts render without @vite output.
    $response = $this->withoutVite()->get('/')->assertOk();

    $csp = (string) $response->headers->get('Content-Security-Policy');
    expect($csp)->not->toBeEmpty()
        ->and($csp)->toContain('default-src');

    $response
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    $permissionsPolicy = (string) $response->headers->get('Permissions-Policy');
    expect($permissionsPolicy)->toContain('camera=()');
});
