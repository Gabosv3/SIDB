<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Vehiculo extends Model
{
    use LogsActivity;

    protected $table = 'vehiculos';

    protected $fillable = [
        'placa',
        'tipo',
        'marca',
        'modelo',
        'anio',
        'estado',
        'asignado_a',
        'sucursal_id',
        'observaciones',
    ];

    protected $casts = [
        'anio' => 'integer',
    ];

    public function asignadoA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function vales(): HasMany
    {
        return $this->hasMany(Vale::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Vehículo {$eventName}");
    }
}
