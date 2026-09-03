<?php

namespace App\Modules\Careers\Events;

use App\Modules\Careers\Enums\ApplicationStatus;
use App\Modules\Careers\Models\JobApplication;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** ATS stage changed (06-hr §7) — status email per the catalog. */
class ApplicationStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly JobApplication $application,
        public readonly ApplicationStatus $from,
        public readonly ApplicationStatus $to,
    ) {}
}
