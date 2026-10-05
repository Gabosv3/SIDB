<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuadreCaja extends Model
{
    protected $table = 'cuadres_caja';

    protected $fillable = [
        'fecha', 'user_id', 'cuadrado_por', 'cobros_efectivo', 'ventas_contado',
        'gastos', 'esperado', 'recibido', 'diferencia', 'nota',
    ];

    protected $casts = [
        'fecha' => 'date',
        'cobros_efectivo' => 'decimal:2',
        'ventas_contado' => 'decimal:2',
        'gastos' => 'decimal:2',
        'esperado' => 'decimal:2',
        'recibido' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cuadradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cuadrado_por');
    }
}
