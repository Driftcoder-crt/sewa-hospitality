<?php

use App\Providers\AppServiceProvider;
use App\Providers\MediaServiceProvider;

return [
    AppServiceProvider::class,
    // Media pipeline wiring (EXIF-strip listener, 09-media-pipeline §7/§8).
    MediaServiceProvider::class,
];
