<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsignacionDiaria extends Model
{
    protected $table = 'asignaciones_diarias';

    protected $fillable = [
        'vendedor_id',
        'sucursal_id',
        'fecha',
        'estado',
        'total_vendido',
        'total_devuelto_valor',
        'liquidada_at',
        'observaciones',
    ];

    protected $casts = [
        'fecha'               => 'date',
        'liquidada_at'        => 'datetime',
        'total_vendido'       => 'decimal:2',
        'total_devuelto_valor'=> 'decimal:2',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class, 'vendedor_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleAsignacion::class, 'asignacion_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function estaActiva(): bool
    {
        return $this->estado === 'activa';
    }

    /**
     * Liquida la asignación: calcula cantidades vendidas vs devueltas
     * consultando las ventas del día del vendedor.
     */
    public function liquidar(?string $observaciones = null): void
    {
        // Para cada producto asignado, sumar lo que se vendió hoy
        $this->detalles->each(function (DetalleAsignacion $detalle) {
            $vendidas = DetalleVenta::whereHas('venta', function ($q) {
                $q->where('vendedor_id', $this->vendedor_id)
                  ->whereDate('fecha_venta', $this->fecha)
                  ->whereIn('estado', ['completada', 'pendiente']);
            })
            ->where('producto_id', $detalle->producto_id)
            ->sum('cantidad');

            $devueltas = max(0, $detalle->cantidad_asignada - $vendidas);

            if ($devueltas > 0) {
                $detalle->producto?->increment('stock', $devueltas);
            }

            $detalle->update([
                'cantidad_vendida'  => $vendidas,
                'cantidad_devuelta' => $devueltas,
            ]);
        });

        // Refrescar detalles y calcular totales
        $this->load('detalles');

        $totalVendido = $this->detalles->sum(
            fn ($d) => $d->cantidad_vendida * ($d->precio_venta ?? 0)
        );
        $totalDevuelto = $this->detalles->sum(
            fn ($d) => $d->cantidad_devuelta * ($d->precio_venta ?? 0)
        );

        $this->update([
            'estado'               => 'liquidada',
            'total_vendido'        => $totalVendido,
            'total_devuelto_valor' => $totalDevuelto,
            'liquidada_at'         => now(),
            'observaciones'        => $observaciones ?? $this->observaciones,
        ]);
    }
}
