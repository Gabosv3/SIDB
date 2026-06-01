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
                $producto->decrement('stock', $detalle->cantidad_asignada);
            }
        });

        static::updating(function (DetalleAsignacion $detalle): void {
            if ($detalle->isDirty('cantidad_asignada')) {
                $original = (int) $detalle->getOriginal('cantidad_asignada');
                $nuevo = (int) $detalle->cantidad_asignada;
                $diferencia = $nuevo - $original;
                $producto = $detalle->producto;

                if ($producto && $diferencia !== 0) {
                    if ($diferencia > 0) {
                        $producto->decrement('stock', $diferencia);
                    } else {
                        $producto->increment('stock', abs($diferencia));
                    }
                }
            }
        });

        static::deleting(function (DetalleAsignacion $detalle): void {
            $producto = $detalle->producto;
            if ($producto) {
                $producto->increment('stock', max(0, $detalle->cantidad_asignada - $detalle->cantidad_vendida));
            }
        });
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
