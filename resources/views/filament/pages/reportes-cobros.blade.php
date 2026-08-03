<x-filament-panels::page>
<style>
    .rp-input, .rp-select {
        border: 1px solid #d1d5db; border-radius: 0.5rem;
        padding: 0.5rem 0.75rem; font-size: 0.875rem;
        color: #111827; background: #fff; outline: none;
    }
    .rp-input:focus, .rp-select:focus { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,.2); }

    .rp-filters { display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; }
    .rp-nav-btn { display: inline-flex; align-items: center; justify-content: center; width: 2.1rem; height: 2.1rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; cursor: pointer; color: #374151; }
    .rp-nav-btn:hover { background: #f9fafb; }
    .rp-periodo-label { font-weight: 700; font-size: 0.9rem; color: #111827; }

    .rp-cobrador-panel { position: relative; }
    .rp-cobrador-btn { border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.85rem; background: #fff; cursor: pointer; }
    .rp-cobrador-dropdown { display: none; position: absolute; z-index: 20; top: calc(100% + 4px); left: 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.6rem; box-shadow: 0 8px 24px rgba(0,0,0,.12); padding: 0.6rem; min-width: 220px; max-height: 260px; overflow-y: auto; }
    .rp-cobrador-dropdown.show { display: block; }
    .rp-cobrador-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.3rem 0.2rem; font-size: 0.82rem; }

    .rp-stat-grid { display: grid; grid-template-columns: repeat(5,1fr); gap: 1rem; margin-bottom: 1.25rem; }
    @media (max-width: 1200px) { .rp-stat-grid { grid-template-columns: repeat(2,1fr); } }
    .rp-stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem 1.2rem; }
    .rp-stat-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
    .rp-stat-num { font-size: 1.4rem; font-weight: 800; color: #111827; margin-top: .2rem; }

    .rp-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; margin-bottom: 1.25rem; }
    .rp-card-header { padding: 0.9rem 1.25rem; border-bottom: 1px solid #e5e7eb; font-size: 0.85rem; font-weight: 700; color: #111827; }

    .rp-thead th { padding: 0.6rem 0.9rem; text-align: left; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .rp-tr { border-bottom: 1px solid #f3f4f6; }
    .rp-tr:hover { background: #f9fafb; }
    .rp-td { padding: 0.65rem 0.9rem; font-size: 0.82rem; color: #374151; }
    .rp-empty { text-align: center; padding: 2.5rem; color: #6b7280; font-size: 0.85rem; }
    .rp-badge { display: inline-flex; align-items: center; padding: .15rem .55rem; border-radius: 9999px; font-size: .7rem; font-weight: 700; }
</style>

@php
    $resumen = $this->getResumen();
    $totales = $this->getTotales($resumen);
    $tendencia = $this->getTendencia();
    $cobradores = $this->getCobradores();
@endphp

{{-- ── Filtros ─────────────────────────────────────────────────────────── --}}
<div class="rp-filters">
    <select wire:model.live="periodoTipo" class="rp-select">
        <option value="semana">Semana</option>
        <option value="quincena">Quincena</option>
        <option value="mes">Mes</option>
    </select>

    <button type="button" class="rp-nav-btn" wire:click="irPeriodoAnterior">‹</button>
    <span class="rp-periodo-label">{{ $this->getEtiquetaPeriodo() }}</span>
    <button type="button" class="rp-nav-btn" wire:click="irPeriodoSiguiente">›</button>

    <input type="date" wire:model.live="fechaReferencia" class="rp-input">

    <div class="rp-cobrador-panel" x-data="{ open: false }">
        <button type="button" class="rp-cobrador-btn" x-on:click="open = !open">
            👤 Cobradores {{ count($cobradorIds) ? '('.count($cobradorIds).')' : '(todos)' }}
        </button>
        <div class="rp-cobrador-dropdown" x-bind:class="{ show: open }" x-on:click.outside="open = false">
            <label class="rp-cobrador-item">
                <input type="checkbox" {{ empty($cobradorIds) ? 'checked' : '' }}
                    onclick="if(this.checked) window.Livewire.find('{{ $this->getId() }}').set('cobradorIds', [])">
                <strong>Todos</strong>
            </label>
            <hr style="margin:.4rem 0; border-color:#f3f4f6;">
            @foreach($cobradores as $c)
                <label class="rp-cobrador-item">
                    <input type="checkbox" value="{{ $c->id }}" wire:model.live="cobradorIds">
                    {{ $c->nombre }} {{ $c->apellido }}
                </label>
            @endforeach
        </div>
    </div>
</div>

{{-- ── Totales del periodo ─────────────────────────────────────────────── --}}
<div class="rp-stat-grid">
    <div class="rp-stat-card">
        <div class="rp-stat-label">Total cobrado</div>
        <div class="rp-stat-num" style="color:#16a34a;">${{ number_format($totales['total_cobrado'], 2) }}</div>
    </div>
    <div class="rp-stat-card">
        <div class="rp-stat-label">Clientes atendidos</div>
        <div class="rp-stat-num">{{ $totales['clientes_atendidos'] }}</div>
    </div>
    <div class="rp-stat-card">
        <div class="rp-stat-label">Clientes no visitados</div>
        <div class="rp-stat-num" style="color:#dc2626;">{{ $totales['clientes_no_visitados'] }}</div>
    </div>
    <div class="rp-stat-card">
        <div class="rp-stat-label">Efectividad promedio</div>
        <div class="rp-stat-num">{{ $totales['efectividad_promedio'] }}%</div>
    </div>
    <div class="rp-stat-card">
        <div class="rp-stat-label">Morosidad total</div>
        <div class="rp-stat-num" style="color:#d97706;">${{ number_format($totales['morosidad_monto'], 2) }}</div>
        <div style="font-size:.7rem; color:#9ca3af;">{{ $totales['morosidad_cantidad'] }} cuota(s) vencida(s)</div>
    </div>
</div>

{{-- ── Tendencia ────────────────────────────────────────────────────────── --}}
<div class="rp-card">
    <div class="rp-card-header">Tendencia de cobros en el periodo</div>
    <div style="padding: 1rem 1.25rem;">
        <canvas id="rp-chart-tendencia" height="80"></canvas>
    </div>
</div>

{{-- ── Comparativo por cobrador ─────────────────────────────────────────── --}}
<div class="rp-card">
    <div class="rp-card-header">Comparativo por cobrador</div>
    @if(empty($resumen))
        <div class="rp-empty">No hay cobradores para mostrar con este filtro.</div>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead class="rp-thead">
                    <tr>
                        <th>Cobrador</th>
                        <th>Total cobrado</th>
                        <th>Clientes atendidos</th>
                        <th>No visitados</th>
                        <th>Efectividad</th>
                        <th>Morosidad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumen as $r)
                        <tr class="rp-tr">
                            <td class="rp-td" style="font-weight:600; color:#111827;">
                                {{ trim(($r['cobrador']->nombre ?? '').' '.($r['cobrador']->apellido ?? '')) }}
                            </td>
                            <td class="rp-td" style="font-weight:700; color:#16a34a;">${{ number_format($r['total_cobrado'], 2) }}</td>
                            <td class="rp-td">{{ $r['clientes_atendidos'] }} <span style="color:#9ca3af;">({{ $r['clientes_con_pago'] }} pagó, {{ $r['visitas_sin_cobro'] }} sin cobro)</span></td>
                            <td class="rp-td">
                                <span class="rp-badge" style="background:{{ $r['clientes_no_visitados'] > 0 ? '#fee2e2' : '#dcfce7' }}; color:{{ $r['clientes_no_visitados'] > 0 ? '#dc2626' : '#16a34a' }};">
                                    {{ $r['clientes_no_visitados'] }} de {{ $r['clientes_con_saldo'] }}
                                </span>
                            </td>
                            <td class="rp-td">
                                <span class="rp-badge" style="background:{{ $r['efectividad_pct'] >= 70 ? '#dcfce7' : ($r['efectividad_pct'] >= 40 ? '#fef9c3' : '#fee2e2') }}; color:{{ $r['efectividad_pct'] >= 70 ? '#16a34a' : ($r['efectividad_pct'] >= 40 ? '#854d0e' : '#dc2626') }};">
                                    {{ $r['efectividad_pct'] }}%
                                </span>
                            </td>
                            <td class="rp-td">
                                ${{ number_format($r['morosidad_monto'], 2) }}
                                <span style="color:#9ca3af; font-size:.72rem;">({{ $r['morosidad_cantidad'] }})</span>
                            </td>
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
            var ctx = document.getElementById('rp-chart-tendencia');
            if (!ctx || typeof Chart === 'undefined') return;
            if (window.rpChartTendencia) window.rpChartTendencia.destroy();
            window.rpChartTendencia = new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json(collect($tendencia)->pluck('fecha')),
                datasets: [{
                    label: 'Cobrado',
                    data: @json(collect($tendencia)->pluck('total')),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,.1)',
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
