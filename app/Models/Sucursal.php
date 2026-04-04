<?php

namespace App\Models;

use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model implements HasName
{
    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'codigo',
        'direccion',
        'telefono',
        'email',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /** Implementación de HasName: Filament usa este método para mostrar el nombre del tenant */
    public function getFilamentName(): string
    {
        return (string) $this->nombre;
    }

    /** Usuarios que pertenecen a esta sucursal */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /** Cobradores de esta sucursal */
    public function cobradores(): HasMany
    {
        return $this->hasMany(Cobrador::class, 'sucursal_id');
    }

    /** Rutas de cobro de esta sucursal */
    public function rutasCobro(): HasMany
    {
        return $this->hasMany(RutaCobro::class, 'sucursal_id');
    }

    /** Categorías de esta sucursal */
    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class, 'sucursal_id');
    }

    /** Clientes de esta sucursal */
    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'sucursal_id');
    }

    /** Vendedores de esta sucursal */
    public function vendedores(): HasMany
    {
        return $this->hasMany(Vendedor::class, 'sucursal_id');
    }

    /** Ventas de esta sucursal */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'sucursal_id');
    }

    /** Movimientos de stock de esta sucursal */
    public function movimientosStock(): HasMany
    {
        return $this->hasMany(MovimientoStock::class, 'sucursal_id');
    }

    /** Productos de esta sucursal */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'sucursal_id');
    }
}
