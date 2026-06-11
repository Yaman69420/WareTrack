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
    /** Elke aangemelde gebruiker: de productlijst is het startpunt van elk magazijnproces. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Elke aangemelde gebruiker: productdetails zijn nodig bij ontvangst en picking. */
    public function view(User $user, Product $product): bool
    {
        return true;
    }

    /** Alleen admins: een nieuw product is masterdata waarop alle bewegingen voortbouwen. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Alleen admins: een foute SKU of naam plant zich voort in elke beweging en levering. */
    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    /** Alleen admins: verwijderen raakt de stockhistoriek en alle gekoppelde leveringsregels. */
    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }
}
