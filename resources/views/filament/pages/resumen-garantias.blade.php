<x-filament-panels::page>
<style>
    .rg-input {
        border: 1px solid #d1d5db; border-radius: 0.5rem;
        padding: 0.5rem 0.75rem; font-size: 0.875rem;
        color: #111827; background: #fff;
        outline: none; transition: border-color .15s;
    }
    .rg-input:focus { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,.2); }

    .rg-card {
        background: #fff; border: 1px solid #e5e7eb;
        border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }
    .rg-stat-label {
        font-size: 0.7rem; font-weight: 600; text-transform: uppercase;
        letter-spacing: .05em; color: #6b7280;
    }
    .rg-stat-value { font-size: 1.875rem; font-weight: 700; color: #16a34a; margin-top: 0.25rem; }

    .rg-thead { background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .rg-thead th {
        padding: 0.6rem 0.75rem; text-align: left;
        font-size: 0.7rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: .04em; color: #6b7280;
    }
    .rg-thead th:first-child { padding-left: 1.25rem; }
    .rg-tr { border-bottom: 1px solid #f3f4f6; transition: background .1s; }
    .rg-tr:hover { background: #f9fafb; }
    .rg-td { padding: 0.6rem 0.75rem; font-size: 0.825rem; color: #374151; }
    .rg-td:first-child { padding-left: 1.25rem; }

    .rg-empty {
        text-align: center; padding: 3rem;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem;
    }

    .rg-badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.18rem 0.55rem; border-radius: 9999px;
        font-size: 0.68rem; font-weight: 700; letter-spacing: .03em;
    }
    .rg-badge-pendiente  { background:#fef9c3; color:#854d0e; }
    .rg-badge-en_proceso { background:#e0f2fe; color:#0369a1; }
    .rg-badge-resuelta   { background:#dcfce7; color:#166534; }
    .rg-badge-rechazada  { background:#ffe4e6; color:#9f1239; }

    /* ── Dark mode ── */
    .dark .rg-input         { background: #2a2a35; border-color: #3f3f50; color: #f3f4f6; }
    .dark .rg-input:focus   { border-color: #818cf8; box-shadow: 0 0 0 2px rgba(129,140,248,.25); }
    .dark .rg-card          { background: #1e1e24; border-color: #2e2e3a; box-shadow: none; }
    .dark .rg-stat-label    { color: #64748b; }
    .dark .rg-stat-value    { color: #ffffff; }
    .dark .rg-thead         { background: #252530; border-bottom-color: #2e2e3a; }
    .dark .rg-thead th      { color: #9ca3af; }
    .dark .rg-tr            { border-bottom-color: #2a2a35; }
    .dark .rg-tr:hover      { background: #252530; }
    .dark .rg-td            { color: #d1d5db; }
    .dark .rg-empty         { background: #1f2937; border-color: #374151; color: #9ca3af; }
    .dark .rg-badge-pendiente  { background:rgba(202,138,4,.18);  color:#fde047; }
    .dark .rg-badge-en_proceso { background:rgba(2,132,199,.18);  color:#38bdf8; }
    .dark .rg-badge-resuelta   { background:rgba(34,197,94,.18);  color:#86efac; }
    .dark .rg-badge-rechazada  { background:rgba(225,29,72,.18);  color:#fb7185; }
</style>

@php
    $resumen = $this->getResumen();
    $totales = $this->getTotales($resumen);
    $asignados = $this->getAsignados();

    $estadoLabels = [
        'pendiente' => 'Pendiente',
        'en_proceso' => 'En proceso',
        'resuelta' => 'Resuelta',
        'rechazada' => 'Rechazada',
    ];
@endphp

{{-- Filtros --}}
<div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:1rem;margin-bottom:1.5rem">
    <div>
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Fecha</label>
        <input type="date" wire:model.live="fecha" class="rg-input" />
    </div>
    @if($asignados->isNotEmpty())
        <div x-data="{ open: false }" style="position:relative">
            <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Asignado a</label>
            <button
                type="button"
                @click="open = !open"
                class="rg-input"
                style="min-width:200px;text-align:left;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:.5rem"
            >
                <span>
                    @if(empty($asignadosSeleccionados))
                        Todos
                    @elseif(count($asignadosSeleccionados) === 1)
                        {{ $asignados->firstWhere('id', $asignadosSeleccionados[0])?->name }}
                    @else
                        {{ count($asignadosSeleccionados) }} seleccionados
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
                    <input type="checkbox" {{ empty($asignadosSeleccionados) ? 'checked' : '' }} wire:click="$set('asignadosSeleccionados', [])">
                    Todos
                </label>
                @foreach($asignados as $aOpt)
                    <label style="display:flex;align-items:center;gap:.5rem;padding:.4rem .5rem;font-size:.8rem;cursor:pointer;color:#111827">
                        <input type="checkbox" value="{{ $aOpt->id }}" wire:model.live="asignadosSeleccionados">
                        {{ $aOpt->name }}
                    </label>
                @endforeach
            </div>
        </div>
    @endif
</div>

{{-- Tarjetas totales --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.75rem">
    <div class="rg-card" style="padding:1.25rem">
        <p class="rg-stat-label">Garantías</p>
        <p class="rg-stat-value">{{ $totales['total'] }}</p>
    </div>
    <div class="rg-card" style="padding:1.25rem">
        <p class="rg-stat-label">Pendientes</p>
        <p class="rg-stat-value" style="color:{{ $totales['pendientes'] > 0 ? '#dc2626' : '#16a34a' }}">{{ $totales['pendientes'] }}</p>
    </div>
    <div class="rg-card" style="padding:1.25rem">
        <p class="rg-stat-label">En proceso</p>
        <p class="rg-stat-value">{{ $totales['en_proceso'] }}</p>
    </div>
    <div class="rg-card" style="padding:1.25rem">
        <p class="rg-stat-label">Resueltas</p>
        <p class="rg-stat-value">{{ $totales['resueltas'] }}</p>
    </div>
    <div class="rg-card" style="padding:1.25rem">
        <p class="rg-stat-label">Rechazadas</p>
        <p class="rg-stat-value" style="color:{{ $totales['rechazadas'] > 0 ? '#dc2626' : '#16a34a' }}">{{ $totales['rechazadas'] }}</p>
    </div>
</div>

{{-- Tabla --}}
@if($resumen->isNotEmpty())
    <div class="rg-card" style="overflow:hidden">
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead class="rg-thead">
                    <tr>
                        <th>Cliente</th>
                        <th>Venta</th>
                        <th>Motivo</th>
                        <th>Reportado por</th>
                        <th>Recoge (cobrador)</th>
                        <th>Asignado a</th>
                        <th>Descripción</th>
                        <th>Fotos</th>
                        <th>Estado</th>
                        <th>Resuelto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumen as $g)
                        <tr class="rg-tr">
                            <td class="rg-td" style="font-weight:500;color:inherit">
                                {{ $g->cliente?->nombre_completo ?? '—' }}
                                @if($g->cliente?->codigo_anterior)
                                    <span style="color:#9ca3af;font-size:0.75rem"> · {{ $g->cliente->codigo_anterior }}</span>
                                @endif
                            </td>
                            <td class="rg-td" style="color:#6b7280">{{ $g->venta?->numero_venta ?? '—' }}</td>
                            <td class="rg-td" style="color:#6b7280">{{ $g->motivo ?? '—' }}</td>
                            <td class="rg-td" style="color:#6b7280">{{ $g->reportadoPor?->name ?? '—' }}</td>
                            <td class="rg-td" style="{{ $g->cobrador ? 'color:#6b7280' : 'color:#dc2626;font-weight:600' }}">
                                {{ $g->cobrador?->nombre_completo ?? 'Sin asignar' }}
                            </td>
                            <td class="rg-td" style="{{ $g->asignadoA ? 'color:#6b7280' : 'color:#dc2626;font-weight:600' }}">
                                {{ $g->asignadoA?->name ?? 'Sin asignar' }}
                            </td>
                            <td class="rg-td" style="color:#6b7280;font-size:0.78rem;max-width:260px">{{ \Illuminate\Support\Str::limit($g->descripcion, 60) }}</td>
                            <td class="rg-td">
                                @if($g->fotos)
                                    <div style="display:flex;gap:.25rem">
                                        @foreach(array_slice($g->fotos, 0, 3) as $foto)
                                            <a href="{{ asset('storage/'.$foto) }}" target="_blank">
                                                <img src="{{ asset('storage/'.$foto) }}" style="width:28px;height:28px;border-radius:6px;object-fit:cover;border:1px solid #e5e7eb" />
                                            </a>
                                        @endforeach
                                        @if(count($g->fotos) > 3)
                                            <span style="font-size:0.7rem;color:#9ca3af;align-self:center">+{{ count($g->fotos) - 3 }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span style="color:#9ca3af">—</span>
                                @endif
                            </td>
                            <td class="rg-td">
                                <span class="rg-badge rg-badge-{{ $g->estado }}">{{ $estadoLabels[$g->estado] ?? $g->estado }}</span>
                            </td>
                            <td class="rg-td" style="color:#6b7280">{{ $g->fecha_resolucion?->format('d/m/Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="rg-empty">
        <p style="font-size:1rem;font-weight:500;color:#6b7280">Sin garantías reportadas en esta fecha</p>
        <p style="font-size:0.875rem;color:#9ca3af;margin-top:0.25rem">Selecciona otra fecha o revisa el filtro</p>
    </div>
@endif

</x-filament-panels::page>
