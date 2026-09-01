<x-filament-panels::page>
<style>
    .rv-input, .rv-select {
        border: 1px solid #d1d5db; border-radius: 0.5rem;
        padding: 0.5rem 0.75rem; font-size: 0.875rem;
        color: #111827; background: #fff; outline: none;
    }
    .rv-input:focus, .rv-select:focus { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,.2); }

    .rv-filters { display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; }
    .rv-nav-btn { display: inline-flex; align-items: center; justify-content: center; width: 2.1rem; height: 2.1rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; cursor: pointer; color: #374151; }
    .rv-nav-btn:hover { background: #f9fafb; }
    .rv-periodo-label { font-weight: 700; font-size: 0.9rem; color: #111827; }

    .rv-vendedor-panel { position: relative; }
    .rv-vendedor-btn { border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.85rem; background: #fff; cursor: pointer; }
    .rv-vendedor-dropdown { display: none; position: absolute; z-index: 20; top: calc(100% + 4px); left: 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.6rem; box-shadow: 0 8px 24px rgba(0,0,0,.12); padding: 0.6rem; min-width: 220px; max-height: 260px; overflow-y: auto; }
    .rv-vendedor-dropdown.show { display: block; }
    .rv-vendedor-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.3rem 0.2rem; font-size: 0.82rem; }

    .rv-stat-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; margin-bottom: 1.25rem; }
    @media (max-width: 1200px) { .rv-stat-grid { grid-template-columns: repeat(2,1fr); } }
    .rv-stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem 1.2rem; }
    .rv-stat-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
    .rv-stat-num { font-size: 1.4rem; font-weight: 800; color: #111827; margin-top: .2rem; }

    .rv-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; margin-bottom: 1.25rem; }
    .rv-card-header { padding: 0.9rem 1.25rem; border-bottom: 1px solid #e5e7eb; font-size: 0.85rem; font-weight: 700; color: #111827; }

    .rv-thead th { padding: 0.6rem 0.9rem; text-align: left; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .rv-tr { border-bottom: 1px solid #f3f4f6; }
    .rv-tr:hover { background: #f9fafb; }
    .rv-td { padding: 0.65rem 0.9rem; font-size: 0.82rem; color: #374151; }
    .rv-empty { text-align: center; padding: 2.5rem; color: #6b7280; font-size: 0.85rem; }
    .rv-badge { display: inline-flex; align-items: center; padding: .15rem .55rem; border-radius: 9999px; font-size: .7rem; font-weight: 700; }
</style>

@php
    $resumen = $this->getResumen();
    $totales = $this->getTotales($resumen);
    $tendencia = $this->getTendencia();
    $vendedores = $this->getVendedores();
    $topProductos = $this->getTopProductos();
@endphp

{{-- ── Filtros ─────────────────────────────────────────────────────────── --}}
<div class="rv-filters">
    <select wire:model.live="periodoTipo" class="rv-select">
        <option value="semana">Semana</option>
        <option value="quincena">Quincena</option>
        <option value="mes">Mes</option>
    </select>

    <button type="button" class="rv-nav-btn" wire:click="irPeriodoAnterior">‹</button>
    <span class="rv-periodo-label">{{ $this->getEtiquetaPeriodo() }}</span>
    <button type="button" class="rv-nav-btn" wire:click="irPeriodoSiguiente">›</button>

    <input type="date" wire:model.live="fechaReferencia" class="rv-input">

    <div class="rv-vendedor-panel" x-data="{ open: false }">
        <button type="button" class="rv-vendedor-btn" x-on:click="open = !open">
            🧑‍💼 Vendedores {{ count($vendedorIds) ? '('.count($vendedorIds).')' : '(todos)' }}
        </button>
        <div class="rv-vendedor-dropdown" x-bind:class="{ show: open }" x-on:click.outside="open = false">
            <label class="rv-vendedor-item">
                <input type="checkbox" {{ empty($vendedorIds) ? 'checked' : '' }}
                    onclick="if(this.checked) window.Livewire.find('{{ $this->getId() }}').set('vendedorIds', [])">
                <strong>Todos</strong>
            </label>
            <hr style="margin:.4rem 0; border-color:#f3f4f6;">
            @foreach($vendedores as $v)
                <label class="rv-vendedor-item">
                    <input type="checkbox" value="{{ $v->id }}" wire:model.live="vendedorIds">
                    {{ $v->nombre }} {{ $v->apellido }}
                </label>
            @endforeach
        </div>
    </div>
</div>

{{-- ── Totales del periodo ─────────────────────────────────────────────── --}}
<div class="rv-stat-grid">
    <div class="rv-stat-card">
        <div class="rv-stat-label">Total vendido</div>
        <div class="rv-stat-num" style="color:#16a34a;">${{ number_format($totales['total_vendido'], 2) }}</div>
    </div>
    <div class="rv-stat-card">
        <div class="rv-stat-label">Ventas realizadas</div>
        <div class="rv-stat-num">{{ $totales['total_ventas'] }}</div>
    </div>
    <div class="rv-stat-card">
        <div class="rv-stat-label">Unidades vendidas</div>
        <div class="rv-stat-num">{{ $totales['total_unidades'] }}</div>
    </div>
    <div class="rv-stat-card">
        <div class="rv-stat-label">Ticket promedio</div>
        <div class="rv-stat-num" style="color:#6366f1;">${{ number_format($totales['ticket_promedio'], 2) }}</div>
    </div>
</div>

{{-- ── Tendencia ────────────────────────────────────────────────────────── --}}
<div class="rv-card">
    <div class="rv-card-header">Tendencia de ventas en el periodo</div>
    <div style="padding: 1rem 1.25rem;">
        <canvas id="rv-chart-tendencia" height="80"></canvas>
    </div>
</div>

{{-- ── Comparativo por vendedor ─────────────────────────────────────────── --}}
<div class="rv-card">
    <div class="rv-card-header">Comparativo por vendedor</div>
    @if(empty($resumen))
        <div class="rv-empty">No hay vendedores para mostrar con este filtro.</div>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead class="rv-thead">
                    <tr>
                        <th>Vendedor</th>
                        <th>Total vendido</th>
                        <th>Ventas</th>
                        <th>Unidades</th>
                        <th>Ticket promedio</th>
                        <th>Crédito / Contado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumen as $r)
                        <tr class="rv-tr">
                            <td class="rv-td" style="font-weight:600; color:#111827;">
                                {{ trim(($r['vendedor']->nombre ?? '').' '.($r['vendedor']->apellido ?? '')) }}
                            </td>
                            <td class="rv-td" style="font-weight:700; color:#16a34a;">${{ number_format($r['total_vendido'], 2) }}</td>
                            <td class="rv-td">{{ $r['total_ventas'] }}</td>
                            <td class="rv-td">{{ $r['total_unidades'] }}</td>
                            <td class="rv-td">${{ number_format($r['ticket_promedio'], 2) }}</td>
                            <td class="rv-td">
                                <span class="rv-badge" style="background:#e0e7ff; color:#4338ca;">{{ $r['ventas_credito'] }} créd.</span>
                                <span class="rv-badge" style="background:#dcfce7; color:#16a34a;">{{ $r['ventas_contado'] }} cont.</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ── Top productos vendidos ───────────────────────────────────────────── --}}
<div class="rv-card">
    <div class="rv-card-header">Productos más vendidos del periodo</div>
    @if(empty($topProductos))
        <div class="rv-empty">No hay ventas en este periodo.</div>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead class="rv-thead">
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th>Código</th>
                        <th>Unidades</th>
                        <th>Total vendido</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topProductos as $i => $p)
                        <tr class="rv-tr">
                            <td class="rv-td">{{ $i + 1 }}</td>
                            <td class="rv-td" style="font-weight:600; color:#111827;">{{ $p['nombre'] }}</td>
                            <td class="rv-td" style="color:#9ca3af;">{{ $p['codigo'] }}</td>
                            <td class="rv-td">{{ $p['unidades'] }}</td>
                            <td class="rv-td" style="font-weight:700; color:#16a34a;">${{ number_format($p['total'], 2) }}</td>
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
            var ctx = document.getElementById('rv-chart-tendencia');
            if (!ctx || typeof Chart === 'undefined') return;
            if (window.rvChartTendencia) window.rvChartTendencia.destroy();
            window.rvChartTendencia = new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json(collect($tendencia)->pluck('fecha')),
                datasets: [{
                    label: 'Vendido',
                    data: @json(collect($tendencia)->pluck('total')),
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22,163,74,.1)',
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
