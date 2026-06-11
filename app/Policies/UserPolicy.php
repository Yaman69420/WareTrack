<?php

namespace App\Policies;

use App\Models\User;

/**
 * Autorisatieregels voor gebruikersbeheer.
 *
 * Volledig admin-only — zelfs de gebruikerslijst (viewAny), want wie rollen
 * kan toekennen, bezit indirect álle andere rechten in het systeem. De enige
 * uitzondering op het patroon zit in delete: een admin kan zichzelf niet
 * verwijderen.
 */
class UserPolicy
{
    /** Alleen admins: de gebruikerslijst toont e-mailadressen en rollen van collega's. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Alleen admins: wie accounts aanmaakt, bepaalt wie toegang krijgt tot het systeem. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Alleen admins: via update wijzig je ook rollen, dus indirect álle rechten. */
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Zelf-verwijdering is geblokkeerd: zo vergrendel je je eigen account niet
     * uit een lopende sessie én kan de laatste admin het systeem nooit zonder
     * beheerder achterlaten — er blijft altijd minstens één admin over.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->id !== $model->id;
    }
}
