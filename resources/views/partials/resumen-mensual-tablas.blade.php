@php
    $m = fn ($n) => '$' . number_format((float) $n, 2);
    $v = $r['ventas']; $c = $r['cobros']; $co = $r['compras']; $g = $r['gastos']; $cm = $r['comisiones']; $ca = $r['cartera']; $op = $r['operacion']; $f = $r['flujo'];
    $tipos = ['contado' => 'Contado', 'credito' => 'Crédito', 'mixta' => 'Mixta'];
@endphp

<h2 class="rm-h">Ventas</h2>
<table class="rm-table rm-kpis">
    <tr>
        <td><span>Total vendido</span><b>{{ $m($v['total']) }}</b></td>
        <td><span>Ventas</span><b>{{ $v['cantidad'] }}</b></td>
        <td><span>Ticket promedio</span><b>{{ $m($v['ticket_promedio']) }}</b></td>
        <td><span>Clientes nuevos</span><b>{{ $v['clientes_nuevos'] }}</b></td>
        <td><span>Primas recibidas</span><b>{{ $m($v['primas']) }}</b></td>
        <td><span>Descuentos</span><b>{{ $m($v['descuentos']) }}</b></td>
    </tr>
</table>
<table class="rm-table">
    <thead><tr><th>Tipo de pago</th><th class="n">Ventas</th><th class="n">Total</th></tr></thead>
    <tbody>
        @forelse($v['por_tipo'] as $tipo => $d)
            <tr><td>{{ $tipos[$tipo] ?? ucfirst($tipo) }}</td><td class="n">{{ $d['cantidad'] }}</td><td class="n">{{ $m($d['total']) }}</td></tr>
        @empty
            <tr><td colspan="3" class="vacio">Sin ventas en el mes.</td></tr>
        @endforelse
        @if($v['anuladas_cantidad'] > 0)
            <tr><td>Canceladas / devueltas (no cuentan)</td><td class="n">{{ $v['anuladas_cantidad'] }}</td><td class="n">{{ $m($v['anuladas_total']) }}</td></tr>
        @endif
    </tbody>
</table>
@if($v['por_vendedor']->isNotEmpty())
    <table class="rm-table">
        <thead><tr><th>Vendedor</th><th class="n">Ventas</th><th class="n">Total vendido</th></tr></thead>
        <tbody>
            @foreach($v['por_vendedor'] as $x)
                <tr><td>{{ $x['vendedor'] }}</td><td class="n">{{ $x['ventas'] }}</td><td class="n">{{ $m($x['total']) }}</td></tr>
            @endforeach
        </tbody>
    </table>
@endif

<h2 class="rm-h">Cobros</h2>
<table class="rm-table rm-kpis">
    <tr>
        <td><span>Total cobrado</span><b>{{ $m($c['total']) }}</b></td>
        <td><span>Pagos recibidos</span><b>{{ $c['pagos'] }}</b></td>
        <td><span>Clientes que pagaron</span><b>{{ $c['clientes'] }}</b></td>
    </tr>
</table>
@if($c['por_cobrador']->isNotEmpty())
    <table class="rm-table">
        <thead><tr><th>Cobrador</th><th class="n">Pagos</th><th class="n">Total cobrado</th></tr></thead>
        <tbody>
            @foreach($c['por_cobrador'] as $x)
                <tr><td>{{ $x['cobrador'] }}</td><td class="n">{{ $x['pagos'] }}</td><td class="n">{{ $m($x['total']) }}</td></tr>
            @endforeach
        </tbody>
    </table>
@endif

<h2 class="rm-h">Gastos</h2>
<table class="rm-table rm-kpis">
    <tr>
        <td><span>Gastos aprobados</span><b>{{ $m($g['total_aprobado']) }}</b></td>
        <td><span>Vehículos</span><b>{{ $m($g['vehiculos']) }}</b></td>
        <td><span>Consumo personal</span><b>{{ $m($g['consumo']) }}</b></td>
        <td><span>Pendientes de aprobar</span><b>{{ $g['pendientes_cantidad'] }} ({{ $m($g['pendientes_total']) }})</b></td>
    </tr>
</table>
@if($g['por_categoria']->isNotEmpty() || $g['por_vehiculo']->isNotEmpty())
    <table class="rm-table">
        <thead><tr><th>Gasto de vehículos por categoría</th><th class="n">Registros</th><th class="n">Total</th></tr></thead>
        <tbody>
            @foreach($g['por_categoria'] as $x)
                <tr><td>{{ $x['categoria'] }}</td><td class="n">{{ $x['registros'] }}</td><td class="n">{{ $m($x['total']) }}</td></tr>
            @endforeach
        </tbody>
    </table>
    <table class="rm-table">
        <thead><tr><th>Vehículo</th><th class="n">Registros</th><th class="n">Total</th></tr></thead>
        <tbody>
            @foreach($g['por_vehiculo'] as $x)
                <tr><td>{{ $x['vehiculo'] }}</td><td class="n">{{ $x['registros'] }}</td><td class="n">{{ $m($x['total']) }}</td></tr>
            @endforeach
        </tbody>
    </table>
