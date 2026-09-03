<?php

namespace App\Modules\Portal\Listeners;

use App\Modules\Portal\Events\MoveStageChanged;
use App\Modules\Portal\Mail\MoveStageMail;
use App\Modules\Portal\Services\PortalAudience;
use App\Modules\Portal\Services\PortalNotificationCenter;
use App\Modules\Testimonials\Services\ReviewRequestEngine;
use App\Support\Mail\Jobs\SendTemplateMail;
use App\Support\Queue\QueueHardenedListener;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * MoveStageChanged (04 doc §5/§7): stage email to employee + managers,
 * notification-center rows, and — on COMPLETE only — the review-request
 * engine fires (08 doc §4.3: one chain per move, idempotent by
 * move_reference; the engine owns all invariants).
 */
class NotifyMoveStageChange implements ShouldQueue
{
    use QueueHardenedListener;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600];

    public function __construct(private readonly PortalNotificationCenter $notifications) {}

    public function handle(MoveStageChanged $event): void
    {
        $move = $event->move->refresh();
        $toLabel = $event->to->label();

        $recipients = PortalAudience::moveRecipients($move);

        foreach ($recipients as $recipient) {
            SendTemplateMail::dispatch(
                key: "move.stage:{$move->getKey()}:{$event->to->value}:{$recipient['email']}",
                template: 'move.stage_changed',
                mailable: new MoveStageMail(
                    move: $move,
                    fromLabel: $event->from->label(),
                    toLabel: $toLabel,
                    whatsNext: PortalAudience::whatsNext($event->to->value),
                    portalUrl: route('portal.moves.show', $move),
                    recipientName: $recipient['name'],
                    recipientEmail: $recipient['email'],
                ),
            );

            if ($recipient['user'] !== null) {
                $this->notifications->notify(
                    userId: (string) $recipient['user']->getKey(),
                    title: 'Move '.$move->reference.' is now '.$toLabel,
                    body: PortalAudience::whatsNext($event->to->value),
                    url: '/moves/'.$move->getKey(),
                    kind: 'stage',
                );
            }
        }

        // Completion triggers the review request — the engine is
        // idempotent (one chain per move, UNIQUE move_reference).
        if ($event->to->value === 'complete' && $move->employee !== null) {
            app(ReviewRequestEngine::class)
                ->requestFor((string) $move->reference, $move->employee->email, $move->employee->name);
        }
    }
}
