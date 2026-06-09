<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappTemplate extends Model
{
    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'whatsapp_account_id',
        'name',
        'language',
        'category',
        'status',
        'body',
        'components',
    ];

    protected $casts = [
        'components' => 'array',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(WhatsappAccount::class, 'whatsapp_account_id');
    }

    public function automatizaciones(): HasMany
    {
        return $this->hasMany(WhatsappAutomation::class, 'template_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Reemplaza variables {{nombre}}, {{monto}}, {{fecha}} en el body.
     */
    public function renderConVariables(array $variables): string
    {
        $body = $this->body ?? '';
        foreach ($variables as $key => $value) {
            $body = str_replace("{{{$key}}}", $value, $body);
        }
        return $body;
    }

    public function estaAprobada(): bool
    {
        return $this->status === 'approved';
    }
}
