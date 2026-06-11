<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

/**
 * Autorisatieregels voor magazijnen (masterdata).
 *
 * Magazijnen raadplegen mag iedereen; de structuur wijzigen is admin-only.
 * Een magazijn hernoemen of verwijderen raakt alle onderliggende locaties
 * en hun stock, dus dat hoort niet bij de rechten van de werkvloer.
 */
class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->isAdmin();
    }
}
