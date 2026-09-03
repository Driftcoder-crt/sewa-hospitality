<?php

namespace App\Modules\Portal\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Portal notification center payload (04 doc §3/§7): realtime badge +
 * mark-read list. The portal_notifications table ships with the
 * notifications surface (M5-b); this event is the contract other
 * modules listen for.
 */
class NotificationCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly string $title,
        public readonly string $body = '',
        public readonly string $url = '',
    ) {}
}
