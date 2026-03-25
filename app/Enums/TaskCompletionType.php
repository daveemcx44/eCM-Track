<?php

namespace App\Enums;

enum TaskCompletionType: string
{
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Terminated = 'terminated';
    case ProblemUnconfirmed = 'problem_unconfirmed';
    case ProblemResolved = 'problem_resolved';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Complete – Task completed',
            self::Cancelled => 'Complete – Task cancelled',
            self::Terminated => 'Complete – Task terminated',
            self::ProblemUnconfirmed => 'Complete – Problem Unconfirmed',
            self::ProblemResolved => 'Complete – Problem Resolved',
        };
    }
}
