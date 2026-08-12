<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Cliente extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => "Cliente {$eventName}");
    }

    protected $table = 'clientes';

    protected $fillable = [
        'sucursal_id',
        'codigo_anterior',
        'nombre',
        'apellido',
        'nombre_conyuge',
        'dui',
        'dui_foto_frente',
        'dui_foto_reverso',
        'nit',
        'telefono_normal',
        'telefono_whatsapp',
        'email',
        'direccion',
        'departamento',
        'municipio',
        'distrito',
        'latitud',
        'longitud',
        'limite_credito',
        'saldo',
        'ruta_cobro_id',
        'orden',
        'activo',
        'grupo_id',
        'revisado_en',
        // Referencias familiares
        'ref_fam1_nombre',
        'ref_fam1_telefono',
        'ref_fam1_parentesco',
        'ref_fam2_nombre',
        'ref_fam2_telefono',
        'ref_fam2_parentesco',
        // Referencias conocidas
        'ref_con1_nombre',
        'ref_con1_telefono',
        'ref_con1_trabajo',
        'ref_con2_nombre',
        'ref_con2_telefono',
        'ref_con2_trabajo',
        'foto_casa',
    ];

    protected $casts = [
        'limite_credito' => 'decimal:2',
        'saldo'          => 'decimal:2',
        'activo'         => 'boolean',
        'revisado_en'    => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function rutaCobro(): BelongsTo
    {
        return $this->belongsTo(RutaCobro::class, 'ruta_cobro_id');
    }

    public function cuentasCobrar(): HasMany
    {
        return $this->hasMany(CuentaCobrar::class, 'cliente_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }

    public function pagosVenta(): HasMany
    {
        return $this->hasMany(PagoVenta::class, 'cliente_id');
    }

    public function pagares(): HasMany
    {
        return $this->hasMany(Pagare::class, 'cliente_id');
    }

    public function gestionesCobro(): HasMany
    {
        return $this->hasMany(GestionCobro::class, 'cliente_id');
    }

    /**
     * Otros clientes vinculados al mismo grupo (hogar/familia), sin incluirse a sí mismo.
     */
    public function vinculados(): HasMany
    {
        return $this->hasMany(Cliente::class, 'grupo_id', 'grupo_id')->whereKeyNot($this->id);
    }

    /**
     * Recalcula y persiste el saldo del cliente como la suma del saldo
     * pendiente de todas sus ventas. Debe llamarse cada vez que una venta
     * o un pago que le pertenece cambia.
     */
    public static function recalcularSaldo(int $clienteId): void
    {
        static::whereKey($clienteId)->update([
            'saldo' => Venta::where('cliente_id', $clienteId)->sum('saldo_pendiente'),
        ]);
    }

    public function whatsappConversacion(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WhatsappConversation::class, 'cliente_id')->latestOfMany();
    }

    /**
     * Saca al cliente de su ruta de cobro activa (deja de aparecer en el
     * recorrido diario del cobrador). Usado cuando una cuenta se manda a
     * recoger o se cancela y no le queda ninguna otra cuenta activa.
     */
    public static function sacarDeRuta(int $clienteId): void
    {
        static::whereKey($clienteId)->first()?->sacarDeSuRuta();
    }

    /** Igual que sacarDeRuta(), pero sobre una instancia ya cargada (evita una consulta extra). */
    /**
     * Saca al cliente de su ruta de cobro activa (venta pagada al 100%,
     * cancelada/devuelta, o mandado a reintegro) y deja constancia en el
     * mismo historial de movimientos de ruta que ya usan los cambios
     * manuales (Activity log 'cliente_ruta_cambio'), para no perder de
     * vista por qué salió.
     */
    public function sacarDeSuRuta(string $motivo = 'Salió de la ruta'): void
    {
        $rutaAnterior = $this->rutaCobro;

        $this->update(['ruta_cobro_id' => null, 'orden' => null]);

        if (! $rutaAnterior) {
            return;
        }

        $nombreCobrador = $rutaAnterior->cobrador
            ? trim($rutaAnterior->cobrador->nombre.' '.$rutaAnterior->cobrador->apellido)
            : null;

        activity('cliente_ruta_cambio')
            ->causedBy(auth()->check() ? auth()->user() : null)
            ->performedOn($this)
            ->withProperties([
                'ruta_anterior_id' => $rutaAnterior->id,
                'ruta_anterior_nombre' => $rutaAnterior->nombre,
                'cobrador_anterior' => $nombreCobrador,
                'ruta_nueva_id' => null,
                'ruta_nueva_nombre' => null,
                'cobrador_nuevo' => null,
            ])
            ->log(sprintf('%s (de "%s")', $motivo, $rutaAnterior->nombre));
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }
}
