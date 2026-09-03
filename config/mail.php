<?php

/*
|--------------------------------------------------------------------------
| Mail configuration
|--------------------------------------------------------------------------
| Local/default transport is the log mailer — nothing accidental is ever
| sent. At M3 the provider decision lands (Resend primary, Brevo fallback):
| Resend is wired by the resend-laravel SDK (config/services.php), Brevo is
| plain SMTP through the `smtp` mailer below. `failover` is the locked
| provider → Hostinger-SMTP fallback chain (03-technical-specs/10-email.md).
*/

return [

    'default' => env('MAIL_MAILER', 'log'),

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            // Shared-hosting SMTP is slow; never hold a request longer.
            'timeout' => 15,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'https://sewahospitality.com'), PHP_URL_HOST) ?: 'sewahospitality.com'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL', 'mail'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            // Provider first, log sink last so a total SMTP outage degrades
            // to an inspectable trail instead of silent loss (breaker +
            // SEV-2 alerting attach at M3).
            'mailers' => ['smtp', 'log'],
        ],

    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@sewahospitality.com'),
        'name' => env('MAIL_FROM_NAME', 'SEWA Hospitality'),
    ],

];
