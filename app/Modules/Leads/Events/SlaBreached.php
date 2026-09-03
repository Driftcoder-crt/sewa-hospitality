<?php

namespace App\Modules\Leads\Events;

use App\Modules\Leads\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An SLA deadline passed without first response (03-leads-crm §4.4).
 * Fired once per lead by sla:calculate; the listener queues the ops
 * alert ("SLA breach alert fires — simulated in the test suite").
 */
class SlaBreached
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Lead $lead) {}
}
