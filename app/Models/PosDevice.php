<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosDevice extends Model
{
    protected $table = 'pos_devices';

    protected $fillable = [
        'nombre', 'numero_inventario', 'whatsapp', 'notas', 'serial', 'user_id', 'cobrador_id',
        'latitud', 'longitud', 'bateria', 'tiene_internet',
        'app_version', 'ultimo_error', 'ultimo_ping', 'estado', 'activo',
        'pendientes_sync', 'pendiente_antiguo_min', 'rechazados_sync',
    ];

    protected $casts = [
        'latitud'        => 'float',
        'longitud'       => 'float',
        'bateria'        => 'integer',
        'tiene_internet' => 'boolean',
        'activo'         => 'boolean',
        'ultimo_ping'    => 'datetime',
        'pendientes_sync'       => 'integer',
        'pendiente_antiguo_min' => 'integer',
        'rechazados_sync'       => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cobrador(): BelongsTo
    {
        return $this->belongsTo(Cobrador::class);
    }

    public function getEstadoCalcAttribute(): string
    {
        if (!$this->ultimo_ping) return 'apagado';
        $min = now()->diffInMinutes($this->ultimo_ping);
        if ($min > 10) return 'sin_conexion';
        if ($this->bateria !== null && $this->bateria <= 20) return 'bateria_baja';
        return 'activo';
    }

    // Minutos que algo puede esperar en la cola del teléfono antes de considerarlo
    // atascado (con señal normal se sube en segundos).
    public const MIN_COLA_ATASCADA = 30;

    /**
     * ¿Este teléfono tiene datos sin subir al servidor? True si hay operaciones
     * rechazadas por el servidor o pendientes que llevan demasiado en la cola.
     */
    public function getSinSubirAttribute(): bool
    {
        return ($this->rechazados_sync ?? 0) > 0
            || (($this->pendientes_sync ?? 0) > 0 && ($this->pendiente_antiguo_min ?? 0) >= self::MIN_COLA_ATASCADA);
    }

    public function getBateriaColorAttribute(): string
    {
        if ($this->bateria === null) return '#6b7280';
        if ($this->bateria > 30) return '#34d399';
        if ($this->bateria > 15) return '#fbbf24';
        return '#f87171';
    }
}
