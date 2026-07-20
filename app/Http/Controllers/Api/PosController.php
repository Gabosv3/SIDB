<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PosDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_serial' => 'nullable|string|max:100',
            'device_nombre' => 'nullable|string|max:100',
            'lat'           => 'nullable|numeric|between:-90,90',
            'lng'           => 'nullable|numeric|between:-180,180',
            'bateria'       => 'nullable|integer|between:0,100',
            'app_version'   => 'nullable|string|max:50',
            'error'         => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $serial = $data['device_serial'] ?? 'UNKNOWN-' . $user->id;

        $device = PosDevice::where('serial', $serial)->first();

        // Si el serial ya está vinculado a OTRO usuario, no se permite que este
        // heartbeat "secuestre" el dispositivo y le reescriba ubicación/estado.
        if ($device && $device->user_id && $device->user_id !== $user->id) {
            return response()->json([
                'mensaje' => 'Este dispositivo ya está vinculado a otro usuario.',
            ], 403);
        }

        if (! $device) {
            $device = PosDevice::create([
                'serial' => $serial,
                'nombre' => $data['device_nombre'] ?? ('POS-' . str_pad($user->id, 3, '0', STR_PAD_LEFT)),
            ]);
        }

        $device->update([
            'user_id'        => $user->id,
            'cobrador_id'    => optional($user->cobrador)->id,
            'latitud'        => $data['lat'] ?? null,
            'longitud'       => $data['lng'] ?? null,
            'bateria'        => $data['bateria'] ?? null,
            'tiene_internet' => true,
            'app_version'    => $data['app_version'] ?? null,
            'ultimo_error'   => $data['error'] ?? null,
            'ultimo_ping'    => now(),
        ]);

        $device->estado = $device->estado_calc;
        $device->saveQuietly();

        return response()->json([
            'ok'          => true,
            'estado'      => $device->estado,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
