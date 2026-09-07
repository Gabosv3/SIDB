<?php

namespace App\Services;

use App\Models\Reintegro;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ResumenReintegrosService
{
    /**
     * @param  array<int>  $vendedorIds
     * @param  array<int>  $cobradorUserIds  user_id de los cobradores que mandaron el reintegro (campo asignado_por)
     * @param  array<int>  $rutaIds  ruta_cobro_id_original — la ruta de la que se sacó al cliente
     */
    public static function resumen(string $fecha, array $vendedorIds = [], array $cobradorUserIds = [], array $rutaIds = []): Collection
    {
        return Reintegro::whereDate('fecha_asignacion', Carbon::parse($fecha))
            ->when($vendedorIds !== [], fn ($q) => $q->whereIn('vendedor_id', $vendedorIds))
            ->when($cobradorUserIds !== [], fn ($q) => $q->whereIn('asignado_por', $cobradorUserIds))
            ->when($rutaIds !== [], fn ($q) => $q->whereIn('ruta_cobro_id_original', $rutaIds))
            ->with(['cliente', 'vendedor', 'asignadoPor', 'rutaCobroOriginal', 'venta:id,numero_venta'])
            ->orderByDesc('created_at')
            ->get();
    }

    /** @param  Collection<int, Reintegro>  $resumen */
    public static function totales(Collection $resumen): array
    {
        return [
            'total' => $resumen->count(),
            'sin_asignar' => $resumen->whereNull('vendedor_id')->count(),
            'recuperados' => $resumen->where('estado', 'recuperado')->count(),
            'no_recuperados' => $resumen->where('estado', 'no_recuperado')->count(),
            'monto_adeudado' => (float) $resumen->sum('monto_adeudado'),
        ];
    }
}
