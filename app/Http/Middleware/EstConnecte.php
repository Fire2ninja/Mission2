<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EstConnecte
{
    /**
     * Laisse passer seulement si le profil demandé ('visiteur' ou 'gestionnaire')
     * est présent en session, sinon renvoie vers la page de connexion.
     */
    public function handle(Request $request, Closure $next, string $profil): Response
    {
        if (session($profil) == null) {
            return redirect()->route('chemin_connexion');
        }
        return $next($request);
    }
}
