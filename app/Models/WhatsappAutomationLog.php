<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappAutomationLog extends Model
{
    protected $table = 'whatsapp_automation_logs';

    protected $fillable = [
        'automation_id',
        'cliente_id',
        'cuota_id',
        'whatsapp_message_id',
        'tipo',
        'estado',
        'error',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function automatizacion(): BelongsTo
    {
        return $this->belongsTo(WhatsappAutomation::class, 'automation_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(GestionCobro::class, 'cuota_id');
    }

    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(WhatsappMessage::class, 'whatsapp_message_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Verifica si ya se envió este recordatorio hoy para esta cuota.
     */
    public static function yaEnviadoHoy(int $automationId, int $clienteId, int $cuotaId): bool
    {
        return self::where('automation_id', $automationId)
            ->where('cliente_id', $clienteId)
            ->where('cuota_id', $cuotaId)
            ->whereDate('created_at', today())
            ->whereIn('estado', ['pending', 'sent'])
            ->exists();
    }
}
