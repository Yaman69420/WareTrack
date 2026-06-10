<?php

namespace App\Policies;

use App\Models\Delivery;
use App\Models\User;

class DeliveryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Delivery $delivery): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Receiving goods is day-to-day warehouse work — both roles may process,
     * unlike create/update/delete which are admin-only masterdata actions.
     */
    public function process(User $user, Delivery $delivery): bool
    {
        return true;
    }

    public function update(User $user, Delivery $delivery): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Delivery $delivery): bool
    {
        return $user->isAdmin();
    }
}
