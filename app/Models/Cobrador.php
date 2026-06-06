<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cobrador extends Model
{
    use HasFactory;

    protected $table = 'cobradores';

    protected $fillable = [
        'sucursal_id',
        'nombre',
        'apellido',
        'telefono',
        'email',
        'activo',
        'user_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function clientes(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            Cliente::class,
            RutaCobro::class,
            'cobrador_id',
            'ruta_cobro_id',
            'id',
            'id'
        );
    }

    public function rutasCobro(): HasMany
    {
        return $this->hasMany(RutaCobro::class, 'cobrador_id');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }
}
