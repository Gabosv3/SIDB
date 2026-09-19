<?php

namespace App\Services;

use App\Models\Cliente;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Clientes "abandonados": llevan al menos 1 mes sin que un cobrador los
 * visite (visitas_cobro) y sin abonar (pago_ventas), sin importar cuál de
 * las dos pasó primero -- si cualquiera de las dos ocurrió dentro del
 * último mes, el cliente ya no cuenta como inactivo.
 */
class ClientesInactivosService
{
    public static function listar(int $dias = 30, ?int $rutaId = null, ?int $cobradorId = null): Collection
    {
        $limite = Carbon::now()->subDays($dias);

        $ultimaVisita = \App\Models\VisitaCobro::selectRaw('cliente_id, MAX(fecha_visita) as ultima')
            ->groupBy('cliente_id')
            ->pluck('ultima', 'cliente_id');

        $ultimoPago = \App\Models\PagoVenta::whereNull('anulado_en')
            ->selectRaw('cliente_id, MAX(fecha_pago) as ultimo')
            ->groupBy('cliente_id')
            ->pluck('ultimo', 'cliente_id');

        $filas = Cliente::where('activo', true)
            ->where('saldo', '>', 0)
            ->when($rutaId, fn ($q) => $q->where('ruta_cobro_id', $rutaId))
            ->when($cobradorId, fn ($q) => $q->whereHas('rutaCobro', fn ($q2) => $q2->where('cobrador_id', $cobradorId)))
            ->with('rutaCobro.cobrador')
            ->get()
            ->map(function (Cliente $cliente) use ($ultimaVisita, $ultimoPago) {
                $visita = $ultimaVisita->get($cliente->id);
                $pago   = $ultimoPago->get($cliente->id);

                return [
                    'cliente'        => $cliente,
                    'ultima_visita'  => $visita ? Carbon::parse($visita) : null,
                    'ultimo_pago'    => $pago ? Carbon::parse($pago) : null,
                ];
            })
            ->filter(function (array $fila) use ($limite) {
                $visitaVieja = ! $fila['ultima_visita'] || $fila['ultima_visita']->lt($limite);
                $pagoViejo   = ! $fila['ultimo_pago'] || $fila['ultimo_pago']->lt($limite);

                return $visitaVieja && $pagoViejo;
            })
            ->map(function (array $fila) {
                $hoy = Carbon::now();
                $fila['dias_sin_visita'] = $fila['ultima_visita'] ? (int) floor($fila['ultima_visita']->diffInDays($hoy)) : null;
                $fila['dias_sin_pago']   = $fila['ultimo_pago'] ? (int) floor($fila['ultimo_pago']->diffInDays($hoy)) : null;

                return $fila;
            });

        // Filtrando por un cobrador puntual: sirve para que salga a cobrar,
        // así que se ordena como su recorrido real (ruta y orden de visita
        // del cliente dentro de ella), no por antigüedad de la deuda.
        // Collection::sortBy() no hace multi-key sort con closures sueltos en
        // el array, así que se ordena a mano con usort.
        if ($cobradorId) {
            $ordenadas = $filas->values()->all();

            usort($ordenadas, function (array $a, array $b) {
                $rutaA = $a['cliente']->rutaCobro?->nombre ?? '';
                $rutaB = $b['cliente']->rutaCobro?->nombre ?? '';

                if ($rutaA !== $rutaB) {
                    return $rutaA <=> $rutaB;
                }

                return ($a['cliente']->orden ?? PHP_INT_MAX) <=> ($b['cliente']->orden ?? PHP_INT_MAX);
            });

            return collect($ordenadas)->values();
        }

        return $filas->sortByDesc(fn (array $fila) => $fila['dias_sin_pago'] ?? PHP_INT_MAX)
            ->values();
    }
}
