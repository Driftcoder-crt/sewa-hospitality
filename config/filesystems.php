<?php

/*
|--------------------------------------------------------------------------
| Filesystem configuration
|--------------------------------------------------------------------------
| `local` holds private app files (resumes land in media, not here).
| `public` is the media disk (MEDIA_DISK default in config/media-library.php);
| on the host it is exposed through the media. subdomain / storage symlink
| with immutable cache headers (03-technical-specs/09-media-pipeline.md §3).
| `s3` is dormant Phase-2 capacity (09-delivery/02-future-scaling.md) —
| env keys only, nothing uses it at launch.
*/

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL', 'https://sewahospitality.com').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
