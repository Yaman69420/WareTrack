<?php

namespace App\Policies;

use App\Models\Delivery;
use App\Models\User;

/**
 * Autorisatieregels voor leveringen.
 *
 * Splitst beheer van uitvoering: leveringen aanmaken, wijzigen of verwijderen
 * is admin-only (planning en masterdata), maar het effectief ontvangen van de
 * goederen (process) is dagelijks magazijnwerk en staat open voor beide rollen.
 */
class DeliveryPolicy
{
    /** Elke aangemelde gebruiker: het leveringsoverzicht stuurt het dagelijkse werk aan. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Elke aangemelde gebruiker: detailgegevens zijn nodig om de levering te kunnen verwerken. */
    public function view(User $user, Delivery $delivery): bool
    {
        return true;
    }

    /** Alleen admins: leveringen inplannen is beheer, geen uitvoerend magazijnwerk. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Goederen ontvangen is dagelijks magazijnwerk: beide rollen mogen een
     * levering verwerken, anders staat de werkvloer stil tot een admin tijd
     * heeft. Dit is een custom ability naast de standaard CRUD-methodes.
     */
    public function process(User $user, Delivery $delivery): bool
    {
        return true;
    }

    /** Alleen admins: een geplande levering aanpassen hoort bij planning, niet bij uitvoering. */
    public function update(User $user, Delivery $delivery): bool
    {
        return $user->isAdmin();
    }

    /** Alleen admins: een levering schrappen wist ook de bestelcontext van de regels. */
    public function delete(User $user, Delivery $delivery): bool
    {
        return $user->isAdmin();
    }
}
