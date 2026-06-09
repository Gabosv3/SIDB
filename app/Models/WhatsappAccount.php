<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class WhatsappAccount extends Model
{
    protected $table = 'whatsapp_accounts';

    protected $fillable = [
        'user_id',
        'tipo',
        'nombre',
        'meta_business_id',
        'waba_id',
        'phone_number_id',
        'display_phone_number',
        'verified_name',
        'access_token',
        'token_expires_at',
        'webhook_status',
        'estado',
        'is_default',
    ];

    protected $casts = [
        'is_default'       => 'boolean',
        'token_expires_at' => 'datetime',
    ];

    // Ocultar token en serialización JSON
    protected $hidden = ['access_token'];

    // ── Accessors para token encriptado ───────────────────────────────────────

    public function setAccessTokenAttribute(?string $value): void
    {
        $this->attributes['access_token'] = $value ? encrypt($value) : null;
    }

    public function getAccessTokenDecryptedAttribute(): ?string
    {
        try {
            return $this->attributes['access_token']
                ? decrypt($this->attributes['access_token'])
                : null;
        } catch (\Exception) {
            return null;
        }
    }

    public function getTokenMaskedAttribute(): string
    {
        if (! isset($this->attributes['access_token']) || ! $this->attributes['access_token']) {
            return 'No configurado';
        }
        return '••••••••••••' . substr($this->access_token_decrypted ?? '', -4);
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversaciones(): HasMany
    {
        return $this->hasMany(WhatsappConversation::class, 'whatsapp_account_id');
    }

    public function plantillas(): HasMany
    {
        return $this->hasMany(WhatsappTemplate::class, 'whatsapp_account_id');
    }

    public function automatizaciones(): HasMany
    {
        return $this->hasMany(WhatsappAutomation::class, 'whatsapp_account_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function estaActivo(): bool
    {
        return $this->estado === 'activo';
    }

    public function estaConfigurado(): bool
    {
        return filled($this->phone_number_id) && filled($this->attributes['access_token']);
    }

    /**
     * Obtener la cuenta activa para un cliente dado.
     * Orden: cobrador del cliente → vendedor de la venta → cuenta default.
     */
    public static function resolverParaCliente(Cliente $cliente): ?self
    {
        // 1. Cobrador de la ruta del cliente
        $cobrador = $cliente->rutaCobro?->cobrador;
        if ($cobrador?->user_id) {
            $cuenta = self::where('user_id', $cobrador->user_id)
                ->where('estado', 'activo')
                ->first();
            if ($cuenta) return $cuenta;
        }

        // 2. Vendedor de la última venta activa
        $venta = $cliente->ventas()->where('saldo_pendiente', '>', 0)->latest()->first();
        if ($venta?->vendedor?->user_id) {
            $cuenta = self::where('user_id', $venta->vendedor->user_id)
                ->where('estado', 'activo')
                ->first();
            if ($cuenta) return $cuenta;
        }

        // 3. Cuenta default de la empresa
        return self::where('is_default', true)->where('estado', 'activo')->first();
    }
}
