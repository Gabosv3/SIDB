<?php

namespace App\Services;

use App\Models\Garantia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ResumenGarantiasService
{
    /** @param  array<int>  $asignadoAIds */
    public static function resumen(string $fecha, array $asignadoAIds = []): Collection
    {
        return Garantia::whereDate('fecha_reporte', Carbon::parse($fecha))
            ->when($asignadoAIds !== [], fn ($q) => $q->whereIn('asignado_a', $asignadoAIds))
            ->with(['cliente', 'venta:id,numero_venta', 'reportadoPor', 'asignadoA', 'cobrador'])
            ->orderByDesc('created_at')
            ->get();
    }

    /** @param  Collection<int, Garantia>  $resumen */
    public static function totales(Collection $resumen): array
    {
        return [
            'total' => $resumen->count(),
            'pendientes' => $resumen->where('estado', 'pendiente')->count(),
            'en_proceso' => $resumen->where('estado', 'en_proceso')->count(),
            'resueltas' => $resumen->where('estado', 'resuelta')->count(),
            'rechazadas' => $resumen->where('estado', 'rechazada')->count(),
        ];
    }
}
