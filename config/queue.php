<?php

/*
|--------------------------------------------------------------------------
| Queue configuration
|--------------------------------------------------------------------------
| Database queue is a locked decision (no Redis on Hostinger shared
| hosting). Queue names: default, emails, ai, syncs, exports
| (03-technical-specs/07-queues-scheduling.md §2). The single hPanel cron
| drains them in two 45s bursts per minute (routes/console.php).
*/

return [

    'default' => env('QUEUE_CONNECTION', 'database'),

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'), // null → default MySQL connection
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => env('QUEUE_QUEUE', 'default'),
            // Longest burst is 45s; 90s gives every job ample room to finish
            // before a second worker considers it abandoned.
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            // Spec: jobs dispatch after the DB commit — a rolled-back
            // request never leaves phantom jobs behind.
            'after_commit' => true,
        ],

    ],

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'job_batches',
    ],

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

];
