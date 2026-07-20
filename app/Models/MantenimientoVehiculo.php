<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MantenimientoVehiculo extends Model
{
    protected $table = 'mantenimientos_vehiculo';

    protected $fillable = [
        'vehiculo_id',
        'fecha',
        'kilometraje',
        'tipo',
        'descripcion',
        'costo',
        'taller',
        'proximo_cambio_km',
        'comprobante',
        'registrado_por',
    ];

    protected $casts = [
        'fecha'             => 'date',
        'kilometraje'       => 'integer',
        'costo'             => 'decimal:2',
        'proximo_cambio_km' => 'integer',
    ];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function getComprobanteUrlAttribute(): ?string
    {
        return $this->comprobante ? Storage::url($this->comprobante) : null;
    }
}
