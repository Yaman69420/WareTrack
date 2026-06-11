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
    /** Elke aangemelde gebruiker: het magazijnoverzicht is nodig om stock te situeren. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Alleen admins: een nieuw magazijn opzetten is een structurele beheersbeslissing. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Alleen admins: een magazijn hernoemen raakt alle onderliggende locaties en stock. */
    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->isAdmin();
    }

    /** Alleen admins: verwijderen trekt de volledige locatiestructuur eronder mee weg. */
    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->isAdmin();
    }
}
