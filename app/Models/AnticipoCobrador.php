<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnticipoCobrador extends Model
{
    protected $table = 'anticipos_cobrador';

    protected $fillable = [
        'cobrador_id',
        'autorizado_por',
        'monto',
        'fecha',
        'semana_inicio',
        'semana_fin',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'fecha'         => 'date',
        'semana_inicio' => 'date',
        'semana_fin'    => 'date',
        'monto'         => 'decimal:2',
    ];

    public function cobrador(): BelongsTo
    {
        return $this->belongsTo(Cobrador::class);
    }

    public function autorizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }
}
