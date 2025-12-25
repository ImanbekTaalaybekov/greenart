<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true; 
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->hasRole(User::ROLE_ADMIN, User::ROLE_ACCOUNTANT)) {
            return true;
        }

        if ($user->hasRole(User::ROLE_WORKER) && $order->worker_id === $user->id) {
            return true;
        }

        if ($user->hasRole(User::ROLE_CLIENT) && $order->client_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    public function update(User $user, Order $order): bool
    {
        if ($user->hasRole(User::ROLE_ADMIN, User::ROLE_ACCOUNTANT)) {
            return true;
        }
        return false;
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    public function assignWorker(User $user, Order $order): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    public function classify(User $user, Order $order): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }
}