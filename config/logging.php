<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;

/*
|--------------------------------------------------------------------------
| Logging configuration
|--------------------------------------------------------------------------
| Default stack writes to the rotating app log AND the ops log. The `ops`
| channel (storage/logs/ops.log, 30-day retention) is the alert surface
| consumed by the ops digest / monitoring milestone: circuit breakers
| (Support\Locks\CircuitBreaker), backups:verify, mail failovers and
| scheduler heartbeats all log here (03-technical-specs/12-monitoring.md).
*/

return [

    'default' => env('LOG_CHANNEL', 'stack'),

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', (string) env('LOG_STACK_CHANNELS', 'single,ops')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'replace_placeholders' => true,
        ],

        /*
         | The ops alert channel: ops-level events (breaker opens, backup
         | failures, queue staleness, mail provider fallback) land here with
         | 30-day retention so the nightly ops digest can sweep them.
         */
        'ops' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ops.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 30,
            'replace_placeholders' => true,
        ],

        /*
         | Sink for the `log` mailer (config/mail.php) so test/staging email
         | is inspectable in its own file instead of laravel.log.
         */
        'mail' => [
            'driver' => 'daily',
            'path' => storage_path('logs/mail.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 7,
            'replace_placeholders' => true,
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

    ],

];
