<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Producto extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => "Producto {$eventName}");
    }

    protected $table = 'productos';

    protected $fillable = [
        'sucursal_id',
        'nombre',
        'codigo',
        'descripcion',
        'unidad_medida',
        'precio_compra',
        'precio_venta',
        'precios_cuotas',
        'stock',
        'stock_minimo',
        'activo',
        'categoria_id',
        'peso',
        'dimensiones',
        'imagen',
    ];

    protected $casts = [
        'precio_compra'  => 'decimal:2',
        'precio_venta'   => 'decimal:2',
        'precios_cuotas' => 'array',
        'stock'          => 'integer',
        'stock_minimo'   => 'integer',
        'activo'         => 'boolean',
        'peso'           => 'decimal:3',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function detalleAsignaciones(): HasMany
    {
        return $this->hasMany(DetalleAsignacion::class, 'producto_id');
    }

    /**
     * Proveedores del producto
     */
    public function proveedores(): BelongsToMany
    {
        return $this->belongsToMany(Proveedor::class, 'producto_proveedor')
            ->withPivot('codigo_proveedor', 'precio_unitario', 'cantidad_minima', 'tiempo_entrega_dias')
            ->withTimestamps();
    }

    /**
     * Detalles de compra del producto
     */
    public function detallesCompra(): HasMany
    {
        return $this->hasMany(DetalleCompra::class, 'producto_id');
    }

    public function detallesVenta(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'producto_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Verificar si el stock está bajo el mínimo
     */
    public function stockBajo(): bool
    {
        return $this->stock <= $this->stock_minimo;
    }

    /**
     * Obtener margen de ganancia
     */
    public function margenGanancia(): float
    {
        if ($this->precio_compra == 0) return 0;
        return (($this->precio_venta - $this->precio_compra) / $this->precio_compra) * 100;
    }

    /**
     * Obtener ganancia unitaria
     */
    public function gananciaUnitaria(): float
    {
        return $this->precio_venta - $this->precio_compra;
    }

    /**
     * Obtener proveedor principal (más reciente)
     */
    public function proveedorPrincipal(): Proveedor|null
    {
        return $this->proveedores()->latest('producto_proveedor.created_at')->first();
    }

    /**
     * Necesita reabastecimiento
     */
    public function necesitaReabastecimiento(): bool
    {
        return $this->stock <= $this->stock_minimo;
    }
}
