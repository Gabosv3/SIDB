<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite el paso únicamente a usuarios con un perfil Vendedor activo.
 * (Los vendedores con es_cobrador=true también pasan, pues siguen siendo vendedores.)
 */
class SoloVendedor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->esVendedor()) {
            return response()->json([
                'message' => 'Acceso denegado. Esta acción es exclusiva para vendedores.',
            ], 403);
        }

        return $next($request);
    }
}
