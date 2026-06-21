<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

/**
 * Autorisatieregels voor leveranciers (masterdata).
 *
 * Leveranciersgegevens raadplegen mag iedereen — een magazijnier moet bij een
 * levering kunnen zien van wie ze komt. Beheren is admin-only: contact- en
 * contractgegevens zijn beheersdata, geen dagelijks magazijnwerk.
 */
class SupplierPolicy
{
    /** Elke aangemelde gebruiker: bij een levering moet zichtbaar zijn van wie ze komt. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Elke aangemelde gebruiker: contactgegevens raadplegen hoort bij het ontvangstwerk. */
    public function view(User $user, Supplier $supplier): bool
    {
        return true;
    }

    /** Alleen admins: leveranciers aanmaken is beheer van contract- en contactdata. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Alleen admins: foute leveranciersdata vervuilt alle toekomstige leveringen. */
    public function update(User $user, Supplier $supplier): bool
    {
        return $user->isAdmin();
    }

    /** Alleen admins: een leverancier verwijderen raakt de historiek van zijn leveringen. */
    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->isAdmin();
    }
}
