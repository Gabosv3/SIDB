<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Preventa extends Model
{
    use LogsActivity;

    protected $fillable = [
        'cliente_id',
        'user_id',
        'vendedor_id',
        'sucursal_id',
        'venta_id',
        'estado',
        'monto_estimado',
        'observaciones',
        'fecha',
    ];

    protected $casts = [
        'monto_estimado' => 'decimal:2',
        'fecha'          => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Preventa $preventa): void {
            $preventa->fecha ??= today();
        });
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePreventa::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Preventa {$eventName}");
    }
}
