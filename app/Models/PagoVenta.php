<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoVenta extends Model
{
    use HasFactory;

    protected $table = 'pago_ventas';

    protected $fillable = [
        'venta_id',
        'cliente_id',
        'ruta_cobro_id',
        'user_id',
        'numero_recibo',
        'monto',
        'fecha_pago',
        'metodo_pago',
        'referencia',
        'observaciones',
        'anulado_en',
        'anulado_por',
        'motivo_anulacion',
    ];

    protected $casts = [
        'monto'      => 'decimal:2',
        'fecha_pago' => 'date',
        'anulado_en' => 'datetime',
    ];

    // ── Boot: actualizar monto_pagado y saldo de la venta ─────────────────────

    protected static function boot(): void
    {
        parent::boot();

        // Guarda una foto de la ruta del cliente al momento del cobro. Es
        // necesaria porque ruta_cobro_id del cliente cambia (se pone en null)
        // cuando la venta se cancela/completa y sale de su ruta — sin esta
        // foto, el cobro ya registrado desaparecería del resumen del día
        // porque dejaría de encajar en cualquier ruta.
        static::creating(function (PagoVenta $pago): void {
            if (! $pago->ruta_cobro_id && $pago->cliente_id) {
                $pago->ruta_cobro_id = Cliente::find($pago->cliente_id)?->ruta_cobro_id;
            }
        });

        static::created(function (PagoVenta $pago): void {
            $venta = $pago->venta;
            if ($venta) {
                // Un pago anulado sigue en la tabla pero nunca vuelve a contar
                // en el total pagado de la venta.
                $totalPagado = $venta->prima + $venta->pagos()->whereNull('anulado_en')->sum('monto');
                $venta->monto_pagado    = $totalPagado;
                $venta->saldo_pendiente = max(0, $venta->total - $totalPagado);
                if ($venta->saldo_pendiente <= 0) {
                    $venta->estado = 'completada';
                }
                $venta->save();
            }
            // Actualizar saldo del cliente
            if ($pago->cliente_id) {
                Cliente::recalcularSaldo($pago->cliente_id);
            }
        });

        static::deleting(function (PagoVenta $pago): void {
            $venta = $pago->venta;
            if ($venta) {
                $totalPagado = $venta->prima + $venta->pagos()->where('id', '!=', $pago->id)->whereNull('anulado_en')->sum('monto');
                $venta->monto_pagado    = $totalPagado;
                $venta->saldo_pendiente = max(0, $venta->total - $totalPagado);
                if ($venta->saldo_pendiente > 0 && $venta->estado === 'completada') {
                    $venta->estado = 'pendiente';
                }
                $venta->save();
            }
            // Actualizar saldo del cliente (se había quedado desactualizado tras eliminar un pago).
            // $venta ya se guardó arriba con su saldo_pendiente recalculado, por lo que esta
            // suma ya lo refleja.
            if ($pago->cliente_id) {
                Cliente::recalcularSaldo($pago->cliente_id);
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }
}