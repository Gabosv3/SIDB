<x-filament-panels::page>
<style>
    .re-input {
        border: 1px solid #d1d5db; border-radius: 0.5rem;
        padding: 0.5rem 0.75rem; font-size: 0.875rem;
        color: #111827; background: #fff;
        outline: none; transition: border-color .15s;
    }
    .re-input:focus { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,.2); }

    .re-card {
        background: #fff; border: 1px solid #e5e7eb;
        border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }
    .re-stat-label {
        font-size: 0.7rem; font-weight: 600; text-transform: uppercase;
        letter-spacing: .05em; color: #6b7280;
    }
    .re-stat-value { font-size: 1.875rem; font-weight: 700; color: #16a34a; margin-top: 0.25rem; }

    .re-thead { background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .re-thead th {
        padding: 0.6rem 0.75rem; text-align: left;
        font-size: 0.7rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: .04em; color: #6b7280;
    }
    .re-thead th:first-child { padding-left: 1.25rem; }
    .re-tr { border-bottom: 1px solid #f3f4f6; transition: background .1s; }
    .re-tr:hover { background: #f9fafb; }
    .re-td { padding: 0.6rem 0.75rem; font-size: 0.825rem; color: #374151; }
    .re-td:first-child { padding-left: 1.25rem; }

    .re-empty {
        text-align: center; padding: 3rem;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem;
    }

    .re-badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.18rem 0.55rem; border-radius: 9999px;
        font-size: 0.68rem; font-weight: 700; letter-spacing: .03em;
    }
    .re-badge-coincide                 { background:#dcfce7; color:#166534; }
    .re-badge-diferencia_investigar    { background:#fef9c3; color:#854d0e; }
    .re-badge-pago_no_registrado       { background:#ffe4e6; color:#9f1239; }
    .re-badge-comprobante_inconsistente{ background:#ffe4e6; color:#9f1239; }

    /* ── Dark mode ── */
    .dark .re-input         { background: #2a2a35; border-color: #3f3f50; color: #f3f4f6; }
    .dark .re-input:focus   { border-color: #818cf8; box-shadow: 0 0 0 2px rgba(129,140,248,.25); }
    .dark .re-card          { background: #1e1e24; border-color: #2e2e3a; box-shadow: none; }
    .dark .re-stat-label    { color: #64748b; }
    .dark .re-stat-value    { color: #ffffff; }
    .dark .re-thead         { background: #252530; border-bottom-color: #2e2e3a; }
    .dark .re-thead th      { color: #9ca3af; }
    .dark .re-tr            { border-bottom-color: #2a2a35; }
    .dark .re-tr:hover      { background: #252530; }
    .dark .re-td            { color: #d1d5db; }
    .dark .re-empty         { background: #1f2937; border-color: #374151; color: #9ca3af; }
    .dark .re-badge-coincide                  { background:rgba(34,197,94,.18);  color:#86efac; }
    .dark .re-badge-diferencia_investigar     { background:rgba(202,138,4,.18);  color:#fde047; }
    .dark .re-badge-pago_no_registrado        { background:rgba(225,29,72,.18);  color:#fb7185; }
    .dark .re-badge-comprobante_inconsistente { background:rgba(225,29,72,.18);  color:#fb7185; }
</style>

@php
    $resumen = $this->getResumen();
    $totales = $this->getTotales($resumen);
    $cobradores = $this->getCobradores();

    $resultadoLabels = [
        'coincide' => 'Todo coincide',
        'diferencia_investigar' => 'Diferencia por investigar',
        'pago_no_registrado' => 'Pago no registrado',
        'comprobante_inconsistente' => 'Comprobante inconsistente',
    ];
@endphp

{{-- Filtros --}}
<div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:1rem;margin-bottom:1.5rem">
    <div>
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Fecha</label>
        <input type="date" wire:model.live="fecha" class="re-input" />
    </div>
    <div x-data="{ open: false }" style="position:relative">
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Cobradores</label>
        <button
            type="button"
            @click="open = !open"
            class="re-input"
            style="min-width:200px;text-align:left;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:.5rem"
        >
            <span>
                @if(empty($cobradoresSeleccionados))
                    Todos los cobradores
                @elseif(count($cobradoresSeleccionados) === 1)
                    {{ $cobradores->firstWhere('id', $cobradoresSeleccionados[0])?->nombre }}
                @else
                    {{ count($cobradoresSeleccionados) }} cobradores seleccionados
                @endif
            </span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div
            x-show="open"
            @click.outside="open = false"
            x-cloak
            style="position:absolute;top:calc(100% + 4px);left:0;z-index:50;min-width:220px;max-height:280px;overflow-y:auto;background:#fff;border:1px solid #e5e7eb;border-radius:.5rem;box-shadow:0 6px 18px rgba(0,0,0,.1);padding:.4rem"
        >
            <label style="display:flex;align-items:center;gap:.5rem;padding:.4rem .5rem;font-size:.8rem;cursor:pointer;border-bottom:1px solid #f3f4f6;font-weight:600;color:#111827">
                <input type="checkbox" {{ empty($cobradoresSeleccionados) ? 'checked' : '' }} wire:click="$set('cobradoresSeleccionados', [])">
                Todos los cobradores
            </label>
            @foreach($cobradores as $cOpt)
                <label style="display:flex;align-items:center;gap:.5rem;padding:.4rem .5rem;font-size:.8rem;cursor:pointer;color:#111827">
                    <input type="checkbox" value="{{ $cOpt->id }}" wire:model.live="cobradoresSeleccionados">
                    {{ $cOpt->nombre }} {{ $cOpt->apellido }}
                </label>
            @endforeach
        </div>
    </div>
</div>

{{-- Tarjetas totales --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.75rem">
    <div class="re-card" style="padding:1.25rem">
        <p class="re-stat-label">Encuestas</p>
        <p class="re-stat-value">{{ $totales['total_encuestas'] }}</p>
    </div>
    <div class="re-card" style="padding:1.25rem">
        <p class="re-stat-label">Coinciden</p>
        <p class="re-stat-value">{{ $totales['coinciden'] }}</p>
    </div>
    <div class="re-card" style="padding:1.25rem">
        <p class="re-stat-label">Con diferencia</p>
        <p class="re-stat-value" style="color:{{ $totales['con_diferencia'] > 0 ? '#dc2626' : '#16a34a' }}">{{ $totales['con_diferencia'] }}</p>
    </div>
    <div class="re-card" style="padding:1.25rem">
        <p class="re-stat-label">Monto en diferencia</p>
        <p class="re-stat-value" style="color:{{ $totales['monto_diferencia'] > 0 ? '#dc2626' : '#16a34a' }}">${{ number_format($totales['monto_diferencia'], 2) }}</p>
    </div>
</div>

{{-- Tabla --}}
@if($resumen->isNotEmpty())
    <div class="re-card" style="overflow:hidden">
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead class="re-thead">
                    <tr>
                        <th>Cliente</th>
                        <th>Supervisor</th>
                        <th>Cobrador de ruta</th>
                        <th>Dice el cliente</th>
                        <th>Registrado BM</th>
                        <th>Diferencia</th>
                        <th>Resultado</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumen as $e)
                        @php
                            $cobradorReal = trim(($e->cobrador?->nombre ?? '').' '.($e->cobrador?->apellido ?? ''));
                            $mismatchCobrador = $e->cobrador_reportado_cliente && $cobradorReal
                                && trim($e->cobrador_reportado_cliente) !== $cobradorReal;
                        @endphp
                        <tr class="re-tr">
                            <td class="re-td" style="font-weight:500;color:inherit">
                                {{ $e->cliente?->nombre_completo ?? '—' }}
                                @if($e->cliente?->codigo_anterior)
                                    <span style="color:#9ca3af;font-size:0.75rem"> · {{ $e->cliente->codigo_anterior }}</span>
                                @endif
                            </td>
                            <td class="re-td" style="color:#6b7280">
                                {{ $e->supervisor ? "{$e->supervisor->nombre} {$e->supervisor->apellido}" : '—' }}
                            </td>
                            <td class="re-td" style="color:#6b7280">
                                {{ $cobradorReal ?: '—' }}
                                @if($mismatchCobrador)
                                    <div style="color:#dc2626;font-size:0.72rem">Cliente dice: {{ $e->cobrador_reportado_cliente }}</div>
                                @endif
                            </td>
                            <td class="re-td">${{ number_format((float) $e->ultimo_pago_monto_cliente, 2) }}</td>
                            <td class="re-td">${{ number_format((float) $e->pago_registrado_bm, 2) }}</td>
                            <td class="re-td" style="color:{{ (float) $e->diferencia !== 0.0 ? '#dc2626' : '#16a34a' }};font-weight:700">
                                ${{ number_format((float) $e->diferencia, 2) }}
                            </td>
                            <td class="re-td">
                                <span class="re-badge re-badge-{{ $e->resultado }}">{{ $resultadoLabels[$e->resultado] ?? $e->resultado }}</span>
                            </td>
                            <td class="re-td" style="color:#6b7280;font-size:0.75rem">{{ $e->observaciones ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="re-empty">
        <p style="font-size:1rem;font-weight:500;color:#6b7280">Sin encuestas registradas para esta fecha</p>
        <p style="font-size:0.875rem;color:#9ca3af;margin-top:0.25rem">Selecciona otra fecha o revisa el filtro de cobradores</p>
    </div>
@endif

</x-filament-panels::page>
