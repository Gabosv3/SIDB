<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaileysSession extends Model
{
    protected $table = 'baileys_sessions';

    protected $fillable = [
        'user_id',
        'session_key',
        'estado',
        'numero_whatsapp',
        'nombre_whatsapp',
        'conectado_at',
        'ultima_actividad_at',
    ];

    protected $casts = [
        'conectado_at' => 'datetime',
        'ultima_actividad_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estaConectada(): bool
    {
        return $this->estado === 'conectado';
    }

    public static function sessionKeyPara(int $userId): string
    {
        return 'user_' . $userId;
    }

    /**
     * Sincroniza el estado local con lo que reportó el servidor Node, a partir de
     * la respuesta de /sessions/{id}/status (y opcionalmente /info una vez conectado).
     */
    public static function sincronizarDesdeEstado(int $userId, array $status, array $info = []): self
    {
        $session = static::firstOrCreate(
            ['user_id' => $userId],
            ['session_key' => static::sessionKeyPara($userId)]
        );

        $conectado = (bool) ($status['connected'] ?? false);
        $esperandoQr = (bool) ($status['qrCodePending'] ?? false);

        $session->update([
            'estado' => $conectado ? 'conectado' : ($esperandoQr ? 'esperando_qr' : 'desconectado'),
            'numero_whatsapp' => $info['jid'] ?? $session->numero_whatsapp,
            'nombre_whatsapp' => $info['name'] ?? $session->nombre_whatsapp,
            'conectado_at' => $conectado && ! $session->estaConectada() ? now() : $session->conectado_at,
            'ultima_actividad_at' => now(),
        ]);

        return $session;
    }
}
