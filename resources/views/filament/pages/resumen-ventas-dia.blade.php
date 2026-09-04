<x-filament-panels::page>
<style>
    .rv-input {
        border: 1px solid #d1d5db; border-radius: 0.5rem;
        padding: 0.5rem 0.75rem; font-size: 0.875rem;
        color: #111827; background: #fff;
        outline: none; transition: border-color .15s;
    }
    .rv-input:focus { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,.2); }

    .rv-card {
        background: #fff; border: 1px solid #e5e7eb;
        border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }
    .rv-stat-label {
        font-size: 0.7rem; font-weight: 600; text-transform: uppercase;
        letter-spacing: .05em; color: #6b7280;
    }
    .rv-stat-value { font-size: 1.875rem; font-weight: 700; color: #16a34a; margin-top: 0.25rem; }

    .rv-thead { background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .rv-thead th {
        padding: 0.6rem 0.75rem; text-align: left;
        font-size: 0.7rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: .04em; color: #6b7280;
    }
    .rv-thead th:first-child { padding-left: 1.25rem; }
    .rv-thead th:last-child  { padding-right: 1.25rem; text-align: right; }
    .rv-tr { border-bottom: 1px solid #f3f4f6; transition: background .1s; }
    .rv-tr:hover { background: #f9fafb; }
    .rv-td { padding: 0.6rem 0.75rem; font-size: 0.825rem; color: #374151; }
    .rv-td:first-child { padding-left: 1.25rem; }
    .rv-td:last-child  { padding-right: 1.25rem; text-align: right; font-weight: 700; color: #111827; }

    .rv-empty {
        text-align: center; padding: 3rem;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem;
    }

    .rv-badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.18rem 0.55rem; border-radius: 9999px;
        font-size: 0.68rem; font-weight: 700; letter-spacing: .03em;
    }
    .rv-badge-nuevo        { background:#dcfce7; color:#166534; }
    .rv-badge-recurrente   { background:#f1f5f9; color:#475569; }
    .rv-badge-contado      { background:#e0f2fe; color:#0369a1; }
    .rv-badge-credito      { background:#fef9c3; color:#854d0e; }
    .rv-badge-mixta        { background:#ede9fe; color:#5b21b6; }
    .rv-badge-completada   { background:#dcfce7; color:#166534; }
    .rv-badge-pendiente    { background:#f1f5f9; color:#475569; }
    .rv-badge-cancelada    { background:#ffe4e6; color:#9f1239; }
    .rv-badge-devuelta     { background:#ffe4e6; color:#9f1239; }

    /* ── Dark mode ── */
    .dark .rv-input         { background: #2a2a35; border-color: #3f3f50; color: #f3f4f6; }
    .dark .rv-input:focus   { border-color: #818cf8; box-shadow: 0 0 0 2px rgba(129,140,248,.25); }
    .dark .rv-card          { background: #1e1e24; border-color: #2e2e3a; box-shadow: none; }
    .dark .rv-stat-label    { color: #64748b; }
    .dark .rv-stat-value    { color: #ffffff; }
    .dark .rv-thead         { background: #252530; border-bottom-color: #2e2e3a; }
    .dark .rv-thead th      { color: #9ca3af; }
    .dark .rv-tr            { border-bottom-color: #2a2a35; }
    .dark .rv-tr:hover      { background: #252530; }
    .dark .rv-td            { color: #d1d5db; }
    .dark .rv-td:last-child { color: #f3f4f6; }
    .dark .rv-empty         { background: #1f2937; border-color: #374151; color: #9ca3af; }
    .dark .rv-badge-nuevo      { background:rgba(34,197,94,.18);  color:#86efac; }
    .dark .rv-badge-recurrente { background:rgba(100,116,139,.18);color:#94a3b8; }
    .dark .rv-badge-contado    { background:rgba(2,132,199,.18);  color:#38bdf8; }
    .dark .rv-badge-credito    { background:rgba(202,138,4,.18);  color:#fde047; }
    .dark .rv-badge-mixta      { background:rgba(124,58,237,.18); color:#c4b5fd; }
    .dark .rv-badge-completada { background:rgba(34,197,94,.18);  color:#86efac; }
    .dark .rv-badge-pendiente  { background:rgba(100,116,139,.18);color:#94a3b8; }
    .dark .rv-badge-cancelada  { background:rgba(225,29,72,.18);  color:#fb7185; }
    .dark .rv-badge-devuelta   { background:rgba(225,29,72,.18);  color:#fb7185; }
</style>

@php
    $resumen = $this->getResumen();
    $totales = $this->getTotales($resumen);
    $vendedores = $this->getVendedores();

    $tipoPagoLabels = ['contado' => 'Contado', 'credito' => 'Crédito', 'mixta' => 'Mixta'];
    $estadoLabels = ['pendiente' => 'Pendiente', 'completada' => 'Completada', 'cancelada' => 'Cancelada', 'devuelta' => 'Devuelta'];
@endphp

{{-- Filtros --}}
<div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:1rem;margin-bottom:1.5rem">
    <div>
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Fecha</label>
        <input type="date" wire:model.live="fecha" class="rv-input" />
    </div>
    <div x-data="{ open: false }" style="position:relative">
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Vendedores</label>
        <button
            type="button"
            @click="open = !open"
            class="rv-input"
            style="min-width:200px;text-align:left;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:.5rem"
        >
            <span>
                @if(empty($vendedoresSeleccionados))
                    Todos los vendedores
                @elseif(count($vendedoresSeleccionados) === 1)
                    {{ $vendedores->firstWhere('id', $vendedoresSeleccionados[0])?->nombre }}
                @else
                    {{ count($vendedoresSeleccionados) }} vendedores seleccionados
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
                <input type="checkbox" {{ empty($vendedoresSeleccionados) ? 'checked' : '' }} wire:click="$set('vendedoresSeleccionados', [])">
                Todos los vendedores
            </label>
            @foreach($vendedores as $vOpt)
                <label style="display:flex;align-items:center;gap:.5rem;padding:.4rem .5rem;font-size:.8rem;cursor:pointer;color:#111827">
                    <input type="checkbox" value="{{ $vOpt->id }}" wire:model.live="vendedoresSeleccionados">
                    {{ $vOpt->nombre }} {{ $vOpt->apellido }}
                </label>
            @endforeach
        </div>
    </div>
    <div>
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Buscar cliente</label>
        <input
            type="text"
            wire:model.live.debounce.400ms="buscarCliente"
            placeholder="Nombre, código o teléfono..."
            class="rv-input"
            style="min-width:220px"
        />
    </div>
</div>

{{-- Tarjetas totales --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.75rem">
    <div class="rv-card" style="padding:1.25rem">
        <p class="rv-stat-label">Total vendido</p>
        <p class="rv-stat-value">${{ number_format($totales['total_vendido'], 2) }}</p>
    </div>
    <div class="rv-card" style="padding:1.25rem">
        <p class="rv-stat-label">Ventas</p>
        <p class="rv-stat-value">{{ $totales['total_ventas'] }}</p>
    </div>
    <div class="rv-card" style="padding:1.25rem">
        <p class="rv-stat-label">Clientes nuevos</p>
        <p class="rv-stat-value">{{ $totales['clientes_nuevos'] }}</p>
    </div>
    <div class="rv-card" style="padding:1.25rem">
        <p class="rv-stat-label">Clientes recurrentes</p>
        <p class="rv-stat-value">{{ $totales['clientes_recurrentes'] }}</p>
    </div>
    @if($totales['ventas_canceladas'] > 0)
        <div class="rv-card" style="padding:1.25rem">
            <p class="rv-stat-label">Canceladas/devueltas</p>
            <p class="rv-stat-value" style="color:#dc2626">{{ $totales['ventas_canceladas'] }}</p>
        </div>
    @endif
</div>

{{-- Tabla de ventas --}}
@if($resumen->isNotEmpty())
    <div class="rv-card" style="overflow:hidden">
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead class="rv-thead">
                    <tr>
                        <th>Hora</th>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>¿Nuevo?</th>
                        <th>Vendedor</th>
                        <th>Venta</th>
                        <th>Tipo pago</th>
                        <th>Estado</th>
                        <th style="text-align:right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumen as $r)
                        @php $v = $r->venta; $c = $v->cliente; @endphp
                        <tr class="rv-tr">
                            <td class="rv-td" style="white-space:nowrap;color:#9ca3af;font-variant-numeric:tabular-nums">
                                {{ $v->fecha_venta->format('H:i') }}
                            </td>
                            <td class="rv-td" style="font-weight:500;color:inherit">
                                {{ $c?->nombre_completo ?? 'Sin cliente' }}
                                @if($c?->codigo_anterior)
                                    <span style="color:#9ca3af;font-size:0.75rem"> · {{ $c->codigo_anterior }}</span>
                                @endif
                            </td>
                            <td class="rv-td" style="color:#6b7280">{{ $c?->telefono_normal ?? '—' }}</td>
                            <td class="rv-td">
                                <span class="rv-badge {{ $r->es_cliente_nuevo ? 'rv-badge-nuevo' : 'rv-badge-recurrente' }}">
                                    {{ $r->es_cliente_nuevo ? 'Nuevo' : 'Recurrente' }}
                                </span>
                            </td>
                            <td class="rv-td" style="color:#6b7280">
                                {{ $v->vendedor ? "{$v->vendedor->nombre} {$v->vendedor->apellido}" : '—' }}
                            </td>
                            <td class="rv-td" style="color:#6b7280">{{ $v->numero_venta }}</td>
                            <td class="rv-td">
                                <span class="rv-badge rv-badge-{{ $v->tipo_pago }}">{{ $tipoPagoLabels[$v->tipo_pago] ?? $v->tipo_pago }}</span>
                            </td>
                            <td class="rv-td">
                                <span class="rv-badge rv-badge-{{ $v->estado }}">{{ $estadoLabels[$v->estado] ?? $v->estado }}</span>
                            </td>
                            <td class="rv-td" style="text-align:right;font-weight:700;color:inherit">${{ number_format((float) $v->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="rv-empty">
        @if(trim($this->buscarCliente) !== '')
            <p style="font-size:1rem;font-weight:500;color:#6b7280">Ningún cliente coincide con "{{ $this->buscarCliente }}"</p>
            <p style="font-size:0.875rem;color:#9ca3af;margin-top:0.25rem">Prueba con otro nombre o borra la búsqueda</p>
        @else
            <p style="font-size:1rem;font-weight:500;color:#6b7280">Sin ventas registradas para esta fecha</p>
            <p style="font-size:0.875rem;color:#9ca3af;margin-top:0.25rem">Selecciona otra fecha o revisa el filtro de vendedores</p>
        @endif
    </div>
@endif

</x-filament-panels::page>
