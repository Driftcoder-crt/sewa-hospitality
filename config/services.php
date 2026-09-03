<?php

/*
|--------------------------------------------------------------------------
| Third-party service credentials
|--------------------------------------------------------------------------
| Kept deliberately minimal. Resend is the only service with its own SDK
| (resend-laravel reads config('services.resend.key')).
|
| - Brevo (fallback mail provider, decided at M3) uses plain SMTP through
|   the MAIL_* environment variables — see the `smtp` / `failover` mailers
|   in config/mail.php. No service entry needed.
| - Cloudflare Turnstile keys live in config/sewa.php (turnstile.site_key /
|   turnstile.secret), next to its fail_mode policy.
| - Ably realtime credentials are read from env (ABLY_*) by
|   ably/laravel-broadcaster — no service entry needed.
*/

return [

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

];
