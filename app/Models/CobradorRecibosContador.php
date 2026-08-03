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

    /**
     * Siguiente número de recibo correlativo del cobrador, de forma atómica
     * (autoritativo en el servidor, nunca en el dispositivo/cliente). Debe
     * llamarse dentro de una transacción de BD ya abierta.
     *
     * Único punto de generación de REC-{cobrador}-{######} — cualquier lugar
     * del sistema (móvil o panel web) que registre un cobro en vivo debe
     * pasar por acá para que el correlativo sea consistente sin importar
     * desde dónde se cobró.
     */
    public static function siguienteNumeroRecibo(int $cobradorId): string
    {
        $contador = self::where('cobrador_id', $cobradorId)->lockForUpdate()->first();
        if (! $contador) {
            self::create(['cobrador_id' => $cobradorId, 'ultimo_numero' => 0]);
            $contador = self::where('cobrador_id', $cobradorId)->lockForUpdate()->first();
        }

        $siguiente = $contador->ultimo_numero + 1;
        $contador->update(['ultimo_numero' => $siguiente]);

        return sprintf('REC-%d-%06d', $cobradorId, $siguiente);
    }
}
