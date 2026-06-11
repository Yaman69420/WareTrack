<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Autorisatieregels voor producten (masterdata).
 *
 * Raadplegen mag elke aangemelde gebruiker; gasten komen hier nooit, want
 * Laravel weigert een policy-methode met een niet-nullable User-parameter
 * automatisch voor niet-ingelogde bezoekers. Productbeheer is admin-only:
 * een fout in SKU of naam plant zich voort in elke beweging en levering.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }
}
