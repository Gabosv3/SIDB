<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

// Encuesta que el supervisor le hace directo al cliente para contrastar lo
// que dice contra lo que el sistema tiene registrado — control anti-fraude,
// no una evaluación del cobrador en general (eso es Supervision).
class EncuestaCliente extends Model
{
    use LogsActivity;

    protected $table = 'encuestas_cliente';

    protected $fillable = [
        'supervisor_id',
        'cliente_id',
        'cobrador_id',
        'fecha',
        'monto_frecuencia_pago',
        'cobrador_reportado_cliente',
        'recibio_comprobante',
        'ultimo_pago_monto_cliente',
        'ultimo_pago_fecha_cliente',
        'saldo_informado_cliente',
        'pago_registrado_bm',
        'saldo_registrado_bm',
        'diferencia',
        'resultado',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'recibio_comprobante' => 'boolean',
        'ultimo_pago_monto_cliente' => 'decimal:2',
        'ultimo_pago_fecha_cliente' => 'date',
        'saldo_informado_cliente' => 'decimal:2',
        'pago_registrado_bm' => 'decimal:2',
        'saldo_registrado_bm' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Supervisor::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function cobrador(): BelongsTo
    {
        return $this->belongsTo(Cobrador::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Encuesta a cliente {$eventName}");
    }
}
