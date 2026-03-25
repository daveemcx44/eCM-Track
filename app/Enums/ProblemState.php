<?php

namespace App\Enums;

enum ProblemState: string
{
    case Added = 'added';
    case Confirmed = 'confirmed';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Added => 'Added',
            self::Confirmed => 'Confirmed',
            self::Resolved => 'Resolved',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Added => $target === self::Confirmed,
            self::Confirmed => in_array($target, [self::Resolved, self::Added]),
            self::Resolved => $target === self::Confirmed,
        };
    }
}
