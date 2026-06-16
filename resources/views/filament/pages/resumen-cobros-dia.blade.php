<x-filament-panels::page>
<style>
    .rc-input {
        border: 1px solid #d1d5db; border-radius: 0.5rem;
        padding: 0.5rem 0.75rem; font-size: 0.875rem;
        color: #111827; background: #fff;
        outline: none; transition: border-color .15s;
    }
    .rc-input:focus { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,.2); }

    .rc-card {
        background: #fff; border: 1px solid #e5e7eb;
        border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }
    .rc-stat-label {
        font-size: 0.7rem; font-weight: 600; text-transform: uppercase;
        letter-spacing: .05em; color: #6b7280;
    }
    .rc-thead { background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .rc-thead th {
        padding: 0.6rem 0.75rem; text-align: left;
        font-size: 0.7rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: .04em; color: #6b7280;
    }
    .rc-thead th:first-child { padding-left: 1.25rem; }
    .rc-thead th:last-child  { padding-right: 1.25rem; text-align: right; }
    .rc-tr { border-bottom: 1px solid #f3f4f6; transition: background .1s; }
    .rc-tr:hover { background: #f9fafb; }
    .rc-td { padding: 0.6rem 0.75rem; font-size: 0.825rem; color: #374151; }
    .rc-td:first-child { padding-left: 1.25rem; color: #9ca3af; }
    .rc-td:last-child  { padding-right: 1.25rem; text-align: right; font-weight: 700; color: #111827; }
    .rc-cobrador-header {
        display: flex; flex-wrap: wrap; align-items: center;
        justify-content: space-between; gap: 0.75rem;
        padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
    }
    .rc-badge {
        display: inline-flex; align-items: center; gap: 0.4rem;
        border: 1px solid #e5e7eb; border-radius: 0.5rem;
        padding: 0.35rem 0.75rem; background: #f9fafb; font-size: 0.8rem;
    }
    .rc-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .rc-empty {
        text-align: center; padding: 3rem;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem;
    }

    /* ── Dark mode ── */
    .dark .rc-input         { background: #1f2937; border-color: #4b5563; color: #f3f4f6; }
    .dark .rc-input:focus   { border-color: #818cf8; box-shadow: 0 0 0 2px rgba(129,140,248,.25); }
    .dark .rc-card          { background: #1f2937; border-color: #374151; box-shadow: none; }
    .dark .rc-stat-label    { color: #9ca3af; }
    .dark .rc-thead         { background: rgba(17,24,39,.4); border-bottom-color: #374151; }
    .dark .rc-thead th      { color: #9ca3af; }
    .dark .rc-tr            { border-bottom-color: rgba(55,65,81,.5); }
    .dark .rc-tr:hover      { background: rgba(55,65,81,.3); }
    .dark .rc-td            { color: #d1d5db; }
    .dark .rc-td:first-child{ color: #6b7280; }
    .dark .rc-td:last-child { color: #f3f4f6; }
    .dark .rc-cobrador-header { background: rgba(17,24,39,.4); border-bottom-color: #374151; }
    .dark .rc-badge         { background: rgba(55,65,81,.5); border-color: #4b5563; }
    .dark .rc-empty         { background: #1f2937; border-color: #374151; color: #9ca3af; }
</style>

@php
    $resumen    = $this->getResumen();
    $totales    = $this->getTotalesGenerales($resumen);
    $cobradores = $this->getCobradores();

    $metodoLabels = [
        'efectivo'      => 'Efectivo',
        'transferencia' => 'Transferencia',
        'cheque'        => 'Cheque',
        'deposito'      => 'Depósito',
    ];
    $metodoDot = [
        'efectivo'      => '#16a34a',
        'transferencia' => '#2563eb',
        'cheque'        => '#9333ea',
        'deposito'      => '#d97706',
    ];
    $metodoColor = [
        'efectivo'      => '#16a34a',
        'transferencia' => '#2563eb',
        'cheque'        => '#9333ea',
        'deposito'      => '#d97706',
    ];
@endphp

{{-- Filtros --}}
<div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:1rem;margin-bottom:1.5rem">
    <div>
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Fecha</label>
        <input type="date" wire:model.live="fecha" class="rc-input" />
    </div>
    <div>
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Cobrador</label>
        <select wire:model.live="cobrador_id" class="rc-input" style="min-width:200px">
            <option value="">Todos los cobradores</option>
            @foreach($cobradores as $c)
                <option value="{{ $c->id }}">{{ $c->nombre }} {{ $c->apellido }}</option>
            @endforeach
        </select>
    </div>
</div>

{{-- Tarjetas totales --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.75rem">
    <div class="rc-card" style="padding:1.25rem">
        <p class="rc-stat-label">Total cobrado</p>
        <p style="font-size:1.875rem;font-weight:700;color:#16a34a;margin-top:0.25rem">${{ number_format($totales['total_cobrado'], 2) }}</p>
    </div>
    <div class="rc-card" style="padding:1.25rem">
        <p class="rc-stat-label">Pagos registrados</p>
        <p style="font-size:1.875rem;font-weight:700;color:#2563eb;margin-top:0.25rem">{{ $totales['total_pagos'] }}</p>
    </div>
    <div class="rc-card" style="padding:1.25rem">
        <p class="rc-stat-label">Clientes visitados</p>
        <p style="font-size:1.875rem;font-weight:700;color:#7c3aed;margin-top:0.25rem">{{ $totales['clientes_visitados'] }}</p>
    </div>
</div>

{{-- Cards por cobrador --}}
@forelse($resumen as $r)
    @php $c = $r['cobrador']; @endphp
    <div class="rc-card" style="margin-bottom:1.25rem;overflow:hidden">

        {{-- Header --}}
        <div class="rc-cobrador-header">
            <div>
                <p style="font-size:1rem;font-weight:700;color:#111827" class="dark-text-primary">
                    {{ $c->nombre }} {{ $c->apellido }}
                </p>
                <p style="font-size:0.8rem;color:#6b7280;margin-top:0.1rem">
                    {{ $r['total_pagos'] }} pago(s) &middot; {{ $r['clientes_visitados'] }} cliente(s) visitado(s)
                </p>
            </div>
            <div style="text-align:right">
                <p class="rc-stat-label">Total cobrado</p>
                <p style="font-size:1.5rem;font-weight:700;color:#16a34a;margin-top:0.1rem">
                    ${{ number_format($r['total_cobrado'], 2) }}
                </p>
            </div>
        </div>

        {{-- Métodos de pago --}}
        @if($r['por_metodo']->isNotEmpty())
            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;padding:0.875rem 1.25rem;border-bottom:1px solid #f3f4f6">
                @foreach($r['por_metodo'] as $metodo => $datos)
                    <div class="rc-badge">
                        <span class="rc-dot" style="background:{{ $metodoDot[$metodo] ?? '#6b7280' }}"></span>
                        <span style="font-weight:500;color:#374151" class="dark-text">{{ $metodoLabels[$metodo] ?? $metodo }}</span>
                        <span style="color:#9ca3af">({{ $datos->cantidad }})</span>
                        <span style="font-weight:700;color:{{ $metodoColor[$metodo] ?? '#6b7280' }}">${{ number_format($datos->monto, 2) }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Tabla --}}
        @if($r['detalle']->isNotEmpty())
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse">
                    <thead class="rc-thead">
                        <tr>
                            <th>Hora</th>
                            <th>Cliente</th>
                            <th>Venta</th>
                            <th>Método</th>
                            <th>Referencia</th>
                            <th>Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($r['detalle'] as $pago)
                            <tr class="rc-tr">
                                <td class="rc-td" style="white-space:nowrap;font-variant-numeric:tabular-nums">
                                    {{ $pago->created_at->format('H:i') }}
                                </td>
                                <td class="rc-td" style="font-weight:500;color:#111827">
                                    {{ $pago->cliente?->nombre_completo ?? '—' }}
                                </td>
                                <td class="rc-td" style="color:#6b7280">
                                    {{ $pago->venta?->numero_venta ?? '—' }}
                                </td>
                                <td class="rc-td">
                                    <span style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.75rem;font-weight:500;color:{{ $metodoColor[$pago->metodo_pago] ?? '#6b7280' }}">
                                        <span class="rc-dot" style="width:6px;height:6px;background:{{ $metodoDot[$pago->metodo_pago] ?? '#9ca3af' }}"></span>
                                        {{ $metodoLabels[$pago->metodo_pago] ?? $pago->metodo_pago }}
                                    </span>
                                </td>
                                <td class="rc-td" style="color:#9ca3af;font-size:0.75rem">
                                    {{ $pago->referencia ?? '—' }}
                                </td>
                                <td class="rc-td">${{ number_format($pago->monto, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@empty
    <div class="rc-empty">
        <p style="font-size:1rem;font-weight:500;color:#6b7280">Sin cobros registrados para esta fecha</p>
        <p style="font-size:0.875rem;color:#9ca3af;margin-top:0.25rem">Selecciona otra fecha o verifica que los cobradores hayan registrado pagos</p>
    </div>
@endforelse

</x-filament-panels::page>
