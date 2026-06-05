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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            color: #2c3e50;
            background: white;
            line-height: 1.6;
        }

        .page {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 30px;
        }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            border-bottom: 3px solid #f97316;
            padding-bottom: 20px;
        }

        .company-info h1 {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .company-info p {
            color: #6b7280;
            font-size: 11px;
            margin: 2px 0;
        }

        .document-title {
            text-align: right;
        }

        .document-title h2 {
            font-size: 16px;
            color: #f97316;
            margin-bottom: 8px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .document-title .date {
            font-size: 12px;
            color: #6b7280;
        }

        /* RESUMEN */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 35px;
        }

        .card {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #f97316;
        }

        .card.orange {
            background: linear-gradient(135deg, #fff5e6 0%, #ffe4b5 100%);
            border-left-color: #f97316;
        }

        .card.green {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            border-left-color: #10b981;
        }

        .card.blue {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left-color: #3b82f6;
        }

        .card-label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .card-value {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
        }

        .card.orange .card-value {
            color: #f97316;
        }

        .card.green .card-value {
            color: #10b981;
        }

        .card.blue .card-value {
            color: #3b82f6;
        }

        /* INFO BOX */
        .info-section {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #e5e7eb;
        }

        .info-section h3 {
            font-size: 13px;
            color: #1f2937;
            margin-bottom: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .info-item {
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-item label {
            font-size: 11px;
            color: #6b7280;
            display: block;
            font-weight: 600;
        }

        .info-item value {
            font-size: 14px;
            color: #1f2937;
            display: block;
            font-weight: 700;
            margin-top: 3px;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: white;
        }

        table thead {
            background: linear-gradient(90deg, #2c3e50 0%, #34495e 100%);
            color: white;
        }

        table th {
            padding: 14px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 12px;
        }

        table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        table tbody tr:hover {
            background: #f0f4f8;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .product-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 3px;
        }

        .product-code {
            font-size: 10px;
            color: #9ca3af;
        }

        .quantity {
            font-weight: 600;
            color: #374151;
        }

        .quantity.sold {
            color: #10b981;
            font-weight: 700;
        }

        .quantity.returned {
            color: #f97316;
            font-weight: 700;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-sold {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-partial {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-available {
            background: #fee2e2;
            color: #991b1b;
        }

        /* TOTALS */
        .totals-section {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 13px;
        }

        .total-row .label {
            opacity: 0.9;
        }

        .total-row .value {
            font-weight: 700;
        }

        .grand-total {
            display: flex;
            justify-content: space-between;
            padding-top: 12px;
            border-top: 2px solid rgba(255, 255, 255, 0.3);
            font-size: 16px;
            font-weight: 700;
        }

        /* NOTES */
        .notes {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 30px;
        }

        .notes h4 {
            font-size: 12px;
            color: #92400e;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .notes p {
            font-size: 12px;
            color: #78350f;
            line-height: 1.5;
        }

        /* FOOTER */
        .footer {
            border-top: 2px solid #e5e7eb;
            padding-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
        }

        .footer p {
            margin: 3px 0;
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
            <div class="company-info">
                <h1>DISTRIBUIDORA</h1>
                <p><strong>Corte de Inventario - Jornada Liquidada</strong></p>
                <p>Sistema de Gestión de Asignaciones Diarias</p>
            </div>
            <div class="document-title">
                <h2>Corte #{{ $asignacion->id }}</h2>
                <div class="date">{{ $asignacion->liquidada_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <!-- SUMMARY CARDS -->
        <div class="summary-cards">
            <div class="card">
                <div class="card-label">Vendedor</div>
                <div class="card-value">{{ substr($asignacion->vendedor->nombre, 0, 1) }}</div>
                <p style="font-size: 11px; color: #6b7280; margin-top: 5px;">{{ $asignacion->vendedor->nombre_completo }}</p>
            </div>
            <div class="card orange">
                <div class="card-label">Total Asignado</div>
                <div class="card-value">{{ $asignacion->detalles->sum('cantidad_asignada') }}</div>
                <p style="font-size: 10px; color: #92400e; margin-top: 3px;">unidades</p>
            </div>
            <div class="card green">
                <div class="card-label">Total Vendido</div>
                <div class="card-value">{{ $asignacion->detalles->sum('cantidad_vendida') }}</div>
                <p style="font-size: 10px; color: #065f46; margin-top: 3px;">unidades</p>
            </div>
            <div class="card blue">
                <div class="card-label">Devuelto</div>
                <div class="card-value">{{ $asignacion->detalles->sum('cantidad_devuelta') }}</div>
                <p style="font-size: 10px; color: #0c4a6e; margin-top: 3px;">unidades</p>
            </div>
        </div>

        <!-- INFO SECTION -->
        <div class="info-section">
            <h3>Información de la Jornada</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>Vendedor</label>
                    <value>{{ $asignacion->vendedor->nombre_completo }}</value>
                </div>
                <div class="info-item">
                    <label>Sucursal</label>
                    <value>{{ $asignacion->sucursal->nombre }}</value>
                </div>
                <div class="info-item">
                    <label>Fecha de Jornada</label>
                    <value>{{ $asignacion->fecha->format('d/m/Y') }}</value>
                </div>
                <div class="info-item">
                    <label>Productos</label>
                    <value>{{ $asignacion->detalles->count() }}</value>
                </div>
                <div class="info-item">
                    <label>Estado</label>
                    <value style="color: #10b981;">✓ Liquidada</value>
                </div>
                <div class="info-item">
                    <label>Liquidado el</label>
                    <value>{{ $asignacion->liquidada_at->format('d/m/Y H:i') }}</value>
                </div>
            </div>
        </div>

        <!-- TABLA DETALLADA -->
        <table>
            <thead>
                <tr>
                    <th style="width: 28%;">Producto</th>
                    <th style="width: 8%; text-align: center;">Asignado</th>
                    <th style="width: 8%; text-align: center;">Vendido</th>
                    <th style="width: 8%; text-align: center;">Disponible</th>
                    <th style="width: 8%; text-align: center;">Devuelto</th>
                    <th style="width: 12%; text-align: right;">P. Unitario</th>
                    <th style="width: 14%; text-align: right;">Total Venta</th>
                    <th style="width: 14%; text-align: center;">Estado</th>
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
                            <div class="product-name">{{ $detalle->producto->nombre }}</div>
                            <div class="product-code">{{ $detalle->producto->codigo }}</div>
                        </td>
                        <td class="text-center"><span class="quantity">{{ $detalle->cantidad_asignada }}</span></td>
                        <td class="text-center"><span class="quantity sold">{{ $detalle->cantidad_vendida }}</span></td>
                        <td class="text-center"><span class="quantity">{{ $disponible }}</span></td>
                        <td class="text-center"><span class="quantity returned">{{ $detalle->cantidad_devuelta }}</span></td>
                        <td class="text-right">${{ number_format($detalle->precio_venta, 2) }}</td>
                        <td class="text-right"><strong>${{ number_format($totalVendido, 2) }}</strong></td>
                        <td class="text-center">
                            @if($estado === 'Vendido')
                                <span class="badge badge-sold">✓ Vendido</span>
                            @elseif($estado === 'Parcial')
                                <span class="badge badge-partial">⊘ Parcial</span>
                            @else
                                <span class="badge badge-available">✗ Disponible</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 30px;">
                            No hay detalles en esta asignación
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- TOTALS -->
        <div class="totals-section">
            <div class="total-row">
                <span class="label">Total Productos Asignados</span>
                <span class="value">{{ $asignacion->detalles->sum('cantidad_asignada') }} unidades</span>
            </div>
            <div class="total-row">
                <span class="label">Total Vendido</span>
                <span class="value">{{ $asignacion->detalles->sum('cantidad_vendida') }} unidades</span>
            </div>
            <div class="total-row">
                <span class="label">Total Disponible/Devuelto</span>
                <span class="value">{{ $asignacion->detalles->sum('cantidad_devuelta') }} unidades</span>
            </div>
            <div class="grand-total">
                <span>INGRESO TOTAL DEL VENDEDOR</span>
                <span>${{ number_format($asignacion->total_vendido, 2) }}</span>
            </div>
        </div>

        <!-- NOTES -->
        @if($asignacion->observaciones)
            <div class="notes">
                <h4>📝 Observaciones</h4>
                <p>{{ $asignacion->observaciones }}</p>
            </div>
        @endif

        <!-- FOOTER -->
        <div class="footer">
            <p>Reporte generado el {{ now()->format('d/m/Y H:i:s') }} por Sistema de Gestión de Inventario</p>
            <p>Este documento constituye un comprobante oficial de la jornada liquidada</p>
        </div>
    </div>
</body>
</html>
