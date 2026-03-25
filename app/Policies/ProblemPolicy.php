<?php

namespace App\Policies;

use App\Models\Problem;
use App\Models\User;

class ProblemPolicy
{
    public function view(User $user, Problem $problem): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function confirm(User $user, Problem $problem): bool
    {
        return true;
    }

    public function resolve(User $user, Problem $problem): bool
    {
        return true;
    }

    public function unconfirm(User $user, Problem $problem): bool
    {
        return true;
    }

    public function unresolve(User $user, Problem $problem): bool
    {
        return true;
    }
}
