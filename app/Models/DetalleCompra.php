<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class DetalleCompra extends Model
{
    use HasFactory;

    protected $table = 'detalle_compras';

    protected $fillable = [
        'compra_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'descuento_unitario',
        'subtotal',
        'numero_lote',
        'fecha_vencimiento',
        'observaciones',
    ];

    protected $casts = [
        'cantidad'           => 'integer',
        'precio_unitario'    => 'decimal:2',
        'descuento_unitario' => 'decimal:2',
        'subtotal'           => 'decimal:2',
        'fecha_vencimiento'  => 'date',
    ];

    protected $appends = ['total_con_descuento'];

    // ── Relationships ─────────────────────────────────────────────────────────

    /**
     * Compra a la que pertenece el detalle
     */
    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    /**
     * Producto comprado
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    // ── Accessors & Mutators ──────────────────────────────────────────────────

    /**
     * Calcular total con descuento
     */
    public function totalConDescuento(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->precio_unitario - $this->descuento_unitario) * $this->cantidad
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Calcular subtotal del artículo
     */
    public function calcularSubtotal(): float
    {
        return ($this->precio_unitario - $this->descuento_unitario) * $this->cantidad;
    }

    /**
     * Obtener ahorro por descuento
     */
    public function ahorroTotal(): float
    {
        return $this->descuento_unitario * $this->cantidad;
    }
}
