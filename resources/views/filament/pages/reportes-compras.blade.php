<x-filament-panels::page>
<style>
    .rcm-input, .rcm-select {
        border: 1px solid #d1d5db; border-radius: 0.5rem;
        padding: 0.5rem 0.75rem; font-size: 0.875rem;
        color: #111827; background: #fff; outline: none;
    }
    .rcm-input:focus, .rcm-select:focus { border-color: #d97706; box-shadow: 0 0 0 2px rgba(217,119,6,.2); }

    .rcm-filters { display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; }
    .rcm-nav-btn { display: inline-flex; align-items: center; justify-content: center; width: 2.1rem; height: 2.1rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; cursor: pointer; color: #374151; }
    .rcm-nav-btn:hover { background: #f9fafb; }
    .rcm-periodo-label { font-weight: 700; font-size: 0.9rem; color: #111827; }

    .rcm-proveedor-panel { position: relative; }
    .rcm-proveedor-btn { border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.85rem; background: #fff; cursor: pointer; }
    .rcm-proveedor-dropdown { display: none; position: absolute; z-index: 20; top: calc(100% + 4px); left: 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.6rem; box-shadow: 0 8px 24px rgba(0,0,0,.12); padding: 0.6rem; min-width: 220px; max-height: 260px; overflow-y: auto; }
    .rcm-proveedor-dropdown.show { display: block; }
    .rcm-proveedor-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.3rem 0.2rem; font-size: 0.82rem; }

    .rcm-stat-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; margin-bottom: 1.25rem; }
    @media (max-width: 1200px) { .rcm-stat-grid { grid-template-columns: repeat(2,1fr); } }
    .rcm-stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem 1.2rem; }
    .rcm-stat-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
    .rcm-stat-num { font-size: 1.4rem; font-weight: 800; color: #111827; margin-top: .2rem; }

    .rcm-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; margin-bottom: 1.25rem; }
    .rcm-card-header { padding: 0.9rem 1.25rem; border-bottom: 1px solid #e5e7eb; font-size: 0.85rem; font-weight: 700; color: #111827; }

    .rcm-thead th { padding: 0.6rem 0.9rem; text-align: left; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .rcm-tr { border-bottom: 1px solid #f3f4f6; }
    .rcm-tr:hover { background: #f9fafb; }
    .rcm-td { padding: 0.65rem 0.9rem; font-size: 0.82rem; color: #374151; }
    .rcm-empty { text-align: center; padding: 2.5rem; color: #6b7280; font-size: 0.85rem; }
</style>

@php
    $resumen = $this->getResumen();
    $totales = $this->getTotales($resumen);
    $tendencia = $this->getTendencia();
    $proveedores = $this->getProveedores();
    $topProductos = $this->getTopProductos();
@endphp

{{-- ── Filtros ─────────────────────────────────────────────────────────── --}}
<div class="rcm-filters">
    <select wire:model.live="periodoTipo" class="rcm-select">
        <option value="semana">Semana</option>
        <option value="quincena">Quincena</option>
        <option value="mes">Mes</option>
    </select>

    <button type="button" class="rcm-nav-btn" wire:click="irPeriodoAnterior">‹</button>
    <span class="rcm-periodo-label">{{ $this->getEtiquetaPeriodo() }}</span>
    <button type="button" class="rcm-nav-btn" wire:click="irPeriodoSiguiente">›</button>

    <input type="date" wire:model.live="fechaReferencia" class="rcm-input">

    <div class="rcm-proveedor-panel" x-data="{ open: false }">
        <button type="button" class="rcm-proveedor-btn" x-on:click="open = !open">
            🚚 Proveedores {{ count($proveedorIds) ? '('.count($proveedorIds).')' : '(todos)' }}
        </button>
        <div class="rcm-proveedor-dropdown" x-bind:class="{ show: open }" x-on:click.outside="open = false">
            <label class="rcm-proveedor-item">
                <input type="checkbox" {{ empty($proveedorIds) ? 'checked' : '' }}
                    onclick="if(this.checked) window.Livewire.find('{{ $this->getId() }}').set('proveedorIds', [])">
                <strong>Todos</strong>
            </label>
            <hr style="margin:.4rem 0; border-color:#f3f4f6;">
            @foreach($proveedores as $p)
                <label class="rcm-proveedor-item">
                    <input type="checkbox" value="{{ $p->id }}" wire:model.live="proveedorIds">
                    {{ $p->nombre }}
                </label>
            @endforeach
        </div>
    </div>
</div>

{{-- ── Totales del periodo ─────────────────────────────────────────────── --}}
<div class="rcm-stat-grid">
    <div class="rcm-stat-card">
        <div class="rcm-stat-label">Total comprado</div>
        <div class="rcm-stat-num" style="color:#d97706;">${{ number_format($totales['total_comprado'], 2) }}</div>
    </div>
    <div class="rcm-stat-card">
        <div class="rcm-stat-label">Compras realizadas</div>
        <div class="rcm-stat-num">{{ $totales['total_compras'] }}</div>
    </div>
    <div class="rcm-stat-card">
        <div class="rcm-stat-label">Saldo pendiente a proveedores</div>
        <div class="rcm-stat-num" style="color:#dc2626;">${{ number_format($totales['saldo_pendiente'], 2) }}</div>
    </div>
    <div class="rcm-stat-card">
        <div class="rcm-stat-label">Ticket promedio</div>
        <div class="rcm-stat-num" style="color:#6366f1;">${{ number_format($totales['ticket_promedio'], 2) }}</div>
    </div>
</div>

{{-- ── Tendencia ────────────────────────────────────────────────────────── --}}
<div class="rcm-card">
    <div class="rcm-card-header">Tendencia de compras en el periodo</div>
    <div style="padding: 1rem 1.25rem;">
        <canvas id="rcm-chart-tendencia" height="80"></canvas>
    </div>
</div>

{{-- ── Comparativo por proveedor ────────────────────────────────────────── --}}
<div class="rcm-card">
    <div class="rcm-card-header">Comparativo por proveedor</div>
    @if(empty($resumen))
        <div class="rcm-empty">No hay proveedores para mostrar con este filtro.</div>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead class="rcm-thead">
                    <tr>
                        <th>Proveedor</th>
                        <th>Total comprado</th>
                        <th>Compras</th>
                        <th>Saldo pendiente</th>
                        <th>Ticket promedio</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumen as $r)
                        <tr class="rcm-tr">
                            <td class="rcm-td" style="font-weight:600; color:#111827;">{{ $r['proveedor']->nombre }}</td>
                            <td class="rcm-td" style="font-weight:700; color:#d97706;">${{ number_format($r['total_comprado'], 2) }}</td>
                            <td class="rcm-td">{{ $r['total_compras'] }}</td>
                            <td class="rcm-td" style="color:{{ $r['saldo_pendiente'] > 0 ? '#dc2626' : '#16a34a' }};">${{ number_format($r['saldo_pendiente'], 2) }}</td>
                            <td class="rcm-td">${{ number_format($r['ticket_promedio'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ── Top productos comprados ──────────────────────────────────────────── --}}
<div class="rcm-card">
    <div class="rcm-card-header">Productos más comprados del periodo</div>
    @if(empty($topProductos))
        <div class="rcm-empty">No hay compras en este periodo.</div>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead class="rcm-thead">
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th>Código</th>
                        <th>Unidades</th>
                        <th>Total comprado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topProductos as $i => $p)
                        <tr class="rcm-tr">
                            <td class="rcm-td">{{ $i + 1 }}</td>
                            <td class="rcm-td" style="font-weight:600; color:#111827;">{{ $p['nombre'] }}</td>
                            <td class="rcm-td" style="color:#9ca3af;">{{ $p['codigo'] }}</td>
                            <td class="rcm-td">{{ $p['unidades'] }}</td>
                            <td class="rcm-td" style="font-weight:700; color:#d97706;">${{ number_format($p['total'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<script>
    (function () {
        function dibujar() {
            var ctx = document.getElementById('rcm-chart-tendencia');
            if (!ctx || typeof Chart === 'undefined') return;
            if (window.rcmChartTendencia) window.rcmChartTendencia.destroy();
            window.rcmChartTendencia = new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json(collect($tendencia)->pluck('fecha')),
                datasets: [{
                    label: 'Comprado',
                    data: @json(collect($tendencia)->pluck('total')),
                    borderColor: '#d97706',
                    backgroundColor: 'rgba(217,119,6,.1)',
                    fill: true,
                    tension: 0.3,
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } },
            },
            });
        }

        if (typeof Chart === 'undefined') {
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
            script.onload = dibujar;
            document.head.appendChild(script);
        } else {
            dibujar();
        }
    })();
</script>
</x-filament-panels::page>
