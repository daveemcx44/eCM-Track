<?php

namespace App\Policies;

use App\Models\Resource;
use App\Models\User;

class ResourcePolicy
{
    public function view(User $user, Resource $resource): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }
}
