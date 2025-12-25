<?php

namespace App\Policies;

use App\Models\User;

class ChatPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }
}