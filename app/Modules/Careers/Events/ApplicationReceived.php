<?php

namespace App\Modules\Careers\Events;

use App\Modules\Careers\Models\JobApplication;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A candidate applied (06-hr §7) — ack to candidate + notify careers@. */
class ApplicationReceived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly JobApplication $application) {}
}
