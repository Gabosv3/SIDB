<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'unidad_medida',
        'precio_compra',
        'precio_venta',
        'stock',
        'stock_minimo',
        'activo',
    ];

    protected $casts = [
        'precio_compra' => 'decimal:2',
        'precio_venta'  => 'decimal:2',
        'stock'         => 'integer',
        'stock_minimo'  => 'integer',
        'activo'        => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoStock::class, 'producto_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function stockBajo(): bool
    {
        return $this->stock <= $this->stock_minimo;
    }
}
