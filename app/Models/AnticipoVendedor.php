<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnticipoVendedor extends Model
{
    protected $table = 'anticipos_vendedor';

    protected $fillable = [
        'vendedor_id',
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

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class);
    }

    public function autorizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }
}
