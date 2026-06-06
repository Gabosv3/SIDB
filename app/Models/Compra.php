<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Compra extends Model
{
    use HasFactory, LogsActivity;

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
