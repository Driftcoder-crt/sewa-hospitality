<?php

/*
|--------------------------------------------------------------------------
| Session configuration
|--------------------------------------------------------------------------
| Database sessions (locked decision, no Redis). The default cookie name is
| `sewa_session`; App\Http\Middleware\SetRequestContext overrides it at
| runtime per area (`sewa_admin_session` / `sewa_app_session`) so the admin
| cookie is never shared with the public site (05-security-reliability §1.1).
*/

return [

    'driver' => env('SESSION_DRIVER', 'database'),

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    'encrypt' => env('SESSION_ENCRYPT', false),

    'files' => storage_path('framework/sessions'),

    'connection' => env('SESSION_CONNECTION'),

    'table' => env('SESSION_TABLE', 'sessions'),

    'store' => env('SESSION_STORE'),

    'lottery' => [2, 100],

    // SetRequestContext narrows this per area at runtime (see above).
    'cookie' => env('SESSION_COOKIE', 'sewa_session'),

    'path' => env('SESSION_PATH', '/'),

    'domain' => env('SESSION_DOMAIN'),

    // Production sets SESSION_SECURE_COOKIE=true behind Cloudflare SSL.
    'secure' => env('SESSION_SECURE_COOKIE'),

    'http_only' => env('SESSION_HTTP_ONLY', true),

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

];
