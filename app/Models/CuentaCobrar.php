<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentaCobrar extends Model
{
    use HasFactory;

    protected $table = 'cuentas_cobrar';

    protected $fillable = [
        'cliente_id',
        'concepto',
        'monto',
        'fecha_vencimiento',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'monto'             => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
