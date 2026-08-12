<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Pagare extends Model
{
    use LogsActivity;

    protected $table = 'pagares';

    protected $fillable = [
        'cliente_id',
        'user_id',
        'venta_id',
        'nombre_deudor',
        'dui',
        'direccion',
        'lugar_firma',
        'monto_financiado',
        'fecha_vencimiento',
        'pdf',
    ];

    protected $casts = [
        'monto_financiado'  => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function getPdfUrlAttribute(): ?string
    {
        return $this->pdf ? Storage::url($this->pdf) : null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Pagaré {$eventName}");
    }
}
