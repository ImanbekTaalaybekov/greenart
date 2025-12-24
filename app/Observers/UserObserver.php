<?php

namespace App\Observers;

use App\Models\Chat;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        if ($user->hasRole(User::ROLE_WORKER, User::ROLE_ADMIN)) {
            
            $generalChat = Chat::firstOrCreate(
                ['type' => 'general'],
                ['name' => 'Общий чат (Офис + Садовники)']
            );

            if (!$generalChat->participants()->where('user_id', $user->id)->exists()) {
                $generalChat->participants()->attach($user->id);
            }
        }
    }
}