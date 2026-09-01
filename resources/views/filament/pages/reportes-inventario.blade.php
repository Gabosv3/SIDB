<x-filament-panels::page>
<style>
    .ri-stat-grid { display: grid; grid-template-columns: repeat(5,1fr); gap: 1rem; margin-bottom: 1.25rem; }
    @media (max-width: 1200px) { .ri-stat-grid { grid-template-columns: repeat(2,1fr); } }
    .ri-stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem 1.2rem; }
    .ri-stat-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
    .ri-stat-num { font-size: 1.4rem; font-weight: 800; color: #111827; margin-top: .2rem; }

    .ri-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; margin-bottom: 1.25rem; }
    .ri-card-header { padding: 0.9rem 1.25rem; border-bottom: 1px solid #e5e7eb; font-size: 0.85rem; font-weight: 700; color: #111827; display: flex; align-items: center; justify-content: space-between; }

    .ri-thead th { padding: 0.6rem 0.9rem; text-align: left; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .ri-tr { border-bottom: 1px solid #f3f4f6; }
    .ri-tr:hover { background: #f9fafb; }
    .ri-td { padding: 0.65rem 0.9rem; font-size: 0.82rem; color: #374151; }
    .ri-empty { text-align: center; padding: 2.5rem; color: #6b7280; font-size: 0.85rem; }
    .ri-badge { display: inline-flex; align-items: center; padding: .15rem .55rem; border-radius: 9999px; font-size: .7rem; font-weight: 700; }
</style>

@php
    $totales = $this->getTotales();
    $stockBajo = $this->getStockBajo();
    $mayorValorizacion = $this->getMayorValorizacion();
    $sinMovimiento = $this->getSinMovimiento();
@endphp

{{-- ── Totales ─────────────────────────────────────────────────────────── --}}
<div class="ri-stat-grid">
    <div class="ri-stat-card">
        <div class="ri-stat-label">Productos activos</div>
        <div class="ri-stat-num">{{ $totales['total_productos'] }}</div>
    </div>
    <div class="ri-stat-card">
        <div class="ri-stat-label">Unidades en stock</div>
        <div class="ri-stat-num">{{ $totales['unidades_totales'] }}</div>
    </div>
    <div class="ri-stat-card">
        <div class="ri-stat-label">Valor a costo</div>
        <div class="ri-stat-num" style="color:#6366f1;">${{ number_format($totales['valor_costo'], 2) }}</div>
    </div>
    <div class="ri-stat-card">
        <div class="ri-stat-label">Valor a venta</div>
        <div class="ri-stat-num" style="color:#16a34a;">${{ number_format($totales['valor_venta'], 2) }}</div>
    </div>
    <div class="ri-stat-card">
        <div class="ri-stat-label">Con stock bajo</div>
        <div class="ri-stat-num" style="color:#dc2626;">{{ $totales['productos_stock_bajo'] }}</div>
    </div>
</div>

{{-- ── Stock bajo ──────────────────────────────────────────────────────── --}}
<div class="ri-card">
    <div class="ri-card-header">⚠ Productos con stock en o bajo el mínimo</div>
    @if(empty($stockBajo))
        <div class="ri-empty">Ningún producto está por debajo de su stock mínimo. 🎉</div>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead class="ri-thead">
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Stock actual</th>
                        <th>Stock mínimo</th>
                        <th>Faltante</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stockBajo as $p)
                        <tr class="ri-tr">
                            <td class="ri-td" style="color:#9ca3af;">{{ $p['codigo'] }}</td>
                            <td class="ri-td" style="font-weight:600; color:#111827;">{{ $p['nombre'] }}</td>
                            <td class="ri-td">
                                <span class="ri-badge" style="background:{{ $p['stock'] <= 0 ? '#fee2e2' : '#fef3c7' }}; color:{{ $p['stock'] <= 0 ? '#dc2626' : '#b45309' }};">{{ $p['stock'] }}</span>
                            </td>
                            <td class="ri-td">{{ $p['stock_minimo'] }}</td>
                            <td class="ri-td" style="font-weight:700; color:#dc2626;">{{ $p['faltante'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ── Mayor valorización ──────────────────────────────────────────────── --}}
<div class="ri-card">
    <div class="ri-card-header">Productos con mayor valorización en inventario</div>
    @if(empty($mayorValorizacion))
        <div class="ri-empty">No hay productos con stock.</div>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead class="ri-thead">
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Stock</th>
                        <th>Valor a costo</th>
                        <th>Valor a venta</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mayorValorizacion as $p)
                        <tr class="ri-tr">
                            <td class="ri-td" style="color:#9ca3af;">{{ $p['codigo'] }}</td>
                            <td class="ri-td" style="font-weight:600; color:#111827;">{{ $p['nombre'] }}</td>
                            <td class="ri-td">{{ $p['stock'] }}</td>
                            <td class="ri-td" style="font-weight:700; color:#6366f1;">${{ number_format($p['valor_costo'], 2) }}</td>
                            <td class="ri-td" style="color:#16a34a;">${{ number_format($p['valor_venta'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ── Sin movimiento ──────────────────────────────────────────────────── --}}
<div class="ri-card">
    <div class="ri-card-header">Productos sin movimiento de stock en los últimos 60 días</div>
    @if(empty($sinMovimiento))
        <div class="ri-empty">Todos los productos con stock han tenido movimiento reciente.</div>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead class="ri-thead">
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Stock</th>
                        <th>Valor a costo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sinMovimiento as $p)
                        <tr class="ri-tr">
                            <td class="ri-td" style="color:#9ca3af;">{{ $p['codigo'] }}</td>
                            <td class="ri-td" style="font-weight:600; color:#111827;">{{ $p['nombre'] }}</td>
                            <td class="ri-td">{{ $p['stock'] }}</td>
                            <td class="ri-td" style="font-weight:700; color:#b45309;">${{ number_format($p['valor_costo'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
</x-filament-panels::page>
