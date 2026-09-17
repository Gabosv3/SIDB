<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Garantías de la Semana</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.3;
        }

        .container { padding: 15px; }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #f97316;
            padding-bottom: 10px;
        }

        .header h1 { font-size: 20px; margin-bottom: 5px; color: #1f2937; }
        .header p { font-size: 11px; color: #6b7280; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table thead { background: #e5e7eb; }
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
            vertical-align: top;
        }
        table tr:nth-child(even) { background: #f9fafb; }

        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 2px;
            font-size: 8px;
            font-weight: bold;
        }
        .status-pendiente  { background: #fef3c7; color: #854d0e; }
        .status-en_proceso { background: #dbeafe; color: #0c4a6e; }
        .status-resuelta   { background: #dcfce7; color: #166534; }
        .status-rechazada  { background: #ffe4e6; color: #9f1239; }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #9ca3af;
            font-size: 11px;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>GARANTÍAS DE LA SEMANA</h1>
            <p>Del {{ $inicio->format('d/m/Y') }} al {{ $fin->format('d/m/Y') }}</p>
        </div>

        @if($garantias->isEmpty())
            <div class="no-data">No hay garantías reportadas en esta semana</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th style="width: 8%;">Fecha</th>
                        <th style="width: 18%;">Cliente</th>
                        <th style="width: 18%;">Producto</th>
                        <th style="width: 16%;">Motivo</th>
                        <th style="width: 14%;">Cobrador (recoge)</th>
                        <th style="width: 10%;">Estado</th>
                        <th style="width: 8%;">F. resolución</th>
                        <th style="width: 8%;">Venta</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($garantias as $g)
                        <tr>
                            <td>{{ $g->fecha_reporte->format('d/m/Y') }}</td>
                            <td>{{ $g->cliente?->nombre_completo ?? '—' }}</td>
                            <td>{{ $g->venta?->detalles->pluck('producto.nombre')->filter()->implode(', ') ?: '—' }}</td>
                            <td>{{ $g->motivo ?: '—' }}</td>
                            <td>{{ $g->cobrador ? "{$g->cobrador->nombre} {$g->cobrador->apellido}" : '—' }}</td>
                            <td>
                                <span class="status-badge status-{{ $g->estado }}">
                                    {{ strtoupper($g->estado) }}
                                </span>
                            </td>
                            <td>{{ $g->fecha_resolucion?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $g->venta?->numero_venta ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="footer">
            <p>Reporte generado el {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
