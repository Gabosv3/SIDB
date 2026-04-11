<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Proveedor extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => "Proveedor {$eventName}");
    }

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'codigo',
        'contacto_principal',
        'email',
        'telefono',
        'telefono_adicional',
        'direccion',
        'ciudad',
        'departamento',
        'pais',
        'codigo_postal',
        'rfc_o_nit',
        'condiciones_pago',
        'dias_credito',
        'descuento_comercial',
        'activo',
        'notas',
    ];

    protected $casts = [
        'descuento_comercial' => 'decimal:2',
        'dias_credito'        => 'integer',
        'activo'              => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    /**
     * Compras realizadas al proveedor
     */
    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'proveedor_id');
    }

    /**
     * Productos que suministra
     */
    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'producto_proveedor')
            ->withPivot('codigo_proveedor', 'precio_unitario', 'cantidad_minima', 'tiempo_entrega_dias')
            ->withTimestamps();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Obtener total gastado con el proveedor
     */
    public function totalGastado(): float
    {
        return $this->compras()
            ->where('estado', 'completada')
            ->sum('total');
    }

    /**
     * Obtener número de compras realizadas
     */
    public function totalCompras(): int
    {
        return $this->compras()->count();
    }

    /**
     * Verificar si el proveedor tiene deuda pendiente
     */
    public function tieneDeuda(): bool
    {
        return $this->compras()
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->exists();
    }

    /**
     * Obtener deuda total pendiente
     */
    public function deudaPendiente(): float
    {
        return $this->compras()
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->sum('saldo_pendiente');
    }
}
