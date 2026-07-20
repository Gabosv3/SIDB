<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AsignacionDiaria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class AsignacionController extends Controller
{
    /**
     * Retorna la asignación activa de hoy para el vendedor autenticado,
     * incluyendo los productos con cantidades.
     */
    public function hoy(Request $request): JsonResponse
    {
        $vendedor = $request->user()->vendedor;

        if (! $vendedor) {
            return response()->json([
                'message' => 'Este usuario no tiene perfil de vendedor.',
            ], 403);
        }

        $asignacion = AsignacionDiaria::with([
            'detalles.producto:id,nombre,codigo,unidad_medida,precio_venta,precios_cuotas,imagen,categoria_id',
            'detalles.producto.categoria:id,nombre',
        ])
        ->where('vendedor_id', $vendedor->id)
        ->where('fecha', today())
        ->where('estado', 'activa')
        ->first();

        if (! $asignacion) {
            return response()->json([
                'message'    => 'No hay asignación activa para hoy.',
                'asignacion' => null,
            ]);
        }

        return response()->json([
            'asignacion' => [
                'id'           => $asignacion->id,
                'fecha'        => $asignacion->fecha->toDateString(),
                'estado'       => $asignacion->estado,
                'sucursal_id'  => $asignacion->sucursal_id,
                'observaciones'=> $asignacion->observaciones,
                'productos'    => $asignacion->detalles->map(fn ($d) => [
                    'id'                => $d->id,
                    'producto_id'       => $d->producto_id,
                    'nombre'            => $d->producto?->nombre,
                    'codigo'            => $d->producto?->codigo,
                    'unidad_medida'     => $d->producto?->unidad_medida,
                    'precio_venta'      => $d->precio_venta ?? $d->producto?->precio_venta,
                    'precios_cuotas'    => $d->producto?->precios_cuotas ?? [],
                    'imagen'            => $d->producto?->imagen,
                    'categoria_id'      => $d->producto?->categoria_id,
                    'categoria'         => $d->producto?->categoria?->nombre,
                    'cantidad_asignada' => $d->cantidad_asignada,
                    'cantidad_vendida'  => $d->cantidad_vendida,
                    'cantidad_devuelta' => $d->cantidad_devuelta,
                ]),
            ],
        ]);
    }

    /**
     * Liquida la asignación de hoy:
     * calcula unidades vendidas vs devueltas y cierra la jornada.
     */
    public function liquidar(Request $request, int $id): JsonResponse
    {
        $vendedor = $request->user()->vendedor;

        if (! $vendedor) {
            return response()->json(['message' => 'Este usuario no tiene perfil de vendedor.'], 403);
        }

        $data = $request->validate([
            'observaciones' => 'nullable|string|max:500',
        ]);

        // Se bloquea la fila de la asignación dentro de una transacción para que
        // dos liquidaciones casi simultáneas (doble tap) no dupliquen el stock
        // devuelto ni los totales — la segunda espera a la primera y ve que ya
        // no está activa.
        $asignacion = DB::transaction(function () use ($id, $vendedor, $data) {
            $asignacion = AsignacionDiaria::where('id', $id)
                ->where('vendedor_id', $vendedor->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $asignacion->estaActiva()) {
                return null;
            }

            $asignacion->liquidar($data['observaciones'] ?? null);

            return $asignacion;
        });

        if (! $asignacion) {
            return response()->json([
                'message' => 'Esta asignación ya fue liquidada.',
            ], 422);
        }

        $asignacion->load('detalles.producto:id,nombre,codigo');

        return response()->json([
            'message'    => 'Jornada liquidada correctamente.',
            'asignacion' => [
                'id'                   => $asignacion->id,
                'fecha'                => $asignacion->fecha->toDateString(),
                'estado'               => $asignacion->estado,
                'total_vendido'        => $asignacion->total_vendido,
                'total_devuelto_valor' => $asignacion->total_devuelto_valor,
                'liquidada_at'         => $asignacion->liquidada_at?->toDateTimeString(),
                'detalle'              => $asignacion->detalles->map(fn ($d) => [
                    'producto'          => $d->producto?->nombre,
                    'cantidad_asignada' => $d->cantidad_asignada,
                    'cantidad_vendida'  => $d->cantidad_vendida,
                    'cantidad_devuelta' => $d->cantidad_devuelta,
                    'valor_vendido'     => round($d->cantidad_vendida * ($d->precio_venta ?? 0), 2),
                    'valor_devuelto'    => round($d->cantidad_devuelta * ($d->precio_venta ?? 0), 2),
                ]),
            ],
        ]);
    }
}
