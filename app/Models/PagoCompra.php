<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoCompra extends Model
{
    use HasFactory;

    protected $table = 'pagos_compra';

    protected $fillable = [
        'compra_id',
        'fecha_pago',
        'monto',
        'forma_pago',
        'referencia_pago',
        'usuario_id',
        'observaciones',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto'      => 'decimal:2',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    /**
     * Compra a la que corresponde el pago
     */
    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    /**
     * Usuario que registró el pago
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
