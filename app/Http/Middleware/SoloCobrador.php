<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite el paso únicamente a usuarios con perfil Cobrador activo
 * O a vendedores con la bandera es_cobrador = true.
 */
class SoloCobrador
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->esCobrador()) {
            return response()->json([
                'message' => 'Acceso denegado. Esta acción es exclusiva para cobradores.',
            ], 403);
        }

        return $next($request);
    }
}
