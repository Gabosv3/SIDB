<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Vendedor extends Model
{
    use HasFactory;

    protected $table = 'vendedores';

    protected $fillable = [
        'codigo',
        'nombre',
        'apellido',
        'email',
        'telefono',
        'activo',
        'sucursal_id',
        'user_id',
        'es_cobrador',
    ];

    protected $casts = [
        'activo'      => 'boolean',
        'es_cobrador' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Vendedor $vendedor): void {
            if (empty($vendedor->codigo)) {
                $ultimo = static::max('id') ?? 0;
                $vendedor->codigo = 'VEND-' . str_pad($ultimo + 1, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'vendedor_id');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionDiaria::class, 'vendedor_id');
    }

    /** Asignación activa de hoy */
    public function asignacionHoy(): ?AsignacionDiaria
    {
        return $this->asignaciones()
            ->where('fecha', today())
            ->where('estado', 'activa')
            ->first();
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }
}
