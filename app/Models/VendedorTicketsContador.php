<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendedorTicketsContador extends Model
{
    const CREATED_AT = null;

    protected $table = 'vendedor_tickets_contador';

    protected $primaryKey = 'vendedor_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'vendedor_id',
        'ultimo_numero',
    ];

    protected $casts = [
        'ultimo_numero' => 'integer',
    ];
}
