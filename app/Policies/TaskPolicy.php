<?php

namespace App\Policies;

use App\Enums\TaskState;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function approve(User $user, Task $task): bool
    {
        if ($task->state !== TaskState::Added) {
            return false;
        }

        if (!$task->type->requiresApproval()) {
            return false;
        }

        return $user->role->canApproveTask();
    }

    public function start(User $user, Task $task): bool
    {
        return true;
    }

    public function complete(User $user, Task $task): bool
    {
        return true;
    }

    public function uncomplete(User $user, Task $task): bool
    {
        if ($task->state !== TaskState::Completed) {
            return false;
        }

        // Auto-completed tasks can only be uncompleted via reactivation (problem level)
        if (in_array($task->completion_type, [
            \App\Enums\TaskCompletionType::ProblemUnconfirmed,
            \App\Enums\TaskCompletionType::ProblemResolved,
        ])) {
            return false;
        }

        return $user->role->canUncompleteTask();
    }
}
