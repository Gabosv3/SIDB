<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corte de Inventario - Asignación Diaria</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f3f4f6;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 0.5rem;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            padding: 2rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .summary-item {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 0.5rem;
            border-left: 4px solid #f97316;
        }

        .summary-item label {
            display: block;
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .summary-item value {
            display: block;
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
        }

        .info-box {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            padding: 1.5rem;
            margin: 2rem;
            border-radius: 0.5rem;
            font-size: 14px;
            color: #065f46;
        }

        .info-box strong {
            display: block;
            margin-bottom: 0.5rem;
            color: #047857;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem;
        }

        table thead {
            background: #f3f4f6;
            border-bottom: 2px solid #e5e7eb;
        }

        table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            color: #374151;
            text-transform: uppercase;
        }

        table td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        table tr:hover {
            background: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .footer {
            padding: 2rem;
            background: #f9fafb;
            border-top: 2px solid #f97316;
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
            border-radius: 0 0 0.75rem 0.75rem;
        }

        .btn {
            padding: 0.875rem 1.75rem;
            border-radius: 0.5rem;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn-primary {
            background: #f97316;
            color: white;
            box-shadow: 0 2px 4px rgba(249, 115, 22, 0.2);
        }

        .btn-primary:hover {
            background: #ea580c;
            box-shadow: 0 4px 8px rgba(249, 115, 22, 0.3);
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary:hover {
            background: #d1d5db;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        .floating-buttons {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 9999;
        }

        .floating-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            font-size: 28px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: all 0.2s;
        }

        .floating-btn-pdf {
            background: #f97316;
            color: white;
        }

        .floating-btn-pdf:hover {
            background: #ea580c;
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(249, 115, 22, 0.3);
        }

        .floating-btn-print {
            background: #3b82f6;
            color: white;
        }

        .floating-btn-print:hover {
            background: #2563eb;
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.3);
        }

        @media print {
            body {
                background: white;
            }

            .footer {
                display: none;
            }

            .btn {
                display: none;
            }

            .floating-buttons {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- FLOATING BUTTONS -->
    <div class="floating-buttons">
        <button class="floating-btn floating-btn-pdf" onclick="descargarPdf()" title="Descargar PDF">📥</button>
        <button class="floating-btn floating-btn-print" onclick="window.print()" title="Imprimir">🖨️</button>
    </div>

    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>✓ Corte de Inventario - Jornada Liquidada</h1>
            <p>Resumen final de la asignación diaria</p>
        </div>

        <!-- RESUMEN EJECUTIVO -->
        <div class="summary">
            <div class="summary-item">
                <label>Vendedor</label>
                <value>{{ $asignacion->vendedor->nombre_completo }}</value>
            </div>
            <div class="summary-item">
                <label>Fecha</label>
                <value>{{ $asignacion->fecha->format('d/m/Y') }}</value>
            </div>
            <div class="summary-item">
                <label>Productos</label>
                <value>{{ $asignacion->detalles->count() }}</value>
            </div>
            <div class="summary-item">
                <label>Total Vendido</label>
                <value>${{ number_format($asignacion->total_vendido, 2) }}</value>
            </div>
        </div>

        <!-- INFO BOX -->
        <div class="info-box">
            <strong>Detalles del Corte:</strong>
            Total asignado: {{ $asignacion->detalles->sum('cantidad_asignada') }} unidades<br>
            Total vendido: {{ $asignacion->detalles->sum('cantidad_vendida') }} unidades<br>
            Total devuelto: {{ $asignacion->detalles->sum('cantidad_devuelta') }} unidades
        </div>

        <!-- TABLA DETALLADA -->
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="text-right">Asignado</th>
                    <th class="text-right">Vendido</th>
                    <th class="text-right">Disponible</th>
                    <th class="text-right">Devuelto</th>
                    <th class="text-right">Precio Unit.</th>
                    <th class="text-right">Total Vendido</th>
                    <th class="text-right">Estado</th>
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
                            <small style="color: #9ca3af;">{{ $detalle->producto->codigo }}</small>
                        </td>
                        <td class="text-right">{{ $detalle->cantidad_asignada }}</td>
                        <td class="text-right"><strong style="color: #059669;">{{ $detalle->cantidad_vendida }}</strong></td>
                        <td class="text-right">{{ $disponible }}</td>
                        <td class="text-right"><strong style="color: #f97316;">{{ $detalle->cantidad_devuelta }}</strong></td>
                        <td class="text-right">${{ number_format($detalle->precio_venta, 2) }}</td>
                        <td class="text-right"><strong>${{ number_format($totalVendido, 2) }}</strong></td>
                        <td class="text-right">
                            @if($estado === 'Vendido')
                                <span class="badge badge-success">✓ Vendido</span>
                            @elseif($estado === 'Parcial')
                                <span class="badge badge-warning">⊘ Parcial</span>
                            @else
                                <span class="badge badge-danger">✗ Disponible</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: #9ca3af;">
                            No hay detalles en esta asignación
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- FOOTER -->
        <div class="footer">
            <button class="btn btn-primary" onclick="descargarPdf()">📥 Descargar PDF</button>
            <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimir Corte</button>
            <button class="btn btn-secondary" onclick="window.location.href='{{ route('filament.administrativo.resources.asignaciones-diarias.index', ['tenant' => auth()->user()->sucursales()->first()->id ?? 1]) }}'">← Volver a Asignaciones</button>
        </div>
    </div>

    <script>
        function descargarPdf() {
            window.location.href = '{{ route("asignacion-diaria.corte.pdf", ["tenant" => $tenant, "asignacion" => $asignacion->id]) }}';
        }
    </script>
</body>
</html>
