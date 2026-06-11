<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

/**
 * Autorisatieregels voor productcategorieën (masterdata).
 *
 * Raadplegen mag elke aangemelde gebruiker: magazijniers hebben categorieën
 * nodig om producten terug te vinden. Beheren is admin-only, want wijzigingen
 * aan masterdata raken meteen de volledige catalogus en de rapportering.
 */
class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }
}
