{{-- Partial SSR: panel derecho de detalles del cliente --}}
@php
    $cliente = $conversation->cliente;
    $cuotas  = \App\Models\GestionCobro::where('cliente_id', $conversation->cliente_id)
        ->whereIn('estado', ['pendiente', 'vencida'])
        ->orderBy('fecha_vencimiento')
        ->get();
@endphp

<div class="detail-section">
    <div class="detail-section-title">👤 Detalle del cliente</div>
    <div class="detail-row">
        <span class="detail-key">Cliente</span>
        <span class="detail-val">{{ $cliente?->nombre_completo ?? 'N/A' }}</span>
    </div>
    <div class="detail-row">
        <span class="detail-key">WhatsApp</span>
        <span class="detail-val">{{ $cliente?->telefono_whatsapp ?? '—' }}</span>
    </div>
    <div class="detail-row">
        <span class="detail-key">Teléfono</span>
        <span class="detail-val">{{ $cliente?->telefono ?? '—' }}</span>
    </div>
    <div class="detail-row">
        <span class="detail-key">Saldo</span>
        <span class="detail-val danger">${{ number_format($cliente?->saldo ?? 0, 2) }}</span>
    </div>
    @if($cliente?->rutaCobro)
    <div class="detail-row">
        <span class="detail-key">Ruta</span>
        <span class="detail-val">{{ $cliente->rutaCobro->nombre }}</span>
    </div>
    @endif
</div>

@if($cuotas->isNotEmpty())
<div class="detail-section">
    <div class="detail-section-title">📋 Cuotas pendientes</div>
    @foreach($cuotas as $cuota)
        <div class="cuota-card {{ $cuota->estado === 'vencida' ? 'vencida' : '' }}">
            <div class="cuota-num">Cuota {{ $cuota->numero_cuota }}/{{ $cuota->total_cuotas }}</div>
            <div class="cuota-monto">${{ number_format($cuota->monto_cuota, 2) }}</div>
            <div class="cuota-fecha">Vence: {{ $cuota->fecha_vencimiento?->format('d/m/Y') }}</div>
        </div>
    @endforeach
</div>
@endif

<div class="detail-section">
    <div class="detail-section-title">⚙️ Automatizaciones</div>
    <div class="automation-row">
        <span class="automation-label">Recordatorio 1 día antes</span>
        <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
    </div>
    <div class="automation-row">
        <span class="automation-label">Día de vencimiento</span>
        <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
    </div>
    <div class="automation-row">
        <span class="automation-label">Confirmación de pago</span>
        <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
    </div>
</div>
