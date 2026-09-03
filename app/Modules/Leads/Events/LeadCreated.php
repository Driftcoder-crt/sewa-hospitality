<?php

namespace App\Modules\Leads\Events;

use App\Modules\Leads\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A lead was created (03-leads-crm §7). Listeners queue the ack email
 * to the lead + the ops/consultant notification — after commit, never
 * inside the write transaction.
 */
class LeadCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Lead $lead) {}
}
