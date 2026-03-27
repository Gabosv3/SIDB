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
        'user_id',
        'monto',
        'fecha_pago',
        'metodo_pago',
        'referencia',
        'observaciones',
    ];

    protected $casts = [
        'monto'      => 'decimal:2',
        'fecha_pago' => 'date',
    ];

    // ── Boot: actualizar monto_pagado y saldo de la venta ─────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (PagoVenta $pago): void {
            $venta = $pago->venta;
            if ($venta) {
                $totalPagado = $venta->pagos()->sum('monto');
                $venta->monto_pagado    = $totalPagado;
                $venta->saldo_pendiente = max(0, $venta->total - $totalPagado);
                if ($venta->saldo_pendiente <= 0) {
                    $venta->estado = 'completada';
                }
                $venta->save();
            }
            // Actualizar saldo del cliente
            if ($pago->cliente_id) {
                $cliente = $pago->cliente;
                if ($cliente) {
                    $saldoTotal = \App\Models\Venta::where('cliente_id', $pago->cliente_id)
                        ->sum('saldo_pendiente');
                    $cliente->saldo = $saldoTotal;
                    $cliente->save();
                }
            }
        });

        static::deleting(function (PagoVenta $pago): void {
            $venta = $pago->venta;
            if ($venta) {
                $totalPagado = $venta->pagos()->where('id', '!=', $pago->id)->sum('monto');
                $venta->monto_pagado    = $totalPagado;
                $venta->saldo_pendiente = max(0, $venta->total - $totalPagado);
                if ($venta->saldo_pendiente > 0 && $venta->estado === 'completada') {
                    $venta->estado = 'pendiente';
                }
                $venta->save();
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
}