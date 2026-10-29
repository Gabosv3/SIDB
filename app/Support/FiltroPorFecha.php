<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * Macro whereFecha(): igual que whereDate() pero con un rango, para que MySQL pueda
 * usar el índice de la columna (whereDate envuelve la columna en DATE(...) y lo
 * descarta, así que recorre toda la tabla).
 *
 *   ->whereFecha('fecha_pago', $dia)            // ese día
 *   ->whereFecha('fecha_vencimiento', '<=', $d) // hasta ese día inclusive
 *
 * Operadores: =, <, <=, >, >=
 */
class FiltroPorFecha
{
    public static function registrar(): void
    {
        Builder::macro('whereFecha', function (string $columna, mixed $operador, mixed $valor = null) {
            /** @var Builder $this */
            if (func_num_args() === 2) {
                [$operador, $valor] = ['=', $operador];
            }

            $dia = Carbon::parse($valor)->startOfDay();
            $siguiente = $dia->copy()->addDay();

            return match ($operador) {
                '=' => $this->where($columna, '>=', $dia)->where($columna, '<', $siguiente),
                '<' => $this->where($columna, '<', $dia),
                '<=' => $this->where($columna, '<', $siguiente),
                '>' => $this->where($columna, '>=', $siguiente),
                '>=' => $this->where($columna, '>=', $dia),
                default => throw new \InvalidArgumentException("Operador no soportado en whereFecha: {$operador}"),
            };
        });
    }
}
