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
        // Los tramos se leen una sola vez por petición (antes era una
        // consulta por cada producto de cada venta).
        $tramo = static::tramosEnMemoria()
            ->filter(fn (self $t) => (float) $t->desde <= $totalVendido
                && ($t->hasta === null || (float) $t->hasta >= $totalVendido))
            ->sortByDesc(fn (self $t) => (float) $t->desde)
            ->first();

        return $tramo ? (float) $tramo->porcentaje : 0.0;
    }

    /** @var \Illuminate\Support\Collection<int, self>|null */
    private static ?\Illuminate\Support\Collection $tramosCache = null;

    private static function tramosEnMemoria(): \Illuminate\Support\Collection
    {
        return static::$tramosCache ??= static::query()->get();
    }

    public static function olvidarCache(): void
    {
        static::$tramosCache = null;
    }

    protected static function booted(): void
    {
        $olvidar = fn () => static::olvidarCache();
        static::saved($olvidar);
        static::deleted($olvidar);
    }
}
