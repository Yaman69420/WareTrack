<?php

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Logt de gebruiker uit. Invokeable action uit de Livewire starter kit,
 * aangeroepen vanuit de navigatie.
 */
class Logout
{
    /**
     * Voert de logout uit: beëindigt de sessie veilig (zie inline toelichting)
     * en stuurt de bezoeker terug naar de publieke startpagina.
     */
    public function __invoke()
    {
        Auth::guard('web')->logout();

        // invalidate() wist de sessiedata én geeft een nieuw sessie-ID;
        // regenerateToken() vernieuwt het CSRF-token. Samen voorkomen ze dat een
        // oud sessie-ID of token na het uitloggen nog bruikbaar is (session fixation).
        Session::invalidate();
        Session::regenerateToken();

        return redirect('/');
    }
}
