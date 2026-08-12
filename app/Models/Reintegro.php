<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Reintegro extends Model
{
    protected $fillable = [
        'venta_id',
        'cliente_id',
        'ruta_cobro_id_original',
        'orden_original',
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

    /** Estados que significan "todavía se está intentando recuperar la cuenta". */
    private const ESTADOS_ACTIVOS = ['pendiente', 'en_proceso'];

    /** Estados que significan "el proceso de recuperación ya terminó". */
    private const ESTADOS_RESUELTOS = ['recuperado', 'no_recuperado'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Reintegro $reintegro): void {
            if (! $reintegro->cliente_id) {
                return;
            }

            $cliente = Cliente::find($reintegro->cliente_id);

            if (! $cliente) {
                return;
            }

            if ($cliente->ruta_cobro_id !== null) {
                // Caso normal: el cliente todavía está en una ruta, se guarda tal cual.
                $reintegro->ruta_cobro_id_original ??= $cliente->ruta_cobro_id;
                $reintegro->orden_original ??= $cliente->orden;

                return;
            }

            // El cliente ya está fuera de ruta (por otro reintegro de una venta
            // distinta, todavía sin resolver). Heredamos la ruta/orden original
            // del reintegro más reciente de este mismo cliente, para no perder
            // el dato y poder devolverlo ahí más adelante.
            if ($reintegro->ruta_cobro_id_original === null) {
                $anterior = static::where('cliente_id', $reintegro->cliente_id)
                    ->whereNotNull('ruta_cobro_id_original')
                    ->latest('id')
                    ->first();

                if ($anterior) {
                    $reintegro->ruta_cobro_id_original = $anterior->ruta_cobro_id_original;
                    $reintegro->orden_original = $anterior->orden_original;
                }
            }
        });

        static::created(function (Reintegro $reintegro): void {
            if (! $reintegro->cliente_id) {
                return;
            }

            DB::transaction(function () use ($reintegro) {
                $cliente = Cliente::whereKey($reintegro->cliente_id)->lockForUpdate()->first();

                if (! $cliente) {
                    return;
                }

                // Si le queda otra cuenta a crédito sana (no cancelada/devuelta,
                // con saldo, y que no esté ya en otro reintegro sin resolver), el
                // cliente se queda en su ruta para que lo sigan cobrando por esa
                // cuenta. Solo sale si esta era su única cuenta activa.
                $ventaIdsEnReintegroActivo = static::where('cliente_id', $reintegro->cliente_id)
                    ->whereIn('estado', self::ESTADOS_ACTIVOS)
                    ->pluck('venta_id');

                $tieneOtraCuentaActiva = Venta::where('cliente_id', $reintegro->cliente_id)
                    ->where('id', '!=', $reintegro->venta_id)
                    ->whereIn('tipo_pago', ['credito', 'mixta'])
                    ->where('saldo_pendiente', '>', 0)
                    ->whereNotIn('estado', ['cancelada', 'devuelta'])
                    ->whereNotIn('id', $ventaIdsEnReintegroActivo)
                    ->exists();

                if (! $tieneOtraCuentaActiva) {
                    $cliente->sacarDeSuRuta('Mandado a reintegro');
                }
            });
        });

        // Si se elimina el reintegro (por error, o porque se decidió descartarlo)
        // en vez de resolverlo, se intenta devolver al cliente a su ruta original
        // — no solo cuando ya estaba "recuperado"/"no_recuperado" como hace el
        // botón manual, sino en cualquier estado, porque borrar el registro
        // significa que ya no hay nada rastreando esta recuperación.
        static::deleting(function (Reintegro $reintegro): void {
            $reintegro->restaurarRutaDelCliente();
        });
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function rutaCobroOriginal(): BelongsTo
    {
        return $this->belongsTo(RutaCobro::class, 'ruta_cobro_id_original');
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

    /**
     * El reintegro ya se resolvió (recuperado o no), el cliente sigue sin
     * ruta, no tiene otro reintegro sin resolver, y hay una ruta original
     * guardada a la cual volver.
     */
    public function puedeDevolverseARuta(): bool
    {
        if (! in_array($this->estado, self::ESTADOS_RESUELTOS, true)) {
            return false;
        }

        if ($this->ruta_cobro_id_original === null || ! $this->cliente_id) {
            return false;
        }

        if (! $this->cliente || $this->cliente->ruta_cobro_id !== null) {
            return false;
        }

        return ! static::where('cliente_id', $this->cliente_id)
            ->where('id', '!=', $this->id)
            ->whereIn('estado', self::ESTADOS_ACTIVOS)
            ->exists();
    }

    /**
     * Devuelve al cliente a la ruta (y posición) de la que se sacó al
     * mandarlo a recoger. No hace nada si no corresponde (ver puedeDevolverseARuta).
     */
    public function devolverClienteARuta(): bool
    {
        if (! $this->puedeDevolverseARuta()) {
            return false;
        }

        $this->restaurarRutaDelCliente();

        return true;
    }

    /**
     * Lógica compartida entre el botón manual "Devolver a ruta" (solo en
     * estados resueltos) y el hook de eliminación (en cualquier estado).
     */
    private function restaurarRutaDelCliente(): void
    {
        if (! $this->cliente_id || $this->ruta_cobro_id_original === null) {
            return;
        }

        DB::transaction(function () {
            $cliente = Cliente::whereKey($this->cliente_id)->lockForUpdate()->first();

            if (! $cliente || $cliente->ruta_cobro_id !== null) {
                return;
            }

            $tieneOtroReintegroActivo = static::where('cliente_id', $this->cliente_id)
                ->where('id', '!=', $this->id)
                ->whereIn('estado', self::ESTADOS_ACTIVOS)
                ->exists();

            if ($tieneOtroReintegroActivo) {
                return;
            }

            $cliente->update([
                'ruta_cobro_id' => $this->ruta_cobro_id_original,
                'orden' => $this->orden_original,
            ]);
        });
    }
}
