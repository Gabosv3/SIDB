<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'conversation_id',
        'wa_message_id',
        'direction',
        'type',
        'body',
        'status',
        'payload',
        'sent_at',
        'received_at',
    ];

    protected $casts = [
        'payload'     => 'array',
        'sent_at'     => 'datetime',
        'received_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsappConversation::class, 'conversation_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function esSaliente(): bool
    {
        return $this->direction === 'out';
    }

    public function esEntrante(): bool
    {
        return $this->direction === 'in';
    }
}
