<?php

namespace App\Modules\Leads\Events;

use App\Modules\Leads\Enums\LeadStatus;
use App\Modules\Leads\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Pipeline status changed (03-leads-crm §7) — audit + downstream hooks. */
class LeadStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly LeadStatus $from,
        public readonly LeadStatus $to,
    ) {}
}
