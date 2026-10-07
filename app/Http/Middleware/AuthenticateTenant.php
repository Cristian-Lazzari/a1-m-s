<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

/**
 * Le API /api/v1/* sono per i gestionali dei ristoranti: ognuno si presenta
 * con la sua chiave (Authorization: Bearer fp_...). Il ristorante trovato
 * finisce nella richiesta: nessun endpoint può agire su un ristorante diverso.
 */
class AuthenticateTenant
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = Tenant::findByApiKey($request->bearerToken());

        if (! $tenant) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
