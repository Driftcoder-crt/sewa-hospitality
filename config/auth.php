<?php

use App\Models\User;

/*
|--------------------------------------------------------------------------
| Authentication configuration
|--------------------------------------------------------------------------
| One guard for the whole platform: staff (admin surface) and clients
| (portal) are both App\Models\User rows separated by Spatie roles, not
| by separate guards (03-technical-specs/02-architecture.md §3). The API
| surface authenticates the same users via Sanctum (config/sanctum.php).
*/

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',

            // Reset links live for 60 minutes (05-security-reliability §1.2).
            // `throttle` spaces out repeat requests per email (seconds); the
            // spec's 3/hour/email cap is layered on by the auth controllers.
            'expire' => 60,

            'throttle' => 60,
        ],
    ],

    // Seconds after which other sessions must re-confirm the password.
    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
