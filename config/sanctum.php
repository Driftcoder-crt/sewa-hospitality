<?php

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;

/*
|--------------------------------------------------------------------------
| Sanctum configuration
|--------------------------------------------------------------------------
| The api. host issues Sanctum tokens (device-bound, 90-day default stamped
| at issuance — 04-modules/13-mobile-readiness.md; 240/min/token limit is
| enforced by the api rate limiter). SPA-cookie statefulness below lets the
| admin/portal frontends authenticate over their own hosts without storing
| tokens in JavaScript.
*/

return [

    /*
     | First-party hosts allowed to authenticate via SPA cookie state.
     | SANCTUM_STATEFUL_DOMAINS is a comma list; the APP_URL host is always
     | appended so local/staging single-host setups work out of the box
     | (port, if any, must go into SANCTUM_STATEFUL_DOMAINS explicitly).
     */
    'stateful' => array_values(array_filter(array_merge(
        array_map('trim', explode(',', (string) env('SANCTUM_STATEFUL_DOMAINS', ''))),
        [parse_url((string) env('APP_URL', 'https://sewahospitality.com'), PHP_URL_HOST) ?: 'sewahospitality.com'],
    ))),

    'guard' => ['web'],

    /*
     | null = no global token expiry; the 90-day device-token lifetime is
     | stamped per token at issuance in the portal/mobile auth milestone.
     | SANCTUM_EXPIRATION (minutes) is an ops escape hatch for a global
     | idle expiry.
     */
    'expiration' => env('SANCTUM_EXPIRATION') !== null ? (int) env('SANCTUM_EXPIRATION') : null,

    'token_prefix' => 'sewa_',

    'middleware' => [
        'authenticate_session' => AuthenticateSession::class,
        // Standard Illuminate middleware (NOT the Sanctum-local copies).
        'encrypt_cookies' => EncryptCookies::class,
        'validate_csrf_token' => ValidateCsrfToken::class,
    ],

];
