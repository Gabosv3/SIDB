<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Las respuestas de error de la API traen el texto en "mensaje", pero la app
 * móvil lee "message" (así lo hace el interceptor de services/api.js), y
 * mostraba un genérico "Error 403" en vez de la explicación. Esto copia el
 * texto a "message" cuando falta, sin tocar nada más — así las apps ya
 * instaladas muestran el motivo real sin necesidad de compilar de nuevo.
 */
class CopiarMensajeAMessage
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof JsonResponse && $response->getStatusCode() >= 400) {
            $datos = $response->getData(true);

            if (
                is_array($datos)
                && isset($datos['mensaje'])
                && is_string($datos['mensaje'])
                && ! array_key_exists('message', $datos)
            ) {
                $datos['message'] = $datos['mensaje'];
                $response->setData($datos);
            }
        }

        return $response;
    }
}
