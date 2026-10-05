<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Compra extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => "Compra {$eventName}");
    }

    protected $table = 'compras';

    protected $fillable = [
        'numero_compra',
        'proveedor_id',
        'fecha_compra',
        'fecha_entrega_estimada',
        'fecha_entrega_real',
        'usuario_id',
        'subtotal',
        'impuesto_porcentaje',
        'impuesto_monto',
        'descuento_monto',
        'total',
        'saldo_pendiente',
        'forma_pago',
        'condicion_pago',
        'dias_credito',
        'fecha_vencimiento',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_compra'             => 'datetime',
        'fecha_entrega_estimada'   => 'date',
        'fecha_entrega_real'       => 'date',
        'fecha_vencimiento'        => 'date',
        'subtotal'                 => 'decimal:2',
        'impuesto_porcentaje'      => 'decimal:2',
        'impuesto_monto'           => 'decimal:2',
        'descuento_monto'          => 'decimal:2',
        'total'                    => 'decimal:2',
        'saldo_pendiente'          => 'decimal:2',
    ];

    protected $appends = ['dias_transcurridos', 'estado_etiqueta'];

    // ── Relationships ─────────────────────────────────────────────────────────

    /**
     * Proveedor de la compra
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Usuario que realizó la compra
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Detalles de la compra (items)
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCompra::class, 'compra_id');
    }

    /** Unidades de este producto que esta compra ya dejó en el inventario (entradas menos reversiones). */
    private function netoIngresado(int $productoId): int
    {
        $base = MovimientoStock::where('referencia', $this->numero_compra)->where('producto_id', $productoId);

        return (int) (clone $base)->where('tipo', 'entrada')->sum('cantidad')
            - (int) (clone $base)->where('tipo', 'salida')->sum('cantidad');
    }

    /**
     * Sube al inventario lo que esta compra trae. Es seguro llamarlo más de
     * una vez (marcar Recibida, volver atrás y marcar otra vez): solo ingresa
     * lo que todavía no esté ingresado con esta compra como referencia, así el
     * stock no se duplica. MovimientoStock::boot() suma el stock al crear una
     * 'entrada' y lo resta con una 'salida'.
     */
    public function ingresarStock(): void
    {
        foreach ($this->detalles()->with('producto')->get()->groupBy('producto_id') as $productoId => $lineas) {
            $faltante = (int) $lineas->sum('cantidad') - $this->netoIngresado((int) $productoId);

            if ($faltante <= 0) {
                continue;
            }

            $primera = $lineas->first();

            MovimientoStock::create([
                'producto_id' => $productoId,
                'user_id' => auth()->id() ?? $this->usuario_id,
                'sucursal_id' => $primera->producto->sucursal_id,
                'tipo' => 'entrada',
                'cantidad' => $faltante,
                'precio_unitario' => $primera->precio_unitario,
                'referencia' => $this->numero_compra,
                'observaciones' => "Compra {$this->numero_compra}",
            ]);
        }
    }

    /**
     * Quita del inventario lo que esta compra había subido (compra cancelada
     * o devuelta al proveedor). Idempotente: si ya se revirtió, no hace nada.
     */
    public function revertirStock(string $motivo = 'cancelada'): void
    {
        foreach ($this->detalles()->with('producto')->get()->groupBy('producto_id') as $productoId => $lineas) {
            $neto = $this->netoIngresado((int) $productoId);

            if ($neto <= 0) {
                continue;
            }

            $primera = $lineas->first();

            MovimientoStock::create([
                'producto_id' => $productoId,
                'user_id' => auth()->id() ?? $this->usuario_id,
                'sucursal_id' => $primera->producto->sucursal_id,
                'tipo' => 'salida',
                'cantidad' => $neto,
                'precio_unitario' => $primera->precio_unitario,
                'referencia' => $this->numero_compra,
                'observaciones' => "Compra {$this->numero_compra} {$motivo}: se descuenta lo que había ingresado",
            ]);
        }
    }

    /**
     * Pagos realizados
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(PagoCompra::class, 'compra_id');
    }

    // ── Accessors & Mutators ──────────────────────────────────────────────────

    /**
     * Obtener etiqueta del estado
     */
    public function estadoEtiqueta(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->estado) {
                'pendiente'   => 'Pendiente',
                'recibida'    => 'Recibida',
                'completada'  => 'Completada',
                'cancelada'   => 'Cancelada',
                'devuelta'    => 'Devuelta',
                default       => $this->estado
            }
        );
    }

    /**
     * Calcular días transcurridos desde la compra
     */
    public function diasTranscurridos(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->fecha_compra->diffInDays(now())
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Generar número de compra único
     */
    public static function generarNumeroPedido(): string
    {
        $ultimoNumero = self::max('numero_compra');
        $siguiente = $ultimoNumero ? intval(substr($ultimoNumero, -6)) + 1 : 1;
        return 'COM-' . now()->format('Ymd') . '-' . str_pad($siguiente, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Incrementar saldo pendiente
     */
    public function incrementarSaldoPendiente(float $monto): void
    {
        $this->saldo_pendiente += $monto;
        $this->save();
    }

    /**
     * Reducir saldo pendiente
     */
    public function reducirSaldoPendiente(float $monto): void
    {
        $this->saldo_pendiente = max(0, $this->saldo_pendiente - $monto);
        $this->save();
    }

    /**
     * Verificar si la compra está completamente pagada
     */
    public function estaPagada(): bool
    {
        return $this->saldo_pendiente <= 0;
    }

    /**
     * Obtener porcentaje de pago
     */
    public function porcentajePago(): float
    {
        if ($this->total == 0) return 0;
        return (($this->total - $this->saldo_pendiente) / $this->total) * 100;
    }
}
