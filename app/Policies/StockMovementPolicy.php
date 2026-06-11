<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

/**
 * Autorisatieregels voor stockbewegingen — het audit-trail van het systeem.
 *
 * Registreren mag elke rol: stock boeken is de kerntaak van het magazijn.
 * Maar eenmaal geboekt is een beweging onveranderlijk: update en delete geven
 * hard `false`, ook voor admins. Een fout wordt rechtgezet met een nieuwe
 * correctiebeweging (StockMovementType::Correction), zodat de historiek
 * volledig en controleerbaar blijft. Er is bewust geen Gate::before-bypass
 * die dit voor admins zou omzeilen.
 */
class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StockMovement $movement): bool
    {
        return true;
    }

    /**
     * Beide rollen mogen bewegingen registreren: dit is de dagelijkse kernflow.
     * Admin-only maken zou de werkvloer blokkeren bij elke ontvangst of picking.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Hard geweigerd, ook voor admins: een audit-trail dat achteraf aanpasbaar
     * is, bewijst niets. Fouten worden rechtgezet met een correctiebeweging.
     */
    public function update(User $user, StockMovement $movement): bool
    {
        return false;
    }

    /**
     * Hard geweigerd, ook voor admins: wissen zou een gat in de historiek slaan
     * en het saldo onverklaarbaar maken. De boeking blijft, fout of niet.
     */
    public function delete(User $user, StockMovement $movement): bool
    {
        return false;
    }
}
