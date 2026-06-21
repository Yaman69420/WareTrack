<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Routemiddleware (alias 'admin' in bootstrap/app.php) die volledige routes
 * afschermt voor niet-admins.
 *
 * Dit is de échte beveiliging, los van de UI: @can in Blade verbergt enkel
 * knoppen en links, maar wie de URL kent of zelf een request opstelt, raakt
 * daar gewoon voorbij. Deze middleware weigert zo'n request aan de serverkant
 * nog vóór de Livewire-component geladen wordt. Policies dekken daarnaast de
 * acties per model; deze middleware dekt hele admin-pagina's in één keer.
 */
class EnsureUserIsAdmin
{
    /**
     * Weigert iedereen die geen admin is met een 403. De null-check staat er
     * bewust: dit middleware mag niet stilzwijgend op 'auth' rekenen.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // abort(403) i.p.v. een redirect: een geweigerde actie is een fout, geen
        // navigatie — zo reageert dit ook correct op Livewire-AJAX-requests.
        // De vergelijking is strikt tegen de enum-case (role is gecast in User),
        // niet tegen de losse string 'admin': een tikfout faalt dan hard.
        if (! $request->user() || $request->user()->role !== UserRole::Admin) {
            abort(403);
        }

        return $next($request);
    }
}
