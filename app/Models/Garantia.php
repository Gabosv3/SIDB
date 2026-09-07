<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Garantia extends Model
{
    use LogsActivity;

    protected $table = 'garantias';

    protected $fillable = [
        'venta_id',
        'cliente_id',
        'sucursal_id',
        'reportado_por',
        'asignado_a',
        'cobrador_id',
        'estado',
        'motivo',
        'descripcion',
        'resolucion',
        'fotos',
        'fecha_reporte',
        'fecha_resolucion',
    ];

    protected $casts = [
        'fecha_reporte'    => 'date',
        'fecha_resolucion' => 'date',
        'fotos'            => 'array',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function reportadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reportado_por');
    }

    public function asignadoA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }

    // Cobrador de la ruta del cliente al momento del reporte — quien debe
    // pasar a recoger el producto en su próxima visita.
    public function cobrador(): BelongsTo
    {
        return $this->belongsTo(Cobrador::class);
    }

    // URLs completas de las fotos, listas para mostrar/enviar — el campo
    // `fotos` en BD solo guarda las rutas relativas del disco `public`.
    public function getFotosUrlsAttribute(): array
    {
        return collect($this->fotos ?? [])
            ->map(fn (string $path) => asset('storage/'.$path))
            ->values()
            ->all();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Garantía {$eventName}");
    }
}
