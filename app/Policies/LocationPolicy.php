<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

/**
 * Autorisatieregels voor magazijnlocaties (masterdata).
 *
 * Iedereen mag locaties raadplegen — elke stockbeweging verwijst ernaar.
 * De indeling van het magazijn wijzigen is admin-only: een fout aangepaste
 * locatie breekt de fysieke vindbaarheid van de stock op de vloer.
 */
class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Location $location): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Location $location): bool
    {
        return $user->isAdmin();
    }
}
