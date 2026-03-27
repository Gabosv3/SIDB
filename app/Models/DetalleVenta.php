<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    ];

    protected $casts = [
        'cantidad'             => 'integer',
        'precio_unitario'      => 'decimal:2',
        'descuento_porcentaje' => 'decimal:2',
        'subtotal'             => 'decimal:2',
    ];

    // ── Boot: descontar stock al crear detalle ────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (DetalleVenta $detalle): void {
            $detalle->producto?->decrement('stock', $detalle->cantidad);
        });

        static::deleting(function (DetalleVenta $detalle): void {
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
