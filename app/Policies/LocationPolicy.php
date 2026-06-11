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
    /** Elke aangemelde gebruiker: zonder locatielijst kan niemand stock plaatsen of vinden. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Alleen admins: nieuwe locaties aanmaken verandert de fysieke magazijnindeling. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Alleen admins: een verkeerd aangepaste locatiecode maakt stock op de vloer onvindbaar. */
    public function update(User $user, Location $location): bool
    {
        return $user->isAdmin();
    }

    /** Alleen admins: een locatie verwijderen raakt alle stock en bewegingen die ernaar wijzen. */
    public function delete(User $user, Location $location): bool
    {
        return $user->isAdmin();
    }
}
