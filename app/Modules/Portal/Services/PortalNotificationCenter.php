<?php

namespace App\Modules\Portal\Services;

use App\Modules\Portal\Events\NotificationCreated;
use App\Modules\Portal\Models\PortalNotification;

/**
 * The only writer for portal notifications (04 doc §3/§7): every
 * notification (stage change, published document, reply, invoice)
 * goes through here so the NotificationCreated event — the realtime
 * contract — always fires with the row.
 */
class PortalNotificationCenter
{
    public function notify(
        string $userId,
        string $title,
        string $body = '',
        string $url = '',
        string $kind = 'general',
    ): PortalNotification {
        $notification = PortalNotification::query()->create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'kind' => $kind,
        ]);

        NotificationCreated::dispatch($userId, $title, $body, $url);

        return $notification;
    }
}
