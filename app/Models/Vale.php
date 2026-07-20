<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Vale extends Model
{
    use LogsActivity;

    protected $table = 'vales';

    protected $fillable = [
        'user_id',
        'sucursal_id',
        'tipo',
        'vehiculo_id',
        'categoria_vehiculo',
        'monto',
        'comprobante',
        'descripcion',
        'fecha_gasto',
        'estado',
        'aprobado_por',
        'fecha_aprobado',
        'observaciones_admin',
    ];

    protected $casts = [
        'monto'          => 'decimal:2',
        'fecha_gasto'    => 'date',
        'fecha_aprobado' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function getComprobanteUrlAttribute(): ?string
    {
        return $this->comprobante ? Storage::url($this->comprobante) : null;
    }

    public function aprobar(User $admin): bool
    {
        if ($this->estado !== 'pendiente') {
            return false;
        }

        return $this->update([
            'estado'         => 'aprobado',
            'aprobado_por'   => $admin->id,
            'fecha_aprobado' => now(),
        ]);
    }

    public function rechazar(User $admin, ?string $observaciones = null): bool
    {
        if ($this->estado !== 'pendiente') {
            return false;
        }

        return $this->update([
            'estado'               => 'rechazado',
            'aprobado_por'         => $admin->id,
            'fecha_aprobado'       => now(),
            'observaciones_admin'  => $observaciones,
        ]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Vale {$eventName}");
    }
}
