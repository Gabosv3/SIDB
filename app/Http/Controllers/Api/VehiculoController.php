<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class VehiculoController extends Controller
{
    #[OA\Get(
        path: '/vehiculos/disponibles',
        summary: 'Vehículos que el usuario autenticado puede usar para un vale',
        description: 'Incluye el/los vehículo(s) asignados de forma fija al usuario, más los vehículos de reserva disponibles (para cuando la moto/carro asignado se avería).',
        security: [['sanctum' => []]],
        tags: ['Vehículos'],
        responses: [
            new OA\Response(response: 200, description: 'Lista de vehículos disponibles'),
        ],
    )]
    public function disponibles(Request $request): JsonResponse
    {
        $vehiculos = Vehiculo::where(function ($query) use ($request) {
            $query->where('asignado_a', $request->user()->id)
                ->orWhere('estado', 'reserva');
        })
            ->where('estado', '!=', 'inactivo')
            ->orderByRaw('(asignado_a = ?) desc', [$request->user()->id])
            ->get()
            ->map(fn (Vehiculo $v) => [
                'id'      => $v->id,
                'placa'   => $v->placa,
                'tipo'    => $v->tipo,
                'marca'   => $v->marca,
                'modelo'  => $v->modelo,
                'estado'  => $v->estado,
                'es_mio'  => $v->asignado_a === $request->user()->id,
            ]);

        return response()->json($vehiculos);
    }
}
