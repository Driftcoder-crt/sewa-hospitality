<?php

namespace App\Modules\Portal\Listeners;

use App\Modules\Portal\Events\ChecklistItemDone;
use App\Modules\Portal\Services\PortalNotificationCenter;
use App\Support\Queue\QueueHardenedListener;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * ChecklistItemDone (04 doc §7): the assigned consultant learns the
 * task is done (portal-side or admin-side completion both funnel here).
 */
class NotifyChecklistDone implements ShouldQueue
{
    use QueueHardenedListener;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600];

    public function __construct(private readonly PortalNotificationCenter $notifications) {}

    public function handle(ChecklistItemDone $event): void
    {
        $item = $event->item->refresh();
        $move = $item->move()->with('consultant')->first();

        if ($move?->consultant === null) {
            return;
        }

        $this->notifications->notify(
            userId: (string) $move->consultant->getKey(),
            title: 'Task done on '.$move->reference,
            body: $item->title,
            url: '/moves/'.$move->getKey(),
            kind: 'checklist',
        );
    }
}
