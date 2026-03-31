<?php

namespace App\Enums;

enum NotificationEventType: string
{
    case ProblemAdded = 'problem_added';
    case ProblemConfirmed = 'problem_confirmed';
    case ProblemUnconfirmed = 'problem_unconfirmed';
    case ProblemResolved = 'problem_resolved';
    case ProblemUnresolved = 'problem_unresolved';
    case TaskAdded = 'task_added';
    case TaskStarted = 'task_started';
    case TaskCompleted = 'task_completed';
    case TaskUncompleted = 'task_uncompleted';
    case ResourceAdded = 'resource_added';
    case NoteAdded = 'note_added';
    case OutreachLogged = 'outreach_logged';

    public function label(): string
    {
        return match ($this) {
            self::ProblemAdded => 'Problem: Adding',
            self::ProblemConfirmed => 'Problem: Confirm',
            self::ProblemUnconfirmed => 'Problem: Unconfirm',
            self::ProblemResolved => 'Problem: Resolve',
            self::ProblemUnresolved => 'Problem: Unresolve',
            self::TaskAdded => 'Task: Adding',
            self::TaskStarted => 'Task: Start',
            self::TaskCompleted => 'Task: Complete',
            self::TaskUncompleted => 'Task: Uncomplete',
            self::ResourceAdded => 'Resource: Adding',
            self::NoteAdded => 'Note: Adding',
            self::OutreachLogged => 'Outreach: Logged',
        };
    }
}
