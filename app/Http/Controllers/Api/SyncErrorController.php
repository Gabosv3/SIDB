<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PosDevice;
use App\Models\SyncError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncErrorController extends Controller
{
    /**
     * POST /api/sync-errores
     *
     * La app reporta las operaciones que no pudo subir (rechazadas por el
     * servidor o atascadas en su cola). Acepta hasta 50 por llamada y es
     * idempotente por (usuario, client_id): reenviar el mismo error no lo duplica.
     * NO recibe el contenido completo de la operación (puede traer fotos o datos
     * personales): solo una etiqueta, el endpoint, el monto y el motivo.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_serial'          => 'nullable|string|max:100',
            'app_version'            => 'nullable|string|max:50',
            'errores'                => 'required|array|min:1|max:50',
            'errores.*.client_id'    => 'required|string|max:64',
            'errores.*.tipo'         => 'nullable|in:rechazado,atascado',
            'errores.*.metodo'       => 'nullable|string|max:10',
            'errores.*.endpoint'     => 'nullable|string|max:255',
            'errores.*.etiqueta'     => 'nullable|string|max:255',
            'errores.*.monto'        => 'nullable|numeric|min:0|max:9999999999',
            'errores.*.http_status'  => 'nullable|integer|between:0,599',
            'errores.*.motivo'       => 'nullable|string|max:1000',
            'errores.*.ocurrido_en'  => 'nullable|date',
        ]);

        $user = $request->user();
        $device = ! empty($data['device_serial'])
            ? PosDevice::where('serial', $data['device_serial'])->first()
            : null;

        $nuevos = 0;
        foreach ($data['errores'] as $e) {
            $error = SyncError::firstOrCreate(
                ['user_id' => $user->id, 'client_id' => $e['client_id']],
                [
                    'pos_device_id' => $device?->id,
                    'tipo'          => $e['tipo'] ?? 'rechazado',
                    'metodo'        => $e['metodo'] ?? null,
                    'endpoint'      => $e['endpoint'] ?? null,
                    'etiqueta'      => $e['etiqueta'] ?? null,
                    'monto'         => $e['monto'] ?? null,
                    'http_status'   => $e['http_status'] ?? null,
                    'motivo'        => $e['motivo'] ?? null,
                    'app_version'   => $data['app_version'] ?? null,
                    'ocurrido_en'   => $e['ocurrido_en'] ?? now(),
                ]
            );

            if ($error->wasRecentlyCreated) {
                $nuevos++;
            }
        }

        return response()->json(['ok' => true, 'registrados' => $nuevos, 'recibidos' => count($data['errores'])], 201);
    }
}
