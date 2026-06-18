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
        $user = $request->user();

        $device = PosDevice::firstOrCreate(
            ['serial' => $request->device_serial ?? 'UNKNOWN-' . $user->id],
            ['nombre' => $request->device_nombre ?? ('POS-' . str_pad($user->id, 3, '0', STR_PAD_LEFT))]
        );

        $device->update([
            'user_id'       => $user->id,
            'cobrador_id'   => optional($user->cobrador)->id,
            'latitud'       => $request->lat,
            'longitud'      => $request->lng,
            'bateria'       => $request->bateria,
            'tiene_internet'=> true,
            'app_version'   => $request->app_version,
            'ultimo_error'  => $request->error,
            'ultimo_ping'   => now(),
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
