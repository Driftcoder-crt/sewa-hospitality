<?php

namespace App\Modules\Portal\Events;

use App\Modules\Portal\Models\PortalMessage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Two-way chat (04 doc §7): notifications + realtime broadcast. */
class MessageSent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly PortalMessage $message) {}
}
