<?php

namespace App\Modules\Portal\Enums;

/**
 * Move stage machine states (04-client-portal.md §4 + schema §8):
 * intake → planning → in-progress → settling → complete → closed.
 * Transitions enforced by App\Modules\Portal\Services\MoveStageMachine.
 */
enum MoveStage: string
{
    case Intake = 'intake';
    case Planning = 'planning';
    case InProgress = 'in-progress';
    case Settling = 'settling';
    case Complete = 'complete';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Intake => 'Intake',
            self::Planning => 'Planning',
            self::InProgress => 'In progress',
            self::Settling => 'Settling in',
            self::Complete => 'Complete',
            self::Closed => 'Closed',
        };
    }

    /** Ordered stages for timeline progress rendering (portal /moves). */
    /** @return list<self> */
    public static function pipeline(): array
    {
        return [self::Intake, self::Planning, self::InProgress, self::Settling, self::Complete, self::Closed];
    }

    public function position(): int
    {
        return array_search($this, self::pipeline(), true);
    }

    public function isTerminal(): bool
    {
        return $this === self::Closed;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
