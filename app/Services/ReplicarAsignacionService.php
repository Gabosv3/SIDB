<?php

namespace App\Services;

use App\Models\AsignacionDiaria;
use App\Models\DetalleAsignacion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReplicarAsignacionService
{
    /** Última asignación del vendedor anterior a la fecha indicada ("el día anterior" que trabajó). */
    public static function origen(int $vendedorId, string $fecha): ?AsignacionDiaria
    {
        return AsignacionDiaria::where('vendedor_id', $vendedorId)
            ->whereDate('fecha', '<', Carbon::parse($fecha)->toDateString())
            ->with('detalles.producto')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Crea la asignación de $fecha copiando los productos y cantidades de la
     * última asignación previa del vendedor. Los precios son los vigentes hoy
     * (igual que en una asignación nueva) y la cantidad se limita al stock
     * disponible -- lo que no alcance queda anotado en 'ajustes'.
     *
     * @return array{asignacion: AsignacionDiaria, origen: AsignacionDiaria, ajustes: array<int, string>}
     */
    public static function replicar(int $vendedorId, string $fecha): array
    {
        $fecha = Carbon::parse($fecha)->toDateString();

        $origen = self::origen($vendedorId, $fecha)
            ?? throw new \RuntimeException('Este vendedor no tiene una asignación anterior para replicar.');

        return DB::transaction(function () use ($vendedorId, $fecha, $origen) {
            if (AsignacionDiaria::where('vendedor_id', $vendedorId)->whereDate('fecha', $fecha)->lockForUpdate()->exists()) {
                throw new \RuntimeException('Este vendedor ya tiene una asignación para esa fecha.');
            }

            $asignacion = AsignacionDiaria::create([
                'vendedor_id' => $vendedorId,
                'sucursal_id' => $origen->sucursal_id,
                'fecha' => $fecha,
                'estado' => 'activa',
            ]);

            $ajustes = [];
            $creados = 0;

            foreach ($origen->detalles as $detalle) {
                $producto = $detalle->producto;

                if (! $producto) {
                    continue;
                }

                $cantidad = min((int) $detalle->cantidad_asignada, max(0, (int) $producto->stock));

                if ($cantidad < (int) $detalle->cantidad_asignada) {
                    $ajustes[] = "{$producto->nombre}: se pidieron {$detalle->cantidad_asignada}, hay {$cantidad} en stock";
                }

                if ($cantidad <= 0) {
                    continue;
                }

                DetalleAsignacion::create([
                    'asignacion_id' => $asignacion->id,
                    'producto_id' => $producto->id,
                    'cantidad_asignada' => $cantidad,
                    'precio_venta' => $producto->precio_venta,
                ]);
                $creados++;
            }

            if ($creados === 0) {
                throw new \RuntimeException('No hay stock de ninguno de los productos de la asignación anterior.');
            }

            return ['asignacion' => $asignacion, 'origen' => $origen, 'ajustes' => $ajustes];
        });
    }
}
