<?php

namespace App\Modules\Portal\Listeners;

use App\Modules\Portal\Enums\SenderRole;
use App\Modules\Portal\Events\MessageSent;
use App\Modules\Portal\Services\PortalNotificationCenter;
use App\Support\Queue\QueueHardenedListener;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * MessageSent (04 doc §7): the OTHER side of the thread gets a
 * notification — client messages notify the move's consultant;
 * consultant messages notify the employee (and org managers can find
 * threads in the admin inbox — no per-manager rows, one honest badge
 * lives with the assigned consultant).
 */
class NotifyMessageSent implements ShouldQueue
{
    use QueueHardenedListener;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600];

    public function __construct(private readonly PortalNotificationCenter $notifications) {}

    public function handle(MessageSent $event): void
    {
        $message = $event->message->refresh();
        $thread = $message->thread()->with(['move.consultant'])->first();

        if ($thread === null) {
            return;
        }

        $move = $thread->move;

        if ($message->sender_role === SenderRole::Client) {
            if ($move?->consultant !== null) {
                $this->notifications->notify(
                    userId: (string) $move->consultant->getKey(),
                    title: 'New message on '.($move->reference ?? 'a thread'),
                    body: mb_substr($message->body, 0, 120),
                    url: '/messages/'.$thread->getKey(),
                    kind: 'message',
                );
            }

            return;
        }

        if ($message->sender_role === SenderRole::Consultant && $move?->employee !== null) {
            $this->notifications->notify(
                userId: (string) $move->employee->getKey(),
                title: 'Your consultant replied',
                body: mb_substr($message->body, 0, 120),
                url: '/messages/'.$thread->getKey(),
                kind: 'message',
            );
        }
    }
}
