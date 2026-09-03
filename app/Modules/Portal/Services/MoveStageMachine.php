<?php

namespace App\Modules\Portal\Services;

use App\Models\User;
use App\Modules\Portal\Enums\MoveStage;
use App\Modules\Portal\Events\MoveStageChanged;
use App\Modules\Portal\Models\PortalMove;
use App\Support\Audit\ActivityLogger;
use InvalidArgumentException;

/**
 * Move stage machine (04-client-portal.md §4/§5): linear pipeline
 * intake → planning → in-progress → settling → complete → closed.
 * Transitions publish MoveStageChanged — the event that drives email,
 * notifications, the review-request engine (complete) and realtime.
 */
class MoveStageMachine
{
    /** Allowed forward transitions (terminal stages accept nothing). */
    private const TRANSITIONS = [
        MoveStage::Intake->value => [MoveStage::Planning],
        MoveStage::Planning->value => [MoveStage::InProgress],
        MoveStage::InProgress->value => [MoveStage::Settling],
        MoveStage::Settling->value => [MoveStage::Complete],
        MoveStage::Complete->value => [MoveStage::Closed],
        MoveStage::Closed->value => [],
    ];

    /** @return list<MoveStage> */
    public function allowedTargets(PortalMove $move): array
    {
        $current = $move->stage;

        if ($current === null || $move->status->value === 'cancelled') {
            return [];
        }

        return self::TRANSITIONS[$current->value];
    }

    public function canTransition(PortalMove $move, MoveStage $target): bool
    {
        return in_array($target, $this->allowedTargets($move), true);
    }

    /** Guard + apply + record + fire. Throws on illegal transitions. */
    public function transition(PortalMove $move, MoveStage $target, ?User $actor = null): PortalMove
    {
        $from = $move->stage;

        if ($from === null || ! $this->canTransition($move, $target)) {
            throw new InvalidArgumentException(
                "Illegal move stage transition [{$from?->value} → {$target->value}].",
            );
        }

        $move->stage = $target;
        $move->save();

        ActivityLogger::log('admin', 'stage-change', $move, ['from' => $from->value, 'to' => $target->value]);

        MoveStageChanged::dispatch($move, $from, $target);

        return $move;
    }
}
