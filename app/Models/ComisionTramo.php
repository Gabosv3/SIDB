<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComisionTramo extends Model
{
    protected $table = 'comision_tramos';

    protected $fillable = [
        'desde',
        'hasta',
        'porcentaje',
    ];

    protected $casts = [
        'desde'      => 'decimal:2',
        'hasta'      => 'decimal:2',
        'porcentaje' => 'decimal:2',
    ];

    /**
     * Porcentaje de comisión que corresponde según el total vendido: el
     * tramo donde CAE el monto, no acumulado por tramos (a diferencia del
     * ISR). Si no hay ningún tramo configurado, o el monto no cae en
     * ninguno (hueco entre tramos), devuelve 0 en vez de fallar.
     */
    public static function porcentajePara(float $totalVendido): float
    {
        $tramo = static::where('desde', '<=', $totalVendido)
            ->where(fn ($q) => $q->whereNull('hasta')->orWhere('hasta', '>=', $totalVendido))
            ->orderByDesc('desde')
            ->first();

        return $tramo ? (float) $tramo->porcentaje : 0.0;
    }
}
