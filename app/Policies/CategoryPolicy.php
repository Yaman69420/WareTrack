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
    /** Elke aangemelde gebruiker: categorieën zijn nodig om producten op te zoeken. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Alleen admins: een nieuwe categorie verandert de indeling van de hele catalogus. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Alleen admins: een hernoemde categorie wijzigt meteen alle gekoppelde producten. */
    public function update(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }

    /** Alleen admins: verwijderen raakt elk product dat in deze categorie zit. */
    public function delete(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }
}
