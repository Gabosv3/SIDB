<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RutaCobro extends Model
{
    use HasFactory;

    protected $table = 'rutas_cobro';

    protected $fillable = [
        'sucursal_id',
        'cobrador_id',
        'nombre',
        'descripcion',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function cobrador(): BelongsTo
    {
        return $this->belongsTo(Cobrador::class, 'cobrador_id');
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'ruta_cobro_id');
    }
}
