<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

// Evaluación que un supervisor llena sobre el cobrador titular de una ruta
// que visitó — no es un cobro, es un formulario de auditoría de campo.
class Supervision extends Model
{
    use LogsActivity;

    protected $table = 'supervisiones';

    protected $fillable = [
        'supervisor_id',
        'cobrador_id',
        'ruta_cobro_id',
        'fecha',
        'visito_clientes_correctos',
        'efectivo_cuadrado',
        'calificacion',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'visito_clientes_correctos' => 'boolean',
        'efectivo_cuadrado' => 'boolean',
        'calificacion' => 'integer',
    ];

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Supervisor::class);
    }

    public function cobrador(): BelongsTo
    {
        return $this->belongsTo(Cobrador::class);
    }

    public function rutaCobro(): BelongsTo
    {
        return $this->belongsTo(RutaCobro::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Supervisión {$eventName}");
    }
}
