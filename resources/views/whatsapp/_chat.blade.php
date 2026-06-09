{{-- Partial SSR: chat activo (mismas clases que renderChat() del JS) --}}
@php
    $cuotas = \App\Models\GestionCobro::where('cliente_id', $conversation->cliente_id)
        ->whereIn('estado', ['pendiente', 'vencida'])
        ->orderBy('fecha_vencimiento')
        ->get();
@endphp

<div class="chat-topbar">
    <div class="chat-client-info">
        <div class="chat-avatar">{{ strtoupper(substr($conversation->cliente?->nombre ?? '?', 0, 1)) }}</div>
        <div>
            <div class="chat-client-name">{{ $conversation->cliente?->nombre_completo ?? 'Sin nombre' }}</div>
            <div class="chat-client-meta">📱 {{ $conversation->cliente?->telefono_whatsapp ?? '—' }}</div>
        </div>
    </div>
    <div class="chat-topbar-actions">
        <button class="icon-btn" onclick="enviarRecordatorio({{ $conversation->cliente_id }})" title="Enviar recordatorio">🔔</button>
        <button class="icon-btn" title="Más opciones">⋮</button>
    </div>
</div>

<div class="chat-messages" id="chatMessages">
    @forelse($conversation->mensajes->sortBy('created_at') as $msg)
        @php
            $t  = $msg->sent_at ?? $msg->received_at ?? $msg->created_at;
            $hr = $t?->format('H:i') ?? '';
        @endphp
        <div class="msg-row {{ $msg->direction }}">
            <div>
                <div class="msg-bubble">{{ $msg->body }}</div>
                <div class="msg-meta">
                    {{ $hr }}
                    @if($msg->direction === 'out')
                        @if($msg->status === 'read')
                            <span class="msg-check check-read">✓✓</span>
                        @else
                            <span class="msg-check check-sent">✓</span>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div style="text-align:center;color:var(--muted);padding:2rem;font-size:13px;">Sin mensajes aún</div>
    @endforelse
</div>

<div class="chat-input-area">
    <div class="quick-btns">
        <button class="quick-btn" onclick="quickMsg('recordar_cuota')">🔔 Recordar cuota</button>
        <button class="quick-btn" onclick="quickMsg('saldo_pendiente')">💰 Saldo pendiente</button>
        <button class="quick-btn" onclick="quickMsg('confirmar_pago')">✅ Confirmar pago</button>
        <button class="quick-btn" onclick="quickMsg('promesa_pago')">📅 Promesa de pago</button>
    </div>
    <div class="input-row">
        <textarea class="msg-textarea" id="msgInput" rows="1"
            placeholder="Escribe un mensaje... (Enter para enviar)"
            onkeydown="onEnter(event)" oninput="autoH(this)"></textarea>
        <button class="send-btn" id="sendBtn" onclick="enviarMensaje()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="white"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
        </button>
    </div>
</div>

<script>
    activeConvId = {{ $conversation->id }};
    document.addEventListener('DOMContentLoaded', scrollBottom);
</script>
