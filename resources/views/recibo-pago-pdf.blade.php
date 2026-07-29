<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo {{ $numeroRecibo }}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Courier New', 'Courier', monospace; font-size:11px; color:#111; }
        .page { padding:12px 10px; }

        .center { text-align:center; }
        .bold { font-weight:bold; }

        .titulo-negocio { font-size:14px; font-weight:bold; letter-spacing:.5px; }
        .telefono { font-size:10px; margin-top:1px; }

        .encabezado-recibo { display:flex; justify-content:space-between; font-size:10px; margin-top:6px; }

        .divisor { border-top:1px dashed #333; margin:6px 0; }

        table.datos { width:100%; border-collapse:collapse; }
        table.datos td { padding:1px 0; font-size:10.5px; }
        table.datos td.label { color:#111; white-space:nowrap; width:80px; }
        table.datos td.valor { text-align:right; }

        .cliente-nombre { font-size:11px; font-weight:bold; }
        .cliente-codigo { font-size:10px; }
        .producto { font-size:10.5px; margin-top:2px; }

        .pie { margin-top:10px; font-size:10px; text-align:center; }
    </style>
</head>
<body>
<div class="page">

    <div class="center">
        <div class="titulo-negocio">{{ strtoupper($config->app_name ?? 'SIDB') }}</div>
        @if($config->telefono)
            <div class="telefono">{{ $config->telefono }}</div>
        @endif
    </div>

    <div class="encabezado-recibo">
        <span>{{ $numeroRecibo }}</span>
        <span>{{ $fecha?->format('d/m/Y') }}</span>
    </div>

    <div class="divisor"></div>

    <div class="cliente-nombre">{{ $cliente?->nombre_completo }}{{ $cliente?->codigo_anterior ? ' ('.$cliente->codigo_anterior.')' : '' }}</div>
    @if($productos->isNotEmpty())
        <div class="producto">{{ $productos->implode(', ') }}</div>
    @endif

    <table class="datos" style="margin-top:6px;">
        <tr>
            <td class="label">Venta:</td>
            <td class="valor">{{ $venta?->numero_venta }}</td>
        </tr>
        <tr>
            <td class="label">Cobrador:</td>
            <td class="valor">{{ $nombreCobrador ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Pago:</td>
            <td class="valor">{{ ucfirst($metodoPago) }}</td>
        </tr>
    </table>

    <div class="divisor"></div>

    <table class="datos">
        <tr>
            <td class="label">Debía:</td>
            <td class="valor">${{ number_format($debia, 2) }}</td>
        </tr>
        <tr>
            <td class="label bold">Abona:</td>
            <td class="valor bold">${{ number_format($abona, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Resta:</td>
            <td class="valor">${{ number_format($resta, 2) }}</td>
        </tr>
    </table>

    <div class="divisor"></div>

    @if($proximaVisita)
        <div class="center">Próx. visita: {{ $proximaVisita->format('d/m/Y') }}</div>
    @endif
    <div class="pie">Gracias por su pago</div>

</div>
</body>
</html>
