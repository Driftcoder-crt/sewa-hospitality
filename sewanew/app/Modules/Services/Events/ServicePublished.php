<?php

namespace App\Modules\Services\Events;

use App\Modules\Services\Models\Service;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServicePublished
{
    use Dispatchable, SerializesModels;

    public Service $service;

    /**
     * Create a new event instance.
     */
    public function __construct(Service $service)
    {
        $this->service = $service;
    }
}
