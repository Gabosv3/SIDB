<x-filament-panels::page>
<style>
    .rr-input {
        border: 1px solid #d1d5db; border-radius: 0.5rem;
        padding: 0.5rem 0.75rem; font-size: 0.875rem;
        color: #111827; background: #fff;
        outline: none; transition: border-color .15s;
    }
    .rr-input:focus { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,.2); }

    .rr-card {
        background: #fff; border: 1px solid #e5e7eb;
        border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }
    .rr-stat-label {
        font-size: 0.7rem; font-weight: 600; text-transform: uppercase;
        letter-spacing: .05em; color: #6b7280;
    }
    .rr-stat-value { font-size: 1.875rem; font-weight: 700; color: #16a34a; margin-top: 0.25rem; }

    .rr-thead { background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .rr-thead th {
        padding: 0.6rem 0.75rem; text-align: left;
        font-size: 0.7rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: .04em; color: #6b7280;
    }
    .rr-thead th:first-child { padding-left: 1.25rem; }
    .rr-tr { border-bottom: 1px solid #f3f4f6; transition: background .1s; }
    .rr-tr:hover { background: #f9fafb; }
    .rr-td { padding: 0.6rem 0.75rem; font-size: 0.825rem; color: #374151; }
    .rr-td:first-child { padding-left: 1.25rem; }

    .rr-empty {
        text-align: center; padding: 3rem;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem;
    }

    .rr-badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.18rem 0.55rem; border-radius: 9999px;
        font-size: 0.68rem; font-weight: 700; letter-spacing: .03em;
    }
    .rr-badge-pendiente     { background:#fef9c3; color:#854d0e; }
    .rr-badge-en_proceso    { background:#e0f2fe; color:#0369a1; }
    .rr-badge-recuperado    { background:#dcfce7; color:#166534; }
    .rr-badge-no_recuperado { background:#ffe4e6; color:#9f1239; }

    /* ── Dark mode ── */
    .dark .rr-input         { background: #2a2a35; border-color: #3f3f50; color: #f3f4f6; }
    .dark .rr-input:focus   { border-color: #818cf8; box-shadow: 0 0 0 2px rgba(129,140,248,.25); }
    .dark .rr-card          { background: #1e1e24; border-color: #2e2e3a; box-shadow: none; }
    .dark .rr-stat-label    { color: #64748b; }
    .dark .rr-stat-value    { color: #ffffff; }
    .dark .rr-thead         { background: #252530; border-bottom-color: #2e2e3a; }
    .dark .rr-thead th      { color: #9ca3af; }
    .dark .rr-tr            { border-bottom-color: #2a2a35; }
    .dark .rr-tr:hover      { background: #252530; }
    .dark .rr-td            { color: #d1d5db; }
    .dark .rr-empty         { background: #1f2937; border-color: #374151; color: #9ca3af; }
    .dark .rr-badge-pendiente     { background:rgba(202,138,4,.18);  color:#fde047; }
    .dark .rr-badge-en_proceso    { background:rgba(2,132,199,.18);  color:#38bdf8; }
    .dark .rr-badge-recuperado    { background:rgba(34,197,94,.18);  color:#86efac; }
    .dark .rr-badge-no_recuperado { background:rgba(225,29,72,.18);  color:#fb7185; }
</style>

@php
    $resumen = $this->getResumen();
    $totales = $this->getTotales($resumen);
    $vendedores = $this->getVendedores();

    $estadoLabels = [
        'pendiente' => 'Pendiente',
        'en_proceso' => 'En proceso',
        'recuperado' => 'Recuperado',
        'no_recuperado' => 'No recuperado',
    ];
@endphp

{{-- Filtros --}}
<div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:1rem;margin-bottom:1.5rem">
    <div>
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Fecha</label>
        <input type="date" wire:model.live="fecha" class="rr-input" />
    </div>
    <div x-data="{ open: false }" style="position:relative">
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Vendedores</label>
        <button
            type="button"
            @click="open = !open"
            class="rr-input"
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
</div>

{{-- Tarjetas totales --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.75rem">
    <div class="rr-card" style="padding:1.25rem">
        <p class="rr-stat-label">Reintegros</p>
        <p class="rr-stat-value">{{ $totales['total'] }}</p>
    </div>
    <div class="rr-card" style="padding:1.25rem">
        <p class="rr-stat-label">Sin asignar</p>
        <p class="rr-stat-value" style="color:{{ $totales['sin_asignar'] > 0 ? '#dc2626' : '#16a34a' }}">{{ $totales['sin_asignar'] }}</p>
    </div>
    <div class="rr-card" style="padding:1.25rem">
        <p class="rr-stat-label">Recuperados</p>
        <p class="rr-stat-value">{{ $totales['recuperados'] }}</p>
    </div>
    <div class="rr-card" style="padding:1.25rem">
        <p class="rr-stat-label">No recuperados</p>
        <p class="rr-stat-value" style="color:{{ $totales['no_recuperados'] > 0 ? '#dc2626' : '#16a34a' }}">{{ $totales['no_recuperados'] }}</p>
    </div>
    <div class="rr-card" style="padding:1.25rem">
        <p class="rr-stat-label">Monto adeudado</p>
        <p class="rr-stat-value" style="color:#dc2626">${{ number_format($totales['monto_adeudado'], 2) }}</p>
    </div>
</div>

{{-- Tabla --}}
@if($resumen->isNotEmpty())
    <div class="rr-card" style="overflow:hidden">
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead class="rr-thead">
                    <tr>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Venta</th>
                        <th>Ruta de origen</th>
                        <th>Enviado por</th>
                        <th>Vendedor asignado</th>
                        <th>Estado</th>
                        <th style="text-align:right">Monto vencido</th>
                        <th>Cuotas</th>
                        <th>Recuperado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumen as $r)
                        <tr class="rr-tr">
                            <td class="rr-td" style="font-weight:500;color:inherit">
                                {{ $r->cliente?->nombre_completo ?? '—' }}
                                @if($r->cliente?->codigo_anterior)
                                    <span style="color:#9ca3af;font-size:0.75rem"> · {{ $r->cliente->codigo_anterior }}</span>
                                @endif
                            </td>
                            <td class="rr-td" style="color:#6b7280">{{ $r->cliente?->telefono_normal ?? '—' }}</td>
                            <td class="rr-td" style="color:#6b7280">{{ $r->venta?->numero_venta ?? '—' }}</td>
                            <td class="rr-td" style="color:#6b7280">{{ $r->rutaCobroOriginal?->nombre ?? '—' }}</td>
                            <td class="rr-td" style="color:#6b7280">{{ $r->asignadoPor?->name ?? '—' }}</td>
                            <td class="rr-td" style="{{ $r->vendedor ? 'color:#6b7280' : 'color:#dc2626;font-weight:600' }}">
                                {{ $r->vendedor ? "{$r->vendedor->nombre} {$r->vendedor->apellido}" : 'Sin asignar' }}
                            </td>
                            <td class="rr-td">
                                <span class="rr-badge rr-badge-{{ $r->estado }}">{{ $estadoLabels[$r->estado] ?? $r->estado }}</span>
                            </td>
                            <td class="rr-td" style="text-align:right;font-weight:700;color:inherit">${{ number_format((float) $r->monto_adeudado, 2) }}</td>
                            <td class="rr-td">{{ $r->cuotas_vencidas }}</td>
                            <td class="rr-td" style="color:#6b7280">{{ $r->fecha_recuperacion?->format('d/m/Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="rr-empty">
        <p style="font-size:1rem;font-weight:500;color:#6b7280">Sin reintegros asignados en esta fecha</p>
        <p style="font-size:0.875rem;color:#9ca3af;margin-top:0.25rem">Selecciona otra fecha o revisa el filtro de vendedores</p>
    </div>
@endif

</x-filament-panels::page>
