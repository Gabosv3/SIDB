<x-filament-panels::page>
<style>
    .rcs-input, .rcs-select {
        border: 1px solid #d1d5db; border-radius: 0.5rem;
        padding: 0.5rem 0.75rem; font-size: 0.875rem;
        color: #111827; background: #fff; outline: none;
    }
    .rcs-input:focus, .rcs-select:focus { border-color: #059669; box-shadow: 0 0 0 2px rgba(5,150,105,.2); }

    .rcs-filters { display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; }
    .rcs-nav-btn { display: inline-flex; align-items: center; justify-content: center; width: 2.1rem; height: 2.1rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; cursor: pointer; color: #374151; }
    .rcs-nav-btn:hover { background: #f9fafb; }
    .rcs-periodo-label { font-weight: 700; font-size: 0.9rem; color: #111827; }

    .rcs-stat-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; margin-bottom: 1.25rem; }
    @media (max-width: 1200px) { .rcs-stat-grid { grid-template-columns: repeat(2,1fr); } }
    .rcs-stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem 1.2rem; }
    .rcs-stat-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
    .rcs-stat-num { font-size: 1.4rem; font-weight: 800; color: #111827; margin-top: .2rem; }

    .rcs-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; margin-bottom: 1.25rem; }
    .rcs-card-header { padding: 0.9rem 1.25rem; border-bottom: 1px solid #e5e7eb; font-size: 0.85rem; font-weight: 700; color: #111827; }

    .rcs-thead th { padding: 0.6rem 0.9rem; text-align: left; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .rcs-tr { border-bottom: 1px solid #f3f4f6; }
    .rcs-tr:hover { background: #f9fafb; }
    .rcs-td { padding: 0.65rem 0.9rem; font-size: 0.82rem; color: #374151; }
    .rcs-empty { text-align: center; padding: 2.5rem; color: #6b7280; font-size: 0.85rem; }
    .rcs-badge { display: inline-flex; align-items: center; padding: .15rem .55rem; border-radius: 9999px; font-size: .7rem; font-weight: 700; }
</style>

@php
    $resumen = $this->getResumen();
    $totales = $this->getTotales($resumen);

    $etiquetaModalidad = [
        'salario_fijo' => ['texto' => 'Salario fijo', 'bg' => '#e0e7ff', 'fg' => '#4338ca'],
        'comision' => ['texto' => 'Comisión', 'bg' => '#dcfce7', 'fg' => '#16a34a'],
        'mixto' => ['texto' => 'Mixto', 'bg' => '#fef9c3', 'fg' => '#a16207'],
    ];
@endphp

{{-- ── Filtros ─────────────────────────────────────────────────────────── --}}
<div class="rcs-filters">
    <select wire:model.live="periodoTipo" class="rcs-select">
        <option value="semana">Semana</option>
        <option value="quincena">Quincena</option>
        <option value="mes">Mes</option>
    </select>

    <button type="button" class="rcs-nav-btn" wire:click="irPeriodoAnterior">‹</button>
    <span class="rcs-periodo-label">{{ $this->getEtiquetaPeriodo() }}</span>
    <button type="button" class="rcs-nav-btn" wire:click="irPeriodoSiguiente">›</button>

    <input type="date" wire:model.live="fechaReferencia" class="rcs-input">
</div>

{{-- ── Totales del periodo ─────────────────────────────────────────────── --}}
<div class="rcs-stat-grid">
    <div class="rcs-stat-card">
        <div class="rcs-stat-label">Total nómina del periodo</div>
        <div class="rcs-stat-num" style="color:#059669;">${{ number_format($totales['total_nomina'], 2) }}</div>
    </div>
    <div class="rcs-stat-card">
        <div class="rcs-stat-label">Empleados a pagar</div>
        <div class="rcs-stat-num">{{ $totales['total_empleados'] }}</div>
    </div>
    <div class="rcs-stat-card">
        <div class="rcs-stat-label">Total en comisiones</div>
        <div class="rcs-stat-num" style="color:#16a34a;">${{ number_format($totales['total_comisiones'], 2) }}</div>
    </div>
    <div class="rcs-stat-card">
        <div class="rcs-stat-label">Total en salarios fijos</div>
        <div class="rcs-stat-num" style="color:#4338ca;">${{ number_format($totales['total_salarios_fijos'], 2) }}</div>
    </div>
</div>

{{-- ── Detalle por empleado ────────────────────────────────────────────── --}}
<div class="rcs-card">
    <div class="rcs-card-header">Detalle de nómina por empleado</div>
    @if(empty($resumen))
        <div class="rcs-empty">No hay empleados activos con modalidad de pago configurada.</div>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead class="rcs-thead">
                    <tr>
                        <th>Empleado</th>
                        <th>Cargo</th>
                        <th>Modalidad</th>
                        <th>Vendido / Cobrado en periodo</th>
                        <th>Comisión</th>
                        <th>Salario base</th>
                        <th>Total a pagar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumen as $r)
                        <tr class="rcs-tr">
                            <td class="rcs-td" style="font-weight:600; color:#111827;">{{ $r['empleado'] }}</td>
                            <td class="rcs-td">{{ $r['cargo'] ?? '—' }}</td>
                            <td class="rcs-td">
                                <span class="rcs-badge" style="background:{{ $etiquetaModalidad[$r['modalidad_pago']]['bg'] ?? '#f3f4f6' }}; color:{{ $etiquetaModalidad[$r['modalidad_pago']]['fg'] ?? '#374151' }};">
                                    {{ $etiquetaModalidad[$r['modalidad_pago']]['texto'] ?? $r['modalidad_pago'] }}
                                </span>
                                @if($r['porcentaje_comision'] > 0)
                                    <span style="color:#6b7280; font-size:.75rem;">({{ $r['porcentaje_comision'] }}%)</span>
                                @endif
                            </td>
                            <td class="rcs-td">${{ number_format($r['base_vendido'] + $r['base_cobrado'], 2) }}</td>
                            <td class="rcs-td" style="color:#16a34a;">${{ number_format($r['comision'], 2) }}</td>
                            <td class="rcs-td" style="color:#4338ca;">${{ number_format($r['salario_base'], 2) }}</td>
                            <td class="rcs-td" style="font-weight:700; color:#059669;">${{ number_format($r['total_a_pagar'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
</x-filament-panels::page>
