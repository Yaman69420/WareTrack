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
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->isAdmin();
    }
}
