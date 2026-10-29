<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Operación de la app móvil que no se pudo subir al servidor (rechazada o
 * atascada). La reporta la app; el panel de monitoreo POS la muestra.
 */
class SyncError extends Model
{
    protected $table = 'sync_errores';

    protected $fillable = [
        'user_id', 'pos_device_id', 'client_id', 'tipo', 'metodo', 'endpoint', 'etiqueta',
        'monto', 'http_status', 'motivo', 'app_version', 'ocurrido_en', 'resuelto_en', 'resuelto_por',
    ];

    protected $casts = [
        'monto'       => 'float',
        'http_status' => 'integer',
        'ocurrido_en' => 'datetime',
        'resuelto_en' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dispositivo(): BelongsTo
    {
        return $this->belongsTo(PosDevice::class, 'pos_device_id');
    }

    public function scopePendientes($query)
    {
        return $query->whereNull('resuelto_en');
    }
}
