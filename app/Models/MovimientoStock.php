<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoStock extends Model
{
    use HasFactory;

    protected $table = 'movimientos_stock';

    protected $fillable = [
        'producto_id',
        'user_id',
        'sucursal_id',
        'tipo',
        'cantidad',
        'precio_unitario',
        'referencia',
        'observaciones',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'cantidad'        => 'integer',
    ];

    // ── Boot: actualizar stock automáticamente ────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (MovimientoStock $movimiento): void {
            $producto = $movimiento->producto;
            if (! $producto) {
                return;
            }

            match ($movimiento->tipo) {
                'entrada' => $producto->increment('stock', $movimiento->cantidad),
                'salida'  => $producto->decrement('stock', $movimiento->cantidad),
                'ajuste'  => $producto->update(['stock' => $movimiento->cantidad]),
            };
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }
}
