<?php

namespace App\Modules\Portal\Events;

use App\Modules\Portal\Models\PortalChecklistItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Task completion from admin or portal side (04 doc §4.2/§7). */
class ChecklistItemDone
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly PortalChecklistItem $item) {}
}
