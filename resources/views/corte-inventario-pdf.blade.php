<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Corte de Inventario</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }

        .container {
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #f97316;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
            color: #1f2937;
        }

        .header p {
            font-size: 12px;
            color: #6b7280;
        }

        .info-section {
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .info-item {
            background: #f9fafb;
            padding: 10px 15px;
            border-radius: 3px;
            border-left: 3px solid #f97316;
        }

        .info-label {
            font-size: 10px;
            color: #6b7280;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 13px;
            font-weight: bold;
            color: #1f2937;
        }

        .summary-box {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 3px;
        }

        .summary-box p {
            margin-bottom: 5px;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table thead {
            background: #374151;
            color: white;
        }

        table th {
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            border-right: 1px solid #555;
        }

        table th:last-child {
            border-right: none;
        }

        table td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }

        table tr:nth-child(even) {
            background: #f9fafb;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .status-vendido {
            background: #dcfce7;
            color: #166534;
            padding: 2px 6px;
            border-radius: 2px;
            font-size: 9px;
        }

        .status-parcial {
            background: #fef3c7;
            color: #92400e;
            padding: 2px 6px;
            border-radius: 2px;
            font-size: 9px;
        }

        .status-disponible {
            background: #fee2e2;
            color: #991b1b;
            padding: 2px 6px;
            border-radius: 2px;
            font-size: 9px;
        }

        .totals {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #1f2937;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .total-row .label {
            font-weight: bold;
        }

        .total-row .value {
            font-weight: bold;
            text-align: right;
        }

        .grand-total {
            background: #f97316;
            color: white;
            padding: 10px;
            border-radius: 3px;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            font-weight: bold;
            margin-top: 10px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>CORTE DE INVENTARIO - JORNADA LIQUIDADA</h1>
            <p>Resumen final de asignación diaria</p>
        </div>

        <!-- INFO SECTION -->
        <div class="info-section">
            <div class="info-item">
                <div class="info-label">Vendedor</div>
                <div class="info-value">{{ $asignacion->vendedor->nombre_completo }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Sucursal</div>
                <div class="info-value">{{ $asignacion->sucursal->nombre }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Fecha de Jornada</div>
                <div class="info-value">{{ $asignacion->fecha->format('d/m/Y') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Fecha de Liquidación</div>
                <div class="info-value">{{ $asignacion->liquidada_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <!-- SUMMARY BOX -->
        <div class="summary-box">
            <p><strong>Resumen del Corte:</strong></p>
            <p>✓ Total de productos: <strong>{{ $asignacion->detalles->count() }}</strong></p>
            <p>✓ Total asignado: <strong>{{ $asignacion->detalles->sum('cantidad_asignada') }} unidades</strong></p>
            <p>✓ Total vendido: <strong>{{ $asignacion->detalles->sum('cantidad_vendida') }} unidades</strong></p>
            <p>✓ Total devuelto: <strong>{{ $asignacion->detalles->sum('cantidad_devuelta') }} unidades</strong></p>
        </div>

        <!-- TABLA DETALLADA -->
        <table>
            <thead>
                <tr>
                    <th style="width: 30%;">Producto</th>
                    <th style="width: 8%; text-align: right;">Asignado</th>
                    <th style="width: 8%; text-align: right;">Vendido</th>
                    <th style="width: 8%; text-align: right;">Disponible</th>
                    <th style="width: 8%; text-align: right;">Devuelto</th>
                    <th style="width: 12%; text-align: right;">Precio Unit.</th>
                    <th style="width: 12%; text-align: right;">Total Vendido</th>
                    <th style="width: 12%; text-align: center;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($asignacion->detalles as $detalle)
                    @php
                        $disponible = $detalle->cantidad_asignada - $detalle->cantidad_vendida;
                        $estado = $detalle->cantidad_vendida == $detalle->cantidad_asignada ? 'Vendido' :
                                 ($detalle->cantidad_vendida > 0 ? 'Parcial' : 'Disponible');
                        $totalVendido = $detalle->cantidad_vendida * $detalle->precio_venta;
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $detalle->producto->nombre }}</strong><br>
                            <small>{{ $detalle->producto->codigo }}</small>
                        </td>
                        <td class="text-right">{{ $detalle->cantidad_asignada }}</td>
                        <td class="text-right font-bold">{{ $detalle->cantidad_vendida }}</td>
                        <td class="text-right">{{ $disponible }}</td>
                        <td class="text-right font-bold">{{ $detalle->cantidad_devuelta }}</td>
                        <td class="text-right">${{ number_format($detalle->precio_venta, 2) }}</td>
                        <td class="text-right font-bold">${{ number_format($totalVendido, 2) }}</td>
                        <td class="text-center">
                            @if($estado === 'Vendido')
                                <span class="status-vendido">VENDIDO</span>
                            @elseif($estado === 'Parcial')
                                <span class="status-parcial">PARCIAL</span>
                            @else
                                <span class="status-disponible">DISPONIBLE</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">
                            No hay detalles en esta asignación
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- TOTALES -->
        <div class="totals">
            <div class="total-row">
                <span class="label">Total Vendido (Valor):</span>
                <span class="value">${{ number_format($asignacion->total_vendido, 2) }}</span>
            </div>
            <div class="total-row">
                <span class="label">Total Devuelto (Valor):</span>
                <span class="value">${{ number_format($asignacion->total_devuelto_valor, 2) }}</span>
            </div>
            <div class="grand-total">
                <span>INGRESO NETO DEL VENDEDOR</span>
                <span>${{ number_format($asignacion->total_vendido - ($asignacion->total_devuelto_valor ?? 0), 2) }}</span>
            </div>
        </div>

        <!-- OBSERVACIONES -->
        @if($asignacion->observaciones)
            <div style="margin-top: 20px; padding: 10px; background: #f3f4f6; border-left: 3px solid #f97316; border-radius: 3px;">
                <strong>Observaciones:</strong><br>
                <p style="margin-top: 5px; font-size: 10px;">{{ $asignacion->observaciones }}</p>
            </div>
        @endif

        <!-- FOOTER -->
        <div class="footer">
            <p>Corte generado el {{ now()->format('d/m/Y H:i:s') }}</p>
            <p>Este documento es válido como constancia de la jornada liquidada.</p>
        </div>
    </div>
</body>
</html>
