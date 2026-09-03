<?php

/*
|--------------------------------------------------------------------------
| Application configuration (Laravel 13 slim style)
|--------------------------------------------------------------------------
| Production-first defaults: every value is env-overridable and the
| defaults match the locked production facts (public domain, IST clock,
| English UI). Local/staging values arrive via .env (see .env.example).
*/

return [

    'name' => env('APP_NAME', 'SEWA Hospitality'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    /*
     | Canonical public-site URL. The four serving hosts (site, admin, app,
     | api) are configured separately in config/sewa.php — one codebase
     | answers all of them (03-technical-specs/02-architecture.md §4).
     */
    'url' => env('APP_URL', 'https://sewahospitality.com'),

    // All scheduling, SLA clocks and report windows run on IST.
    'timezone' => env('APP_TIMEZONE', 'Asia/Kolkata'),

    /*
     | The application locale stays English; the other platform locales
     | (hi, ja, ko, tr, ar) are path-prefixed and managed by the I18n
     | module (04-modules/11-multilingual.md), never by swapping app locale.
     */
    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    // Indian-context fake data for seeders/tests (INR, +91 numbers, IN names).
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_IN'),

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(explode(',', (string) env('APP_PREVIOUS_KEYS', ''))),
    ],

    'maintenance' => [
        // The database store keeps maintenance mode authoritative on the
        // shared-hosting layout (06-hosting-deployment §3); tests use file.
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
