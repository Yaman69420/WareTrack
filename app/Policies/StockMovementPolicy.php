<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StockMovement $movement): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true; // both admin and warehouse_worker can register movements
    }

    public function update(User $user, StockMovement $movement): bool
    {
        return false; // immutable — never editable
    }

    public function delete(User $user, StockMovement $movement): bool
    {
        return false; // immutable — never deletable
    }
}
