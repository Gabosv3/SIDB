<x-filament-panels::page>
<style>
    .as-input, .as-select {
        border: 1px solid #d1d5db; border-radius: 0.5rem;
        padding: 0.5rem 0.75rem; font-size: 0.875rem;
        color: #111827; background: #fff; outline: none;
    }
    .as-input:focus, .as-select:focus { border-color: #7c3aed; box-shadow: 0 0 0 2px rgba(124,58,237,.2); }

    .as-filters { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.75rem; margin-bottom: 1.5rem; }
    .as-nav-btn { display: inline-flex; align-items: center; justify-content: center; width: 2.1rem; height: 2.1rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; cursor: pointer; color: #374151; }
    .as-nav-btn:hover { background: #f9fafb; }
    .as-periodo-label { font-weight: 700; font-size: 0.9rem; color: #111827; }

    .as-stat-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap: 1rem; margin-bottom: 1.75rem; }
    .as-stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
    .as-stat-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
    .as-stat-num { font-size: 1.875rem; font-weight: 700; margin-top: .25rem; color: #16a34a; }

    .as-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; margin-bottom: 1.25rem; overflow: hidden; }
    .as-card-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #f9fafb; }
    .as-empleado-name { font-size: 1rem; font-weight: 700; color: #111827; }
    .as-empleado-sub { font-size: 0.8rem; color: #6b7280; margin-top: .1rem; }

    .as-thead th { padding: 0.6rem 0.9rem; text-align: left; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .as-thead th:first-child { padding-left: 1.25rem; }
    .as-tr { border-bottom: 1px solid #f3f4f6; transition: background .1s; }
    .as-tr:hover { background: #f9fafb; }
    .as-td { padding: 0.6rem 0.9rem; font-size: 0.82rem; color: #374151; }
    .as-td:first-child { padding-left: 1.25rem; }
    .as-empty { text-align: center; padding: 2.5rem; color: #6b7280; font-size: 0.85rem; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; }
    .as-badge { display: inline-flex; align-items: center; padding: .18rem .55rem; border-radius: 9999px; font-size: .68rem; font-weight: 700; }
    .as-badge-tarde   { background:#fee2e2; color:#dc2626; }
    .as-badge-atiempo { background:#dcfce7; color:#16a34a; }

    /* ── Dark mode ── */
    .dark .as-input, .dark .as-select { background: #2a2a35; border-color: #3f3f50; color: #f3f4f6; }
    .dark .as-input:focus, .dark .as-select:focus { border-color: #a78bfa; box-shadow: 0 0 0 2px rgba(167,139,250,.25); }
    .dark .as-nav-btn { background: #2a2a35; border-color: #3f3f50; color: #d1d5db; }
    .dark .as-nav-btn:hover { background: #33333f; }
    .dark .as-periodo-label { color: #f1f5f9; }
    .dark .as-stat-card { background: #1e1e24; border-color: #2e2e3a; box-shadow: none; }
    .dark .as-stat-label { color: #64748b; }
    .dark .as-stat-num { color: #ffffff; }
    .dark .as-card { background: #1e1e24; border-color: #2e2e3a; }
    .dark .as-card-header { background: #252530; border-bottom-color: #2e2e3a; }
    .dark .as-empleado-name { color: #f1f5f9; }
    .dark .as-empleado-sub { color: #94a3b8; }
    .dark .as-thead th { background: #252530; border-bottom-color: #2e2e3a; color: #9ca3af; }
    .dark .as-tr { border-bottom-color: #2a2a35; }
    .dark .as-tr:hover { background: #252530; }
    .dark .as-td { color: #d1d5db; }
    .dark .as-empty { background: #1f2937; border-color: #374151; color: #9ca3af; }
    .dark .as-badge-tarde   { background:rgba(225,29,72,.18); color:#fb7185; }
    .dark .as-badge-atiempo { background:rgba(34,197,94,.18); color:#86efac; }
</style>

@php
    $resumen = $this->getResumen();
    $totales = $this->getTotales($resumen);
    $porEmpleado = $this->getResumenPorEmpleado($resumen);
    $empleados = $this->getEmpleados();
@endphp

{{-- ── Filtros ─────────────────────────────────────────────────────────── --}}
<div class="as-filters">
    <div>
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Periodo</label>
        <select wire:model.live="periodoTipo" class="as-select">
            <option value="semana">Semana</option>
            <option value="quincena">Quincena</option>
            <option value="mes">Mes</option>
        </select>
    </div>

    <div style="display:flex;align-items:center;gap:.5rem">
        <button type="button" class="as-nav-btn" wire:click="irPeriodoAnterior">‹</button>
        <span class="as-periodo-label">{{ $this->getEtiquetaPeriodo() }}</span>
        <button type="button" class="as-nav-btn" wire:click="irPeriodoSiguiente">›</button>
    </div>

    <div>
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Fecha de referencia</label>
        <input type="date" wire:model.live="fechaReferencia" class="as-input">
    </div>

    <div x-data="{ open: false }" style="position:relative">
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Empleados</label>
        <button
            type="button"
            @click="open = !open"
            class="as-select"
            style="min-width:200px;text-align:left;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:.5rem"
        >
            <span>
                @if(empty($empleadoIds))
                    Todos los empleados
                @elseif(count($empleadoIds) === 1)
                    {{ $empleados->firstWhere('id', $empleadoIds[0])?->user?->name }}
                @else
                    {{ count($empleadoIds) }} empleados seleccionados
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
                <input type="checkbox" {{ empty($empleadoIds) ? 'checked' : '' }} wire:click="$set('empleadoIds', [])">
                Todos los empleados
            </label>
            @foreach($empleados as $eOpt)
                <label style="display:flex;align-items:center;gap:.5rem;padding:.4rem .5rem;font-size:.8rem;cursor:pointer;color:#111827">
                    <input type="checkbox" value="{{ $eOpt->id }}" wire:model.live="empleadoIds">
                    {{ $eOpt->user?->name ?? $eOpt->codigo_empleado }}
                </label>
            @endforeach
        </div>
    </div>
</div>

{{-- ── Totales del periodo ─────────────────────────────────────────────── --}}
<div class="as-stat-grid">
    <div class="as-stat-card">
        <p class="as-stat-label">Días con marcaje</p>
        <p class="as-stat-num">{{ $totales['dias_con_marcaje'] }}</p>
    </div>
    <div class="as-stat-card">
        <p class="as-stat-label">Tardanzas</p>
        <p class="as-stat-num" style="color:{{ $totales['total_tardanzas'] > 0 ? '#dc2626' : '#16a34a' }}">{{ $totales['total_tardanzas'] }}</p>
    </div>
    <div class="as-stat-card">
        <p class="as-stat-label">Horas trabajadas (con salida marcada)</p>
        <p class="as-stat-num" style="color:#7c3aed">{{ number_format($totales['total_horas'], 1) }}</p>
    </div>
</div>

{{-- ── Detalle por empleado ─────────────────────────────────────────────── --}}
@forelse($porEmpleado as $e)
    <div class="as-card" x-data="{ open: false }">
        <div class="as-card-header" style="cursor:pointer" @click="open = !open">
            <div>
                <p class="as-empleado-name">{{ $e['empleado'] }}</p>
                <p class="as-empleado-sub">
                    {{ $e['dias_con_marcaje'] }} día(s) con marcaje
                    @if($e['tardanzas'] > 0)
                        &middot; <span style="color:#dc2626;font-weight:700">{{ $e['tardanzas'] }} tardanza(s)</span>
                    @endif
                    &middot; {{ number_format($e['total_horas'], 1) }} hrs trabajadas
                </p>
            </div>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" :style="open ? 'transform:rotate(180deg)' : ''" style="transition:transform .15s;color:#9ca3af"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div x-show="open" x-cloak style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead class="as-thead">
                    <tr>
                        <th>Fecha</th>
                        <th>Primera entrada</th>
                        <th>Última salida</th>
                        <th>Horas trabajadas</th>
                        <th>Puntualidad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($e['dias'] as $d)
                        <tr class="as-tr">
                            <td class="as-td" style="font-weight:500;color:inherit">{{ $d['fecha']->format('d/m/Y') }}</td>
                            <td class="as-td">{{ $d['primera_entrada']->format('H:i') }}</td>
                            <td class="as-td">{{ $d['ultima_salida']?->format('H:i') ?? '—' }}</td>
                            <td class="as-td">{{ $d['horas_trabajadas'] !== null ? number_format($d['horas_trabajadas'], 1).' hrs' : '—' }}</td>
                            <td class="as-td">
                                @if($d['llego_tarde'])
                                    <span class="as-badge as-badge-tarde">Tarde</span>
                                @else
                                    <span class="as-badge as-badge-atiempo">A tiempo</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="as-empty">
        No hay marcajes en este periodo.
        <div style="margin-top:.5rem;font-size:.78rem">Recordá vincular a cada empleado con su "Código de asistencia" del equipo Hikvision, desde su ficha de perfil.</div>
    </div>
@endforelse
</x-filament-panels::page>
