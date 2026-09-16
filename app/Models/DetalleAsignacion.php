<?php

namespace App\Models;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleAsignacion extends Model
{
    protected $table = 'detalle_asignaciones';

    protected $fillable = [
        'asignacion_id',
        'producto_id',
        'cantidad_asignada',
        'cantidad_vendida',
        'cantidad_devuelta',
        'precio_venta',
    ];

    protected $casts = [
        'cantidad_asignada' => 'integer',
        'cantidad_vendida'  => 'integer',
        'cantidad_devuelta' => 'integer',
        'precio_venta'      => 'decimal:2',
    ];

    public function getDisponibleAttribute(): int
    {
        return max(0, $this->cantidad_asignada - $this->cantidad_vendida);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (DetalleAsignacion $detalle): void {
            $producto = $detalle->producto;
            if ($producto && $detalle->cantidad_asignada > 0) {
                self::ajustarStock($producto, -$detalle->cantidad_asignada);
            }
        });

        static::updating(function (DetalleAsignacion $detalle): void {
            if ($detalle->isDirty('cantidad_asignada')) {
                $original = (int) $detalle->getOriginal('cantidad_asignada');
                $nuevo = (int) $detalle->cantidad_asignada;
                $diferencia = $nuevo - $original;
                $producto = $detalle->producto;

                if ($producto && $diferencia !== 0) {
                    self::ajustarStock($producto, -$diferencia);
                }
            }
        });

        static::deleting(function (DetalleAsignacion $detalle): void {
            $producto = $detalle->producto;
            if ($producto) {
                self::ajustarStock($producto, max(0, $detalle->cantidad_asignada - $detalle->cantidad_vendida));
            }
        });
    }

    /**
     * Descuenta (delta negativo) o devuelve (delta positivo) stock al
     * asignar/actualizar/eliminar un detalle. Si el producto es un combo,
     * el ajuste real va sobre cada componente (proporcional a la cantidad
     * de combos) en vez del combo mismo -- su stock es un valor calculado,
     * no algo que se pueda sumar/restar directamente (ver
     * Producto::recalcularStockCombo()).
     */
    private static function ajustarStock(Producto $producto, int $delta): void
    {
        if (! $producto->es_combo) {
            $producto->increment('stock', $delta);

            return;
        }

        foreach ($producto->componentes as $componente) {
            $componente->componente?->increment('stock', $delta * $componente->cantidad);
        }
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(AsignacionDiaria::class, 'asignacion_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
