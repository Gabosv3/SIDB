<x-filament-panels::page>
<style>
    .as-input, .as-select {
        border: 1px solid #d1d5db; border-radius: 0.5rem;
        padding: 0.5rem 0.75rem; font-size: 0.875rem;
        color: #111827; background: #fff; outline: none;
    }
    .as-input:focus, .as-select:focus { border-color: #7c3aed; box-shadow: 0 0 0 2px rgba(124,58,237,.2); }

    .as-filters { display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; }
    .as-nav-btn { display: inline-flex; align-items: center; justify-content: center; width: 2.1rem; height: 2.1rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; cursor: pointer; color: #374151; }
    .as-nav-btn:hover { background: #f9fafb; }
    .as-periodo-label { font-weight: 700; font-size: 0.9rem; color: #111827; }

    .as-stat-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1rem; margin-bottom: 1.25rem; }
    @media (max-width: 900px) { .as-stat-grid { grid-template-columns: repeat(1,1fr); } }
    .as-stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem 1.2rem; }
    .as-stat-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
    .as-stat-num { font-size: 1.4rem; font-weight: 800; color: #111827; margin-top: .2rem; }

    .as-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; margin-bottom: 1.25rem; }
    .as-card-header { padding: 0.9rem 1.25rem; border-bottom: 1px solid #e5e7eb; font-size: 0.85rem; font-weight: 700; color: #111827; }

    .as-thead th { padding: 0.6rem 0.9rem; text-align: left; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .as-tr { border-bottom: 1px solid #f3f4f6; }
    .as-tr:hover { background: #f9fafb; }
    .as-td { padding: 0.65rem 0.9rem; font-size: 0.82rem; color: #374151; }
    .as-empty { text-align: center; padding: 2.5rem; color: #6b7280; font-size: 0.85rem; }
    .as-badge { display: inline-flex; align-items: center; padding: .15rem .55rem; border-radius: 9999px; font-size: .7rem; font-weight: 700; }
</style>

@php
    $resumen = $this->getResumen();
    $totales = $this->getTotales($resumen);
@endphp

{{-- ── Filtros ─────────────────────────────────────────────────────────── --}}
<div class="as-filters">
    <select wire:model.live="periodoTipo" class="as-select">
        <option value="semana">Semana</option>
        <option value="quincena">Quincena</option>
        <option value="mes">Mes</option>
    </select>

    <button type="button" class="as-nav-btn" wire:click="irPeriodoAnterior">‹</button>
    <span class="as-periodo-label">{{ $this->getEtiquetaPeriodo() }}</span>
    <button type="button" class="as-nav-btn" wire:click="irPeriodoSiguiente">›</button>

    <input type="date" wire:model.live="fechaReferencia" class="as-input">
</div>

{{-- ── Totales del periodo ─────────────────────────────────────────────── --}}
<div class="as-stat-grid">
    <div class="as-stat-card">
        <div class="as-stat-label">Días con marcaje</div>
        <div class="as-stat-num">{{ $totales['dias_con_marcaje'] }}</div>
    </div>
    <div class="as-stat-card">
        <div class="as-stat-label">Tardanzas</div>
        <div class="as-stat-num" style="color:#dc2626;">{{ $totales['total_tardanzas'] }}</div>
    </div>
    <div class="as-stat-card">
        <div class="as-stat-label">Horas trabajadas (con salida marcada)</div>
        <div class="as-stat-num" style="color:#7c3aed;">{{ number_format($totales['total_horas'], 1) }}</div>
    </div>
</div>

{{-- ── Detalle por empleado y día ───────────────────────────────────────── --}}
<div class="as-card">
    <div class="as-card-header">Marcajes del periodo</div>
    @if(empty($resumen))
        <div class="as-empty">
            No hay marcajes en este periodo.
            <div style="margin-top:.5rem; font-size:.78rem;">Recordá vincular a cada empleado con su "Código de asistencia" del equipo Hikvision, desde su ficha de perfil.</div>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead class="as-thead">
                    <tr>
                        <th>Fecha</th>
                        <th>Empleado</th>
                        <th>Primera entrada</th>
                        <th>Última salida</th>
                        <th>Horas trabajadas</th>
                        <th>Puntualidad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumen as $r)
                        <tr class="as-tr">
                            <td class="as-td">{{ $r['fecha']->format('d/m/Y') }}</td>
                            <td class="as-td" style="font-weight:600; color:#111827;">{{ $r['empleado'] }}</td>
                            <td class="as-td">{{ $r['primera_entrada']->format('H:i') }}</td>
                            <td class="as-td">{{ $r['ultima_salida']?->format('H:i') ?? '—' }}</td>
                            <td class="as-td">{{ $r['horas_trabajadas'] !== null ? number_format($r['horas_trabajadas'], 1).' hrs' : '—' }}</td>
                            <td class="as-td">
                                @if($r['llego_tarde'])
                                    <span class="as-badge" style="background:#fee2e2; color:#dc2626;">Tarde</span>
                                @else
                                    <span class="as-badge" style="background:#dcfce7; color:#16a34a;">A tiempo</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
</x-filament-panels::page>
