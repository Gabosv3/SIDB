<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CobradorRecibosContador extends Model
{
    const CREATED_AT = null;

    protected $table = 'cobrador_recibos_contador';

    protected $primaryKey = 'cobrador_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'cobrador_id',
        'ultimo_numero',
    ];

    protected $casts = [
        'ultimo_numero' => 'integer',
    ];
}
