<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Diario</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.3;
        }

        .container {
            padding: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #f97316;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 20px;
            margin-bottom: 5px;
            color: #1f2937;
        }

        .header p {
            font-size: 11px;
            color: #6b7280;
        }

        .info-section {
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
        }

        .info-item {
            background: #f9fafb;
            padding: 8px 10px;
            border-left: 3px solid #f97316;
            border-radius: 2px;
        }

        .info-label {
            font-size: 9px;
            color: #6b7280;
            font-weight: bold;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 12px;
            font-weight: bold;
            color: #1f2937;
        }

        .vendedor-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
            border: 1px solid #e5e7eb;
            border-radius: 3px;
            padding: 10px;
        }

        .vendedor-header {
            background: #374151;
            color: white;
            padding: 8px;
            margin: -10px -10px 10px -10px;
            border-radius: 2px 2px 0 0;
        }

        .vendedor-header h3 {
            font-size: 11px;
            margin-bottom: 3px;
        }

        .vendedor-info {
            font-size: 9px;
            color: #ccc;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table thead {
            background: #e5e7eb;
        }

        table th {
            padding: 5px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            border-bottom: 2px solid #9ca3af;
        }

        table td {
            padding: 5px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
        }

        table tr:nth-child(even) {
            background: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        .summary-box {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            padding: 8px;
            margin-top: 10px;
            border-radius: 2px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 9px;
        }

        .summary-row .label {
            font-weight: bold;
        }

        .summary-row .value {
            text-align: right;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 2px;
            font-size: 8px;
            font-weight: bold;
        }

        .status-activa {
            background: #dbeafe;
            color: #0c4a6e;
        }

        .status-liquidada {
            background: #dcfce7;
            color: #166534;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #9ca3af;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>REPORTE DIARIO DE ASIGNACIONES</h1>
            <p>Fecha: {{ $fecha->format('d/m/Y') }}</p>
        </div>

        <!-- INFO SECTION -->
        <div class="info-section">
            <div class="info-item">
                <div class="info-label">Total de Asignaciones</div>
                <div class="info-value">{{ $asignaciones->count() }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Asignaciones Liquidadas</div>
                <div class="info-value">{{ $asignaciones->where('estado', 'liquidada')->count() }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Asignaciones Activas</div>
                <div class="info-value">{{ $asignaciones->where('estado', 'activa')->count() }}</div>
            </div>
        </div>

        @if($asignaciones->isEmpty())
            <div class="no-data">
                No hay asignaciones para el día de hoy
            </div>
        @else
            <!-- ASIGNACIONES POR VENDEDOR -->
            @foreach($asignaciones->groupBy('vendedor_id') as $vendedorAsignaciones)
                @php
                    $vendedor = $vendedorAsignaciones->first()->vendedor;
                    $totalVendido = $vendedorAsignaciones->sum('total_vendido');
                    $totalDevuelto = $vendedorAsignaciones->sum('total_devuelto_valor');
                @endphp

                <div class="vendedor-section">
                    <div class="vendedor-header">
                        <h3>{{ $vendedor->nombre_completo }}</h3>
                        <div class="vendedor-info">
                            {{ $vendedor->codigo ?? 'S/C' }} - Sucursal: {{ $vendedorAsignaciones->first()->sucursal->nombre }}
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th style="width: 25%;">Producto</th>
                                <th style="width: 8%; text-align: center;">Asignado</th>
                                <th style="width: 8%; text-align: center;">Vendido</th>
                                <th style="width: 8%; text-align: center;">Devuelto</th>
                                <th style="width: 10%; text-align: right;">Precio Unit.</th>
                                <th style="width: 13%; text-align: right;">Total Vendido</th>
                                <th style="width: 12%; text-align: center;">Estado</th>
                                <th style="width: 8%; text-align: center;">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vendedorAsignaciones as $asignacion)
                                @foreach($asignacion->detalles as $detalle)
                                    <tr>
                                        <td>
                                            <strong>{{ $detalle->producto->nombre }}</strong><br>
                                            <small>{{ $detalle->producto->codigo }}</small>
                                        </td>
                                        <td class="text-right">{{ $detalle->cantidad_asignada }}</td>
                                        <td class="text-right">{{ $detalle->cantidad_vendida }}</td>
                                        <td class="text-right">{{ $detalle->cantidad_devuelta }}</td>
                                        <td class="text-right">${{ number_format($detalle->precio_venta, 2) }}</td>
                                        <td class="text-right"><strong>${{ number_format($detalle->cantidad_vendida * $detalle->precio_venta, 2) }}</strong></td>
                                        <td class="text-right">
                                            <span class="status-badge status-{{ $asignacion->estado }}">
                                                {{ strtoupper($asignacion->estado) }}
                                            </span>
                                        </td>
                                        <td class="text-right">{{ $asignacion->fecha->format('d/m') }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>

                    <div class="summary-box">
                        <div class="summary-row">
                            <span class="label">Total Vendido:</span>
                            <span class="value">${{ number_format($totalVendido, 2) }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="label">Total Devuelto:</span>
                            <span class="value">${{ number_format($totalDevuelto, 2) }}</span>
                        </div>
                        <div class="summary-row" style="border-top: 1px solid #fcd34d; padding-top: 4px; margin-top: 4px;">
                            <span class="label" style="font-size: 10px;">Ingreso Neto:</span>
                            <span class="value" style="font-size: 11px; color: #059669;">
                                ${{ number_format($totalVendido - $totalDevuelto, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif

        <!-- FOOTER -->
        <div class="footer">
            <p>Reporte generado el {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
