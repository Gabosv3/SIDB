<?php

namespace App\Services;

use App\Models\IdempotencyRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

/**
 * Protege endpoints contra reintentos offline que crean registros duplicados
 * (venta doblada, cobro doblado) cuando el servidor SÍ procesó el request
 * pero la respuesta se perdió antes de llegar al teléfono (timeout, señal
 * cortada a medio camino). El teléfono no tiene forma de distinguir ese caso
 * de un request que nunca llegó, así que ambos casos se tratan igual del
 * lado de la app: se reintenta con la MISMA clave.
 *
 * Uso: el cliente manda "idempotency_key" (generada una sola vez, antes del
 * primer intento, y reutilizada en los reintentos). Si la clave ya se vio
 * para ese usuario+endpoint, se devuelve la respuesta guardada la primera
 * vez en vez de repetir la operación.
 */
class IdempotencyService
{
    public static function manejar(int $userId, string $endpoint, ?string $idempotencyKey, callable $operacion): JsonResponse
    {
        if (! $idempotencyKey) {
            return $operacion();
        }

        try {
            $registro = IdempotencyRequest::create([
                'user_id'         => $userId,
                'endpoint'        => $endpoint,
                'idempotency_key' => $idempotencyKey,
            ]);
        } catch (QueryException $e) {
            $existente = IdempotencyRequest::where('user_id', $userId)
                ->where('endpoint', $endpoint)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existente && $existente->response_body !== null) {
                return response()->json($existente->response_body, $existente->response_status);
            }

            // La primera vez sigue en curso (o se cayó a medias sin guardar
            // respuesta) — no hay nada seguro que devolver todavía.
            return response()->json([
                'mensaje' => 'Esta operación ya se está procesando. Espera unos segundos y vuelve a intentar si no ves el resultado.',
            ], 409);
        }

        try {
            $respuesta = $operacion();
        } catch (\Throwable $e) {
            // Si la operación falla (ej. validación, sin asignación activa),
            // no hay nada válido que cachear — se borra el marcador para que
            // un reintento con la misma clave pueda volver a intentarlo en
            // vez de quedar atrapado viendo "ya se está procesando" para
            // siempre.
            $registro->delete();
            throw $e;
        }

        $registro->update([
            'response_status' => $respuesta->getStatusCode(),
            'response_body'   => $respuesta->getData(true),
        ]);

        return $respuesta;
    }
}
