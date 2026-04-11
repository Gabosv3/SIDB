<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Venta extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => "Venta {$eventName}");
    }

    protected $table = 'ventas';

    protected $fillable = [
        'numero_venta',
        'cliente_id',
        'user_id',
        'vendedor_id',
        'sucursal_id',
        'fecha_venta',
        'estado',
        'tipo_pago',
        'subtotal',
        'descuento_porcentaje',
        'descuento_monto',
        'impuesto_porcentaje',
        'impuesto_monto',
        'total',
        'monto_pagado',
        'saldo_pendiente',
        'fecha_pago_limite',
        'dias_credito',
        'observaciones',
    ];

    protected $casts = [
        'fecha_venta'          => 'datetime',
        'subtotal'             => 'decimal:2',
        'descuento_porcentaje' => 'decimal:2',
        'descuento_monto'      => 'decimal:2',
        'impuesto_porcentaje'  => 'decimal:2',
        'impuesto_monto'       => 'decimal:2',
        'total'                => 'decimal:2',
        'monto_pagado'         => 'decimal:2',
        'saldo_pendiente'      => 'decimal:2',        'fecha_pago_limite'    => 'date',
        'dias_credito'         => 'integer',    ];

    // ── Boot: generar número de venta ─────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Venta $venta): void {
            if (empty($venta->numero_venta)) {
                $venta->numero_venta = 'VNT-' . strtoupper(Str::random(8));
            }
            if (empty($venta->fecha_venta)) {
                $venta->fecha_venta = now();
            }
            if (empty($venta->user_id)) {
                $venta->user_id = auth()->id();
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class, 'vendedor_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoVenta::class, 'venta_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function estaCompletada(): bool
    {
        return $this->estado === 'completada';
    }

    public function tieneSaldo(): bool
    {
        return (float) $this->saldo_pendiente > 0;
    }
}
