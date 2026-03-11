<?php

namespace App\Models;

use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
}
