<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Venta extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => "Venta {$eventName}");
    }

    protected $table = 'ventas';

    protected $fillable = [
        'numero_venta',
        'cliente_id',
        'user_id',
        'vendedor_id',
        'sucursal_id',
        'fecha_venta',
        'estado',
        'tipo_pago',
        'subtotal',
        'descuento_porcentaje',
        'descuento_monto',
        'impuesto_porcentaje',
        'impuesto_monto',
        'total',
        'prima',
        'monto_pagado',
        'saldo_pendiente',
        'fecha_pago_limite',
        'dias_credito',
        'observaciones',
    ];

    protected $casts = [
        'fecha_venta'          => 'datetime',
        'subtotal'             => 'decimal:2',
        'descuento_porcentaje' => 'decimal:2',
        'descuento_monto'      => 'decimal:2',
        'impuesto_porcentaje'  => 'decimal:2',
        'impuesto_monto'       => 'decimal:2',
        'total'                => 'decimal:2',
        'prima'                => 'decimal:2',
        'monto_pagado'         => 'decimal:2',
        'saldo_pendiente'      => 'decimal:2',        'fecha_pago_limite'    => 'date',
        'dias_credito'         => 'integer',    ];

    // ── Boot: generar número de venta ─────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Venta $venta): void {
            if (empty($venta->numero_venta)) {
                $venta->numero_venta = $venta->vendedor_id
                    ? static::siguienteNumeroTicket((int) $venta->vendedor_id)
                    : 'VNT-' . strtoupper(Str::random(8));
            }
            if (empty($venta->fecha_venta)) {
                $venta->fecha_venta = now();
            }
            if (empty($venta->user_id)) {
                $venta->user_id = auth()->id();
            }
        });

        // Al eliminar la venta, sus pagos se borran por cascada a nivel de BD
        // (no disparan los hooks de PagoVenta), así que el saldo del cliente
        // se recalcula aquí explícitamente.
        static::deleted(function (Venta $venta): void {
            if ($venta->cliente_id) {
                Cliente::recalcularSaldo($venta->cliente_id);
            }
        });

        // Al cancelar, devolver, o terminar de pagar una venta, el cliente sale
        // de su ruta de cobro activa — pero solo si esta era su única cuenta a
        // crédito con saldo. Si le queda otra venta activa, se queda en la ruta
        // para que lo sigan cobrando por esa.
        static::saved(function (Venta $venta): void {
            if (! $venta->wasChanged('estado') || ! in_array($venta->estado, ['cancelada', 'devuelta', 'completada'], true) || ! $venta->cliente_id) {
                return;
            }

            DB::transaction(function () use ($venta) {
                // Bloquea la fila del cliente para que dos cancelaciones casi
                // simultáneas de sus últimas cuentas activas no se "pisen" (cada
                // una viendo a la otra como todavía activa y ninguna limpiando la ruta).
                $cliente = Cliente::whereKey($venta->cliente_id)->lockForUpdate()->first();

                if (! $cliente) {
                    return;
                }

                $tieneOtraCuentaActiva = static::where('cliente_id', $venta->cliente_id)
                    ->where('id', '!=', $venta->id)
                    ->whereIn('tipo_pago', ['credito', 'mixta'])
                    ->where('saldo_pendiente', '>', 0)
                    ->whereNotIn('estado', ['cancelada', 'devuelta'])
                    ->exists();

                if (! $tieneOtraCuentaActiva) {
                    $motivo = match ($venta->estado) {
                        'completada' => "Venta {$venta->numero_venta} pagada al 100%",
                        'cancelada' => "Venta {$venta->numero_venta} cancelada",
                        'devuelta' => "Venta {$venta->numero_venta} devuelta",
                        default => 'Salió de la ruta',
                    };
                    $cliente->sacarDeSuRuta($motivo);
                }
            });
        });

        // Aviso a los super_admin (campanita de notificaciones) cuando una
        // venta se cancela o se devuelve — antes esto pasaba en silencio, sin
        // que el admin se enterara salvo que entrara a revisar manualmente.
        static::saved(function (Venta $venta): void {
            if (! $venta->wasChanged('estado') || ! in_array($venta->estado, ['cancelada', 'devuelta'], true)) {
                return;
            }

            $cliente = $venta->cliente_id ? Cliente::find($venta->cliente_id) : null;
            $accion = $venta->estado === 'cancelada' ? 'cancelada' : 'devuelta';

            $notificacion = \Filament\Notifications\Notification::make()
                ->title('Venta ' . $accion)
                ->body(sprintf(
                    '%s (%s) — %s',
                    $venta->numero_venta,
                    $cliente?->nombre_completo ?? 'cliente #' . $venta->cliente_id,
                    $venta->observaciones ?: 'sin motivo indicado'
                ))
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->warning()
                ->toDatabase();

            // sendNow() en vez de sendToDatabase(): la notificación de Filament
            // implementa ShouldQueue, y este proyecto no tiene un worker de cola
            // corriendo — con sendToDatabase() se quedaría encolada para siempre
            // y nunca llegaría a la campanita.
            \Illuminate\Support\Facades\Notification::sendNow(
                User::role('super_admin')->get(),
                $notificacion
            );
        });
    }

    // Numeración de ticket propia por vendedor, igual que los recibos de
    // cobro (REC-{cobradorId}-{000001}). Se apoya en una fila-contador con
    // lockForUpdate para que dos ventas casi simultáneas del mismo vendedor
    // nunca reciban el mismo número, y cada vendedor lleva su propia serie
    // en vez de un folio único compartido para todos.
    private static function siguienteNumeroTicket(int $vendedorId): string
    {
        $contador = VendedorTicketsContador::where('vendedor_id', $vendedorId)->lockForUpdate()->first();
        if (! $contador) {
            VendedorTicketsContador::create(['vendedor_id' => $vendedorId, 'ultimo_numero' => 0]);
            $contador = VendedorTicketsContador::where('vendedor_id', $vendedorId)->lockForUpdate()->first();
        }

        $siguiente = $contador->ultimo_numero + 1;
        $contador->update(['ultimo_numero' => $siguiente]);

        return sprintf('TCK-%d-%06d', $vendedorId, $siguiente);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class, 'vendedor_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoVenta::class, 'venta_id');
    }

    public function gestionesCobro(): HasMany
    {
        return $this->hasMany(GestionCobro::class, 'venta_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function estaCompletada(): bool
    {
        return $this->estado === 'completada';
    }

    public function tieneSaldo(): bool
    {
        return (float) $this->saldo_pendiente > 0;
    }

    /**
     * Calcula si el cliente va adelantado, atrasado o al día en sus cuotas.
     *
     * @return array{
     *   estado: 'adelantado'|'atrasado'|'al_dia'|'completado',
     *   diferencia: int,          // cuotas de diferencia (positivo=adelantado, negativo=atrasado)
     *   cuotas_esperadas: int,    // cuotas que deberían haberse pagado a hoy
     *   cuotas_cobradas: int,     // cuotas marcadas como cobrado
     *   cuotas_pendientes: int,   // cuotas que aún faltan
     *   proxima_fecha: string|null
     * }
     */
    public function estadoCuotas(): array
    {
        $hoy = now()->startOfDay();

        $cuotas = $this->gestionesCobro()->orderBy('numero_cuota')->get();

        if ($cuotas->isEmpty()) {
            return [
                'estado'           => 'completado',
                'diferencia'       => 0,
                'cuotas_esperadas' => 0,
                'cuotas_cobradas'  => 0,
                'cuotas_pendientes'=> 0,
                'proxima_fecha'    => null,
            ];
        }

        // Cuántas deberían estar pagadas hoy (fecha_vencimiento <= hoy)
        $esperadas = $cuotas->filter(
            fn($c) => \Carbon\Carbon::parse($c->fecha_vencimiento)->startOfDay()->lte($hoy)
        )->count();

        // Cuántas ya fueron cobradas completas
        $cobradas   = $cuotas->where('estado', 'cobrado')->count();

        // Cuota parcial (si existe)
        $parcial = $cuotas->where('estado', 'parcialmente_cobrado')->first();

        // Pendientes (incluyendo la parcial como no terminada)
        $pendientes = $cuotas->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])->count();

        // Próxima cuota sin cobrar (parcial primero, luego pendiente)
        $proxima = $parcial
            ?? $cuotas->where('estado', 'pendiente')->sortBy('fecha_vencimiento')->first();

        // Diferencia: la cuota parcial cuenta como "en progreso", no como cobrada
        $diferencia = $cobradas - $esperadas;

        if ($pendientes === 0) {
            $estado = 'completado';
        } elseif ($diferencia > 0) {
            $estado = 'adelantado';
        } elseif ($diferencia < 0) {
            $estado = 'atrasado';
        } else {
            $estado = 'al_dia';
        }

        return [
            'estado'            => $estado,
            'diferencia'        => abs($diferencia),
            'cuotas_esperadas'  => $esperadas,
            'cuotas_cobradas'   => $cobradas,
            'cuotas_pendientes' => $pendientes,
            'proxima_fecha'     => $proxima
                ? \Carbon\Carbon::parse($proxima->fecha_vencimiento)->format('d/m/Y')
                : null,
            // Detalle de la cuota parcial
            'parcial_numero'    => $parcial?->numero_cuota,
            'parcial_pagado'    => $parcial ? (float) $parcial->monto_pagado : null,
            'parcial_total'     => $parcial ? (float) $parcial->monto_cuota  : null,
        ];
    }
}
