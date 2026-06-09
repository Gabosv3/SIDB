<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WhatsappConversation extends Model
{
    protected $table = 'whatsapp_conversations';

    protected $fillable = [
        'cliente_id',
        'user_id',
        'whatsapp_account_id',
        'wa_id',
        'phone_number_id',
        'estado',
        'ultimo_mensaje',
        'ultimo_mensaje_at',
    ];

    protected $casts = [
        'ultimo_mensaje_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(WhatsappAccount::class, 'whatsapp_account_id');
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(WhatsappMessage::class, 'conversation_id')->orderBy('created_at');
    }

    public function ultimoMensaje(): HasOne
    {
        return $this->hasOne(WhatsappMessage::class, 'conversation_id')->latestOfMany();
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function estaAbierta(): bool
    {
        return $this->estado === 'abierta';
    }

    public function cerrar(): void
    {
        $this->update(['estado' => 'cerrada']);
    }

    public function abrir(): void
    {
        $this->update(['estado' => 'abierta']);
    }
}
