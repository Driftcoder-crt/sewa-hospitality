<?php

/*
|--------------------------------------------------------------------------
| CORS configuration
|--------------------------------------------------------------------------
| Only the api. host serves cross-origin responses (03-technical-specs/
| 04-api-spec.md). The allowlist is explicit — sewahospitality.com plus the
| app./admin. subdomains; NO wildcard origins, ever (01-platform-vision/
| 04-subdomains-ventures.md). Credentials are supported for Sanctum SPA
| cookie authentication, which is exactly why the allowlist stays strict.
*/

return [

    'paths' => ['api/*', 'v1/*'],

    'allowed_origins' => array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', 'https://sewahospitality.com,https://app.sewahospitality.com,https://admin.sewahospitality.com')))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'allowed_methods' => ['*'],

    'exposed_headers' => [],

    // One hour of preflight caching; long enough to keep OPTIONS traffic
    // negligible, short enough to revoke quickly via config deploy.
    'max_age' => 3600,

    'supports_credentials' => true,

];
