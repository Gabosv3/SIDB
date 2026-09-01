<x-filament-panels::page>
<style>
    .rc-stat-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; margin-bottom: 1.25rem; }
    @media (max-width: 1200px) { .rc-stat-grid { grid-template-columns: repeat(2,1fr); } }
    .rc-stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem 1.2rem; }
    .rc-stat-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
    .rc-stat-num { font-size: 1.4rem; font-weight: 800; color: #111827; margin-top: .2rem; }

    .rc-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; margin-bottom: 1.25rem; }
    .rc-card-header { padding: 0.9rem 1.25rem; border-bottom: 1px solid #e5e7eb; font-size: 0.85rem; font-weight: 700; color: #111827; }

    .rc-thead th { padding: 0.6rem 0.9rem; text-align: left; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .rc-tr { border-bottom: 1px solid #f3f4f6; }
    .rc-tr:hover { background: #f9fafb; }
    .rc-td { padding: 0.65rem 0.9rem; font-size: 0.82rem; color: #374151; }
    .rc-empty { text-align: center; padding: 2.5rem; color: #6b7280; font-size: 0.85rem; }
    .rc-badge { display: inline-flex; align-items: center; padding: .15rem .55rem; border-radius: 9999px; font-size: .7rem; font-weight: 700; }

    .rc-aging-grid { display: grid; grid-template-columns: repeat(5,1fr); gap: 1rem; padding: 1.1rem 1.25rem; }
    @media (max-width: 900px) { .rc-aging-grid { grid-template-columns: repeat(2,1fr); } }
    .rc-aging-box { border-radius: 0.6rem; padding: .8rem 1rem; text-align: center; }
    .rc-aging-label { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; opacity: .8; }
    .rc-aging-num { font-size: 1.15rem; font-weight: 800; margin-top: .25rem; }
    .rc-aging-count { font-size: .72rem; opacity: .75; margin-top: .15rem; }
</style>

@php
    $totales = $this->getTotales();
    $antiguedad = $this->getAntiguedad();
    $porRuta = $this->getResumenPorRuta();
    $atrasados = $this->getClientesMasAtrasados();

    $coloresAging = [
        '0-15' => ['bg' => '#dcfce7', 'fg' => '#16a34a'],
        '16-30' => ['bg' => '#fef9c3', 'fg' => '#a16207'],
        '31-60' => ['bg' => '#ffedd5', 'fg' => '#c2410c'],
        '61-90' => ['bg' => '#fee2e2', 'fg' => '#dc2626'],
        '90+' => ['bg' => '#fecaca', 'fg' => '#991b1b'],
    ];
@endphp

{{-- ── Totales ─────────────────────────────────────────────────────────── --}}
<div class="rc-stat-grid">
    <div class="rc-stat-card">
        <div class="rc-stat-label">Clientes con saldo</div>
        <div class="rc-stat-num">{{ $totales['total_clientes_con_saldo'] }}</div>
    </div>
    <div class="rc-stat-card">
        <div class="rc-stat-label">Cartera total</div>
        <div class="rc-stat-num" style="color:#6366f1;">${{ number_format($totales['cartera_total'], 2) }}</div>
    </div>
    <div class="rc-stat-card">
        <div class="rc-stat-label">Clientes en mora (+30 días)</div>
        <div class="rc-stat-num" style="color:#dc2626;">{{ $totales['clientes_en_mora'] }}</div>
    </div>
    <div class="rc-stat-card">
        <div class="rc-stat-label">Monto en mora</div>
        <div class="rc-stat-num" style="color:#dc2626;">${{ number_format($totales['monto_en_mora'], 2) }}</div>
    </div>
</div>

{{-- ── Antigüedad de saldos ────────────────────────────────────────────── --}}
<div class="rc-card">
    <div class="rc-card-header">Antigüedad de saldos (días desde la venta más vieja sin saldar)</div>
    <div class="rc-aging-grid">
        @foreach($antiguedad as $etiqueta => $fila)
            <div class="rc-aging-box" style="background: {{ $coloresAging[$etiqueta]['bg'] }}; color: {{ $coloresAging[$etiqueta]['fg'] }};">
                <div class="rc-aging-label">{{ $etiqueta }} días</div>
                <div class="rc-aging-num">${{ number_format($fila['monto'], 2) }}</div>
                <div class="rc-aging-count">{{ $fila['clientes'] }} cliente(s)</div>
            </div>
        @endforeach
    </div>
</div>

{{-- ── Cartera por ruta ────────────────────────────────────────────────── --}}
<div class="rc-card">
    <div class="rc-card-header">Cartera y morosidad por ruta</div>
    @if(empty($porRuta))
        <div class="rc-empty">No hay rutas activas.</div>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead class="rc-thead">
                    <tr>
                        <th>Ruta</th>
                        <th>Clientes con saldo</th>
                        <th>Cartera total</th>
                        <th>Clientes en mora</th>
                        <th>Monto en mora</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($porRuta as $r)
                        <tr class="rc-tr">
                            <td class="rc-td" style="font-weight:600; color:#111827;">{{ $r['ruta'] }}</td>
                            <td class="rc-td">{{ $r['total_clientes'] }}</td>
                            <td class="rc-td" style="font-weight:700; color:#6366f1;">${{ number_format($r['cartera_total'], 2) }}</td>
                            <td class="rc-td">
                                <span class="rc-badge" style="background:{{ $r['clientes_en_mora'] > 0 ? '#fee2e2' : '#dcfce7' }}; color:{{ $r['clientes_en_mora'] > 0 ? '#dc2626' : '#16a34a' }};">{{ $r['clientes_en_mora'] }}</span>
                            </td>
                            <td class="rc-td" style="color:#dc2626;">${{ number_format($r['monto_en_mora'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ── Clientes más atrasados ──────────────────────────────────────────── --}}
<div class="rc-card">
    <div class="rc-card-header">Clientes más atrasados</div>
    @if(empty($atrasados))
        <div class="rc-empty">No hay clientes con saldo pendiente.</div>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead class="rc-thead">
                    <tr>
                        <th>Cliente</th>
                        <th>Ruta</th>
                        <th>Saldo</th>
                        <th>Días transcurridos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($atrasados as $c)
                        <tr class="rc-tr">
                            <td class="rc-td" style="font-weight:600; color:#111827;">{{ $c['nombre'] }}</td>
                            <td class="rc-td">{{ $c['ruta'] }}</td>
                            <td class="rc-td" style="font-weight:700; color:#6366f1;">${{ number_format($c['saldo'], 2) }}</td>
                            <td class="rc-td">
                                <span class="rc-badge" style="background:{{ $c['dias_transcurridos'] > 30 ? '#fee2e2' : '#dcfce7' }}; color:{{ $c['dias_transcurridos'] > 30 ? '#dc2626' : '#16a34a' }};">{{ $c['dias_transcurridos'] }} días</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
</x-filament-panels::page>
