<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Producto extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => "Producto {$eventName}");
    }

    protected $table = 'productos';

    protected $fillable = [
        'sucursal_id',
        'nombre',
        'codigo',
        'origen',
        'es_combo',
        'descripcion',
        'unidad_medida',
        'precio_compra',
        'precio_venta',
        'precio_vendedor',
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
        'precio_vendedor' => 'decimal:2',
        'precios_cuotas' => 'array',
        'stock'          => 'integer',
        'stock_minimo'   => 'integer',
        'activo'         => 'boolean',
        'es_combo'       => 'boolean',
        'peso'           => 'decimal:3',
    ];

    protected static function boot(): void
    {
        parent::boot();

        // Si este producto es COMPONENTE de algún combo, y su stock cambió,
        // hay que recalcular el stock de esos combos (pueden dar más o menos
        // unidades completas ahora). No aplica si el que cambió es el combo
        // en sí (su propio stock se pisa por recalcularStockCombo(), lo cual
        // dispararía este mismo evento -- por eso se usa updateQuietly() ahí,
        // que no re-dispara boot hooks y evita el bucle).
        // increment()/decrement() (usado en todos los ajustes de stock) solo
        // disparan 'updating'/'updated', NUNCA 'saving'/'saved' -- por eso
        // este hook cuelga de 'updated', no de 'saved'.
        static::updated(function (Producto $producto): void {
            if (! $producto->wasChanged('stock')) {
                return;
            }

            $combosAfectados = ComboComponente::where('producto_componente_id', $producto->id)
                ->pluck('producto_combo_id')
                ->unique();

            foreach ($combosAfectados as $comboId) {
                self::recalcularStockCombo($comboId);
            }
        });
    }

    /**
     * El stock de un combo no se edita a mano: es la cantidad de combos
     * completos que alcanzan con el stock actual de cada componente
     * (el mínimo, redondeado hacia abajo). Se guarda en la misma columna
     * `stock` que un producto normal para que todo el resto del sistema
     * (asignaciones, ventas, filtros de "stock > 0") funcione sin cambios.
     */
    public static function recalcularStockCombo(int $comboId): void
    {
        $componentes = ComboComponente::where('producto_combo_id', $comboId)->with('componente')->get();

        $stockCombo = $componentes->isEmpty()
            ? 0
            : (int) $componentes->min(fn (ComboComponente $c) => $c->cantidad > 0
                ? intdiv((int) ($c->componente?->stock ?? 0), $c->cantidad)
                : 0);

        // updateQuietly() no dispara el boot hook de arriba -- si lo hiciera,
        // un combo que también fuera componente de otro combo entraría en
        // un ciclo infinito de recálculos.
        static::whereKey($comboId)->update(['stock' => max(0, $stockCombo)]);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    /** Componentes que forman este combo (solo aplica si es_combo = true). */
    public function componentes(): HasMany
    {
        return $this->hasMany(ComboComponente::class, 'producto_combo_id');
    }

    /** Combos en los que este producto participa como componente. */
    public function usadoEnCombos(): HasMany
    {
        return $this->hasMany(ComboComponente::class, 'producto_componente_id');
    }

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
