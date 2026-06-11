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
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Delivery $delivery): bool
    {
        return true;
    }

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

    public function update(User $user, Delivery $delivery): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Delivery $delivery): bool
    {
        return $user->isAdmin();
    }
}
