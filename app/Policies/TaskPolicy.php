<?php

namespace App\Policies;

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
        return true;
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
        return true;
    }
}
