<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappAutomation extends Model
{
    protected $table = 'whatsapp_automations';

    protected $fillable = [
        'nombre',
        'tipo',
        'whatsapp_account_id',
        'template_id',
        'dias_antes',
        'dias_despues',
        'hora_envio',
        'activo',
    ];

    protected $casts = [
        'activo'       => 'boolean',
        'dias_antes'   => 'integer',
        'dias_despues' => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(WhatsappAccount::class, 'whatsapp_account_id');
    }

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(WhatsappTemplate::class, 'template_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WhatsappAutomationLog::class, 'automation_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public static function tiposLabels(): array
    {
        return [
            'recordatorio_antes_vencimiento' => 'Recordatorio antes de vencimiento',
            'recordatorio_dia_vencimiento'   => 'Recordatorio día de vencimiento',
            'recordatorio_mora'              => 'Recordatorio de mora',
            'confirmacion_pago'              => 'Confirmación de pago',
            'verificacion_cobro'             => 'Verificación de cobro',
            'seguimiento_promesa_pago'       => 'Seguimiento de promesa de pago',
        ];
    }
}
