<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GestionCobro extends Model
{
    protected $table = 'gestion_cobros';

    protected $fillable = [
        'venta_id',
        'cliente_id',
        'numero_cuota',
        'total_cuotas',
        'monto_cuota',
        'monto_pagado',
        'fecha_vencimiento',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'monto_cuota' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'numero_cuota' => 'integer',
        'total_cuotas' => 'integer',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function sucursal(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Sucursal::class,
            Venta::class,
            'id',
            'id',
            'venta_id',
            'sucursal_id'
        );
    }
}
