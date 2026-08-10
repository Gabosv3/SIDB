<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Supervisor extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'supervisores';

    protected $fillable = [
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Rutas de cobro que este supervisor puede ver/operar, asignadas desde el panel. */
    public function rutasSupervisadas(): BelongsToMany
    {
        return $this->belongsToMany(RutaCobro::class, 'supervisor_ruta_cobro', 'supervisor_id', 'ruta_cobro_id');
    }

    public function supervisiones(): HasMany
    {
        return $this->hasMany(Supervision::class);
    }

    public function encuestasCliente(): HasMany
    {
        return $this->hasMany(EncuestaCliente::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Supervisor {$eventName}");
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }
}
