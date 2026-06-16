<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reintegro extends Model
{
    protected $fillable = [
        'venta_id',
        'cliente_id',
        'vendedor_id',
        'sucursal_id',
        'asignado_por',
        'estado',
        'motivo',
        'observaciones',
        'fecha_asignacion',
        'fecha_recuperacion',
        'monto_adeudado',
        'cuotas_vencidas',
    ];

    protected $casts = [
        'fecha_asignacion'   => 'date',
        'fecha_recuperacion' => 'date',
        'monto_adeudado'     => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function asignadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_por');
    }
}
