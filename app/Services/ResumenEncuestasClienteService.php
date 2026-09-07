<?php

namespace App\Services;

use App\Models\EncuestaCliente;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ResumenEncuestasClienteService
{
    /** @param  array<int>  $cobradorIds */
    public static function resumen(string $fecha, array $cobradorIds = []): Collection
    {
        return EncuestaCliente::whereDate('fecha', Carbon::parse($fecha))
            ->when($cobradorIds !== [], fn ($q) => $q->whereIn('cobrador_id', $cobradorIds))
            ->with(['cliente', 'supervisor', 'cobrador'])
            ->orderByDesc('created_at')
            ->get();
    }

    /** @param  Collection<int, EncuestaCliente>  $resumen */
    public static function totales(Collection $resumen): array
    {
        return [
            'total_encuestas' => $resumen->count(),
            'coinciden' => $resumen->where('resultado', 'coincide')->count(),
            'con_diferencia' => $resumen->where('resultado', '!=', 'coincide')->count(),
            'monto_diferencia' => (float) $resumen->sum(fn (EncuestaCliente $e) => abs((float) $e->diferencia)),
        ];
    }
}
