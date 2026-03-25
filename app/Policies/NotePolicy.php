<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    public function create(User $user): bool
    {
        return true;
    }
}