@endif

<h2 class="rm-h">Compras y proveedores</h2>
<table class="rm-table rm-kpis">
    <tr>
        <td><span>Comprado en el mes</span><b>{{ $m($co['total']) }}</b></td>
        <td><span>Compras</span><b>{{ $co['cantidad'] }}</b></td>
        <td><span>Pagado a proveedores</span><b>{{ $m($co['pagado_proveedores']) }}</b></td>
        <td><span>Deuda con proveedores (a hoy)</span><b>{{ $m($co['deuda_proveedores']) }}</b></td>
    </tr>
</table>
@if($co['por_proveedor']->isNotEmpty())
    <table class="rm-table">
        <thead><tr><th>Proveedor</th><th class="n">Compras</th><th class="n">Total</th></tr></thead>
        <tbody>
            @foreach($co['por_proveedor'] as $x)
                <tr><td>{{ $x['proveedor'] }}</td><td class="n">{{ $x['compras'] }}</td><td class="n">{{ $m($x['total']) }}</td></tr>
            @endforeach
        </tbody>
    </table>
@endif

<h2 class="rm-h">Comisiones (estimadas)</h2>
<table class="rm-table rm-kpis">
    <tr>
        <td><span>Vendedores</span><b>{{ $m($cm['total_vendedores']) }}</b></td>
        <td><span>Cobradores ({{ \App\Services\ResumenMensualService::COMISION_COBRADOR_PCT }}%)</span><b>{{ $m($cm['total_cobradores']) }}</b></td>
        <td><span>Total comisiones</span><b>{{ $m($cm['total']) }}</b></td>
        <td><span>Anticipos entregados</span><b>{{ $m($cm['anticipos']) }}</b></td>
    </tr>
</table>
<table class="rm-table">
    <thead><tr><th>Vendedor</th><th class="n">Vendido (crédito/mixta)</th><th class="n">Comisión</th></tr></thead>
    <tbody>
        @forelse($cm['vendedores'] as $x)
            <tr><td>{{ $x['vendedor'] }}</td><td class="n">{{ $m($x['vendido']) }}</td><td class="n">{{ $m($x['comision']) }}</td></tr>
        @empty
            <tr><td colspan="3" class="vacio">Sin comisiones de vendedores.</td></tr>
        @endforelse
    </tbody>
</table>
<table class="rm-table">
    <thead><tr><th>Cobrador</th><th class="n">Cobrado</th><th class="n">Comisión</th></tr></thead>
    <tbody>
        @forelse($cm['cobradores'] as $x)
            <tr><td>{{ $x['cobrador'] }}</td><td class="n">{{ $m($x['cobrado']) }}</td><td class="n">{{ $m($x['comision']) }}</td></tr>
        @empty
            <tr><td colspan="3" class="vacio">Sin comisiones de cobradores.</td></tr>
        @endforelse
    </tbody>
</table>

<h2 class="rm-h">Cartera y operación</h2>
<table class="rm-table rm-kpis">
    <tr>
        <td><span>Cartera pendiente (a hoy)</span><b>{{ $m($ca['saldo_total']) }}</b></td>
        <td><span>Clientes con saldo</span><b>{{ $ca['clientes_con_saldo'] }}</b></td>
        <td><span>Asignaciones diarias</span><b>{{ $op['asignaciones'] }}</b></td>
        <td><span>Reintegros (asignados / recuperados)</span><b>{{ $op['reintegros_asignados'] }} / {{ $op['reintegros_recuperados'] }}</b></td>
        <td><span>Garantías (reportadas / resueltas)</span><b>{{ $op['garantias_reportadas'] }} / {{ $op['garantias_resueltas'] }}</b></td>
    </tr>
</table>

<h2 class="rm-h">Flujo estimado del mes</h2>
<table class="rm-table">
    <tbody>
        <tr><td>Entradas: cobros + efectivo al vender (contado y primas)</td><td class="n">{{ $m($f['entradas']) }}</td></tr>
        <tr><td>Salidas: gastos aprobados + pagos a proveedores + comisiones</td><td class="n">{{ $m($f['salidas']) }}</td></tr>
        <tr class="total"><td>Flujo estimado</td><td class="n">{{ $m($f['neto']) }}</td></tr>
    </tbody>
</table>
<p class="rm-nota">Estimado: no es contabilidad. Las comisiones son estimadas (vendedores por tramo, cobradores al {{ \App\Services\ResumenMensualService::COMISION_COBRADOR_PCT }}% de lo cobrado) y no incluye sueldos fijos ni otros costos. La cartera y la deuda con proveedores son a la fecha de hoy.</p>
