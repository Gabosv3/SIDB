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
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }

        .page {
            padding: 30px 25px;
            max-width: 850px;
            margin: 0 auto;
        }

        /* HEADER */
        .header {
            margin-bottom: 25px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .header-left p {
            font-size: 10px;
            color: #666;
        }

        .header-right {
            text-align: right;
            font-size: 10px;
        }

        .header-right .title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* INFO SECTION */
        .info-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
            font-size: 10px;
        }

        .info-item {
            border: 1px solid #ddd;
            padding: 8px 12px;
        }

        .info-label {
            font-weight: bold;
            color: #666;
            margin-bottom: 3px;
            font-size: 9px;
            text-transform: uppercase;
        }

        .info-value {
            font-weight: bold;
            color: #000;
            font-size: 11px;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            font-size: 10px;
        }

        table thead {
            background: #f0f0f0;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }

        table th {
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            border-bottom: 1px solid #000;
        }

        table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }

        table tbody tr:last-child td {
            border-bottom: 2px solid #000;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .product-name {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .product-code {
            font-size: 9px;
            color: #999;
        }

        /* TOTALS */
        .totals {
            margin: 25px 0;
            border: 1px solid #000;
            padding: 15px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .total-row.main {
            border-top: 1px solid #000;
            border-bottom: 2px solid #000;
            padding-top: 8px;
            padding-bottom: 8px;
            margin-bottom: 0;
            font-weight: bold;
            font-size: 12px;
        }

        /* NOTES */
        .notes {
            margin: 20px 0;
            padding: 12px;
            border: 1px solid #ddd;
            font-size: 10px;
        }

        .notes h4 {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 11px;
        }

        /* FOOTER */
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
            text-align: center;
        }

        /* SIGNATURES */
        .signatures {
            margin-top: 40px;
            display: flex;
            justify-content: space-around;
        }

        .signature {
            width: 150px;
            text-align: center;
            font-size: 9px;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 30px;
            padding-top: 5px;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #999;
        }

        @media print {
            body {
                background: white;
            }
            .page {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- HEADER -->
        <div class="header">
            <div class="header-left">
                <h1>CORTE DE INVENTARIO</h1>
                <p>Jornada Liquidada - {{ $asignacion->sucursal->nombre }}</p>
            </div>
            <div class="header-right">
                <div class="title">Corte #{{ $asignacion->id }}</div>
                <div>{{ $asignacion->liquidada_at->format('d/m/Y') }}</div>
                <div>{{ $asignacion->liquidada_at->format('H:i') }}</div>
            </div>
        </div>

        <!-- INFO SECTION -->
        <div class="info-row">
            <div class="info-item">
                <div class="info-label">Vendedor</div>
                <div class="info-value">{{ $asignacion->vendedor->nombre_completo }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Sucursal</div>
                <div class="info-value">{{ $asignacion->sucursal->nombre }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Fecha Jornada</div>
                <div class="info-value">{{ $asignacion->fecha->format('d/m/Y') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Productos</div>
                <div class="info-value">{{ $asignacion->detalles->count() }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Estado</div>
                <div class="info-value">LIQUIDADA</div>
            </div>
            <div class="info-item">
                <div class="info-label">Liquidado</div>
                <div class="info-value">{{ $asignacion->liquidada_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <!-- TABLE -->
        @if($asignacion->detalles->isEmpty())
            <div class="no-data">
                No hay detalles en esta asignación
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th style="width: 28%;">Producto</th>
                        <th style="width: 7%; text-align: center;">Asignado</th>
                        <th style="width: 7%; text-align: center;">Vendido</th>
                        <th style="width: 7%; text-align: center;">Disponible</th>
                        <th style="width: 7%; text-align: center;">Devuelto</th>
                        <th style="width: 9%; text-align: right;">P. Asignado</th>
                        <th style="width: 9%; text-align: right;">P. Venta</th>
                        <th style="width: 11%; text-align: right;">Total Venta</th>
                        <th style="width: 14%; text-align: center;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($asignacion->detalles as $detalle)
                        @php
                            $disponible = $detalle->cantidad_asignada - $detalle->cantidad_vendida;
                            $estado = $detalle->cantidad_vendida == $detalle->cantidad_asignada ? 'VENDIDO' :
                                     ($detalle->cantidad_vendida > 0 ? 'PARCIAL' : 'DISPONIBLE');
                            $totalVendido = $detalle->cantidad_vendida * $detalle->precio_venta;
                        @endphp
                        <tr>
                            <td>
                                <div class="product-name">{{ $detalle->producto->nombre }}</div>
                                <div class="product-code">{{ $detalle->producto->codigo }}</div>
                            </td>
                            <td class="text-center">{{ $detalle->cantidad_asignada }}</td>
                            <td class="text-center"><strong>{{ $detalle->cantidad_vendida }}</strong></td>
                            <td class="text-center">{{ $disponible }}</td>
                            <td class="text-center"><strong>{{ $detalle->cantidad_devuelta }}</strong></td>
                            <td class="text-right">${{ number_format($detalle->precio_venta, 2) }}</td>
                            <td class="text-right">
                                @php
                                    $precioVenta = $detalle->cantidad_vendida > 0 ? $totalVendido / $detalle->cantidad_vendida : $detalle->precio_venta;
                                @endphp
                                <strong>${{ number_format($precioVenta, 2) }}</strong>
                                @if(abs($precioVenta - $detalle->precio_venta) > 0.01)
                                    <div style="font-size: 8px; color: #f97316;">
                                        @if($precioVenta > $detalle->precio_venta)
                                            (+${{ number_format($precioVenta - $detalle->precio_venta, 2) }})
                                        @else
                                            (-${{ number_format($detalle->precio_venta - $precioVenta, 2) }})
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="text-right"><strong>${{ number_format($totalVendido, 2) }}</strong></td>
                            <td class="text-center">{{ $estado }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- TOTALS -->
        <div class="totals">
            <div class="total-row">
                <span>Total Asignado:</span>
                <span><strong>{{ $asignacion->detalles->sum('cantidad_asignada') }} unidades</strong></span>
            </div>
            <div class="total-row">
                <span>Total Vendido:</span>
                <span><strong>{{ $asignacion->detalles->sum('cantidad_vendida') }} unidades</strong></span>
            </div>
            <div class="total-row">
                <span>Total Devuelto:</span>
                <span><strong>{{ $asignacion->detalles->sum('cantidad_devuelta') }} unidades</strong></span>
            </div>
            <div class="total-row main">
                <span>INGRESO TOTAL:</span>
                <span>${{ number_format($asignacion->total_vendido, 2) }}</span>
            </div>
        </div>

        <!-- NOTES -->
        @if($asignacion->observaciones)
            <div class="notes">
                <h4>Observaciones:</h4>
                <p>{{ $asignacion->observaciones }}</p>
            </div>
        @endif

        <!-- SIGNATURES -->
        <div class="signatures">
            <div class="signature">
                <div class="signature-line">Vendedor</div>
            </div>
            <div class="signature">
                <div class="signature-line">Supervisor</div>
            </div>
        </div>

        <!-- NOTES -->
        <div style="font-size: 9px; color: #666; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
            <p><strong>Nota sobre Precios:</strong></p>
            <p>• P. Asignado: Precio base asignado al inicio de la jornada</p>
            <p>• P. Venta: Precio promedio real de venta (puede variar por promociones, pagos a crédito, etc.)</p>
            <p>• El incremento/descuento se muestra en naranja en la columna P. Venta</p>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            <p>Reporte generado: {{ now()->format('d/m/Y H:i:s') }}</p>
            <p>Este documento es válido como constancia oficial de la jornada liquidada</p>
        </div>
    </div>
</body>
</html>
