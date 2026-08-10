<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'semana_ciclo',
        'descripcion',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
        'semana_ciclo' => 'integer',
    ];

    protected $appends = [
        'nombre_con_dia',
    ];

    // ── Accessors ──────────────────────────────────────────────────────────────

    public function getNombreConDiaAttribute(): string
    {
        $dia = $this->dia_semana ? ucfirst($this->dia_semana) : 'Sin día';
        $semana = $this->semana_ciclo ? " — Semana {$this->semana_ciclo}" : '';
        return "{$this->nombre} ({$dia}{$semana})";
    }

    /**
     * ¿Esta ruta corre en la semana dada del ciclo quincenal (1 o 2)? Una
     * ruta sin semana_ciclo asignada todavía (null) se considera visible en
     * cualquier semana — es el valor de transición para no ocultar rutas de
     * golpe hasta que se clasifiquen. $semanaCiclo también puede venir null
     * (ciclo no configurado en Personalización del Sistema), en cuyo caso
     * tampoco se filtra nada.
     */
    public function correspondeASemana(?int $semanaCiclo): bool
    {
        return $this->semana_ciclo === null || $semanaCiclo === null || $this->semana_ciclo === $semanaCiclo;
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

    /** Supervisores con acceso a esta ruta (además del cobrador titular). */
    public function supervisores(): BelongsToMany
    {
        return $this->belongsToMany(Supervisor::class, 'supervisor_ruta_cobro', 'ruta_cobro_id', 'supervisor_id');
    }
}
