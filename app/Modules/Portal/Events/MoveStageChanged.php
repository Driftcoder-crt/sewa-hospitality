<?php

namespace App\Modules\Portal\Events;

use App\Modules\Portal\Enums\MoveStage;
use App\Modules\Portal\Models\PortalMove;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by MoveStageMachine on every legal transition (04 doc §7):
 * email + notification + review-request on completion; realtime
 * broadcast with wire:poll fallback (11-realtime §3).
 */
class MoveStageChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly PortalMove $move,
        public readonly MoveStage $from,
        public readonly MoveStage $to,
    ) {}
}
