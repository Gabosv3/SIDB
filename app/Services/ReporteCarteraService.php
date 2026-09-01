<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\RutaCobro;
use App\Models\Venta;
use Illuminate\Support\Carbon;

/**
 * Foto del estado actual de la cartera de crédito: antigüedad de saldos
 * (cuánto tiempo llevan pendientes, agrupado en buckets tipo AR-aging),
 * cartera y morosidad por ruta, y detalle de los clientes más atrasados.
 * La antigüedad se calcula desde la fecha de la venta más vieja que el
 * cliente todavía debe, porque fecha_pago_limite casi nunca está poblada.
 */
class ReporteCarteraService
{
    private const BUCKETS = [
        '0-15' => [0, 15],
        '16-30' => [16, 30],
        '31-60' => [31, 60],
        '61-90' => [61, 90],
        '90+' => [91, PHP_INT_MAX],
    ];

    /**
     * Por cada cliente con saldo > 0, la venta más antigua sin saldar y sus
     * días transcurridos desde la fecha de venta.
     */
    /**
     * Días transcurridos desde la venta, sin permitir negativos: una venta
     * con fecha futura (dato mal cargado o venta programada) cuenta como
     * "al día", no como un error de signo que rompe los buckets.
     */
    private static function diasTranscurridos(?object $venta, Carbon $hoy): int
    {
        if (! $venta) {
            return 0;
        }

        return max(0, (int) Carbon::parse($venta->fecha_venta)->diffInDays($hoy, false));
    }

    private static function ventaMasViejaPorCliente(): \Illuminate\Support\Collection
    {
        return Venta::where('saldo_pendiente', '>', 0)
            ->whereNotIn('estado', ['cancelada', 'devuelta'])
            ->orderBy('fecha_venta')
            ->get(['cliente_id', 'fecha_venta'])
            ->unique('cliente_id')
            ->keyBy('cliente_id');
    }

    public function totales(): array
    {
        return self::totalesEstaticos();
    }

    public static function totalesEstaticos(): array
    {
        $clientes = Cliente::where('activo', true)->where('saldo', '>', 0)->get(['id', 'saldo']);
        $ventaVieja = self::ventaMasViejaPorCliente();
        $hoy = Carbon::today();

        $enMora = $clientes->filter(function (Cliente $c) use ($ventaVieja, $hoy) {
            $venta = $ventaVieja->get($c->id);

            return self::diasTranscurridos($venta, $hoy) > 30;
        });

        return [
            'total_clientes_con_saldo' => $clientes->count(),
            'cartera_total' => round((float) $clientes->sum('saldo'), 2),
            'clientes_en_mora' => $enMora->count(),
            'monto_en_mora' => round((float) $enMora->sum('saldo'), 2),
        ];
    }

    /**
     * Buckets de antigüedad de saldo (tipo AR-aging), con monto y cantidad
     * de clientes en cada rango de días transcurridos desde su venta más
     * vieja sin saldar.
     */
    public static function antiguedad(): array
    {
        $clientes = Cliente::where('activo', true)->where('saldo', '>', 0)->get(['id', 'saldo']);
        $ventaVieja = self::ventaMasViejaPorCliente();
        $hoy = Carbon::today();

        $resultado = [];
        foreach (self::BUCKETS as $etiqueta => [$min, $max]) {
            $resultado[$etiqueta] = ['clientes' => 0, 'monto' => 0.0];
        }

        foreach ($clientes as $cliente) {
            $venta = $ventaVieja->get($cliente->id);
            $dias = self::diasTranscurridos($venta, $hoy);

            foreach (self::BUCKETS as $etiqueta => [$min, $max]) {
                if ($dias >= $min && $dias <= $max) {
                    $resultado[$etiqueta]['clientes']++;
                    $resultado[$etiqueta]['monto'] += (float) $cliente->saldo;
                    break;
                }
            }
        }

        foreach ($resultado as $etiqueta => $fila) {
            $resultado[$etiqueta]['monto'] = round($fila['monto'], 2);
        }

        return $resultado;
    }

    /**
     * Cartera y morosidad agrupada por ruta de cobro activa.
     */
    public static function resumenPorRuta(): array
    {
        $ventaVieja = self::ventaMasViejaPorCliente();
        $hoy = Carbon::today();

        return RutaCobro::where('activa', true)
            ->get()
            ->map(function (RutaCobro $ruta) use ($ventaVieja, $hoy) {
                $clientes = Cliente::where('activo', true)
                    ->where('ruta_cobro_id', $ruta->id)
                    ->where('saldo', '>', 0)
                    ->get(['id', 'saldo']);

                $enMora = $clientes->filter(function (Cliente $c) use ($ventaVieja, $hoy) {
                    $venta = $ventaVieja->get($c->id);

                    return self::diasTranscurridos($venta, $hoy) > 30;
                });

                return [
                    'ruta' => $ruta->nombre,
                    'total_clientes' => $clientes->count(),
                    'cartera_total' => round((float) $clientes->sum('saldo'), 2),
                    'clientes_en_mora' => $enMora->count(),
                    'monto_en_mora' => round((float) $enMora->sum('saldo'), 2),
                ];
            })
            ->sortByDesc('cartera_total')
            ->values()
            ->all();
    }

    /**
     * Los clientes con saldo más atrasados, para seguimiento directo.
     */
    public static function clientesMasAtrasados(int $limite = 30): array
    {
        $ventaVieja = self::ventaMasViejaPorCliente();
        $hoy = Carbon::today();

        return Cliente::where('activo', true)
            ->where('saldo', '>', 0)
            ->with('rutaCobro')
            ->get(['id', 'nombre', 'apellido', 'saldo', 'ruta_cobro_id'])
            ->map(function (Cliente $c) use ($ventaVieja, $hoy) {
                $venta = $ventaVieja->get($c->id);
                $dias = self::diasTranscurridos($venta, $hoy);

                return [
                    'nombre' => trim($c->nombre.' '.$c->apellido),
                    'ruta' => $c->rutaCobro->nombre ?? 'Sin ruta',
                    'saldo' => round((float) $c->saldo, 2),
                    'dias_transcurridos' => $dias,
                ];
            })
            ->sortByDesc('dias_transcurridos')
            ->take($limite)
            ->values()
            ->all();
    }
}
