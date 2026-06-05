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
        'dia_semana',
        'descripcion',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    protected $appends = [
        'nombre_con_dia',
    ];

    // ── Accessors ──────────────────────────────────────────────────────────────

    public function getNombreConDiaAttribute(): string
    {
        $dia = $this->dia_semana ? ucfirst($this->dia_semana) : 'Sin día';
        return "{$this->nombre} ({$dia})";
    }

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
