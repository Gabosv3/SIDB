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
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'cobrador_id');
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
