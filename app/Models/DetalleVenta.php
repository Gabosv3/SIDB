<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AsignacionDiaria;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleVenta extends Model
{
    use HasFactory;

    protected $table = 'detalle_ventas';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'descuento_porcentaje',
        'subtotal',
        'cuotas',
        'precio_cuota',
        'tipo_pago',
    ];

    protected $casts = [
        'cantidad'             => 'integer',
        'precio_unitario'      => 'decimal:2',
        'descuento_porcentaje' => 'decimal:2',
        'subtotal'             => 'decimal:2',
        'cuotas'               => 'integer',
        'precio_cuota'         => 'decimal:2',
    ];

    // ── Boot: descontar stock al crear detalle ────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (DetalleVenta $detalle): void {
            $venta = $detalle->venta;
            if ($venta && $venta->vendedor_id) {
                $tieneAsignacion = AsignacionDiaria::where('vendedor_id', $venta->vendedor_id)
                    ->whereDate('fecha', $venta->fecha_venta)
                    ->where('estado', 'activa')
                    ->whereHas('detalles', function ($query) use ($detalle) {
                        $query->where('producto_id', $detalle->producto_id);
                    })
                    ->exists();

                if ($tieneAsignacion) {
                    return;
                }
            }

            $detalle->producto?->decrement('stock', $detalle->cantidad);
        });

        static::deleting(function (DetalleVenta $detalle): void {
            $venta = $detalle->venta;
            if ($venta && $venta->vendedor_id) {
                $tieneAsignacion = AsignacionDiaria::where('vendedor_id', $venta->vendedor_id)
                    ->whereDate('fecha', $venta->fecha_venta)
                    ->where('estado', 'activa')
                    ->whereHas('detalles', function ($query) use ($detalle) {
                        $query->where('producto_id', $detalle->producto_id);
                    })
                    ->exists();

                if ($tieneAsignacion) {
                    return;
                }
            }

            $detalle->producto?->increment('stock', $detalle->cantidad);
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
