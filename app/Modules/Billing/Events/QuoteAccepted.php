<?php

namespace App\Modules\Billing\Events;

use App\Modules\Billing\Models\Quote;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Quote acceptance → invoice draft + CRM status auto-update (12 doc §7). */
class QuoteAccepted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Quote $quote) {}
}
