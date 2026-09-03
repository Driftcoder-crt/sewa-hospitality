<?php

/*
|--------------------------------------------------------------------------
| Cache configuration
|--------------------------------------------------------------------------
| The database cache store is a locked decision: Hostinger shared hosting
| has no Redis (06-hosting-deployment §1), so cache, queues, sessions and
| Pulse all run on MySQL. Shared-hosting CPU discipline (05-security-
| reliability §2.5) favours long-TTL entries with explicit flushes.
*/

return [

    'default' => env('CACHE_STORE', 'database'),

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'), // null → default MySQL connection
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => null, // same connection; locks live in cache_locks
            'lock_table' => env('DB_CACHE_LOCK_TABLE', 'cache_locks'),
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

    'prefix' => env('CACHE_PREFIX', 'sewa_cache'),

];
