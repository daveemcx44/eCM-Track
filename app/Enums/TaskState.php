<?php

namespace App\Enums;

enum TaskState: string
{
    case Added = 'added';
    case Approved = 'approved';
    case Started = 'started';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Added => 'Added',
            self::Approved => 'Approved',
            self::Started => 'Started',
            self::Completed => 'Completed',
        };
    }

    /**
     * Check if transition is valid.
     * Goals skip the Approved state (Added -> Started directly).
     */
    public function canTransitionTo(self $target, bool $isGoal = false): bool
    {
        return match ($this) {
            self::Added => $isGoal
                ? $target === self::Started
                : in_array($target, [self::Approved, self::Started]),
            self::Approved => $target === self::Started,
            self::Started => $target === self::Completed,
            self::Completed => $target === self::Started, // uncomplete
        };
    }
}
