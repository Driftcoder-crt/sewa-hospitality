<?php

namespace App\Modules\Services\Events;

use App\Modules\Services\Models\Service;

/** ServiceUpdated (event catalog): content or coverage changed. */
class ServiceUpdated
{
    public function __construct(public readonly Service $service) {}
}
