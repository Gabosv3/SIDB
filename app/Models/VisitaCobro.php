<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitaCobro extends Model
{
    protected $table = 'visitas_cobro';

    protected $fillable = [
        'cliente_id',
        'user_id',
        'gestion_cobro_id',
        'fecha_visita',
        'resultado',
        'promesa_fecha',
        'foto_hogar',
        'observaciones',
        'latitud',
        'longitud',
    ];

    protected $casts = [
        'fecha_visita'  => 'datetime',
        'promesa_fecha' => 'date',
        'latitud'       => 'decimal:8',
        'longitud'      => 'decimal:8',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gestionCobro(): BelongsTo
    {
        return $this->belongsTo(GestionCobro::class);
    }
}
