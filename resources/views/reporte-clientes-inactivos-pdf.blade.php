<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes Inactivos</title>
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
            border-bottom: 3px solid #dc2626;
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

        .nunca { color: #dc2626; font-weight: bold; }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Clientes sin visita ni abono</h1>
            <p>Al menos {{ $dias }} días sin visita y sin abono — generado el {{ $fecha->format('d/m/Y') }}</p>
            <p>Total: {{ $filas->count() }} clientes</p>
        </div>

        @if($filas->isEmpty())
            <div class="no-data">No hay clientes que cumplan este criterio.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Ruta</th>
                        <th>Cobrador</th>
                        <th>Saldo</th>
                        <th>Última visita</th>
                        <th>Último abono</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($filas as $fila)
                        @php $cliente = $fila['cliente']; @endphp
                        <tr>
                            <td>{{ $cliente->nombre_completo }}</td>
                            <td>{{ $cliente->telefono_whatsapp ?: $cliente->telefono_normal ?: '—' }}</td>
                            <td>{{ $cliente->rutaCobro?->nombre ?? '—' }}</td>
                            <td>{{ $cliente->rutaCobro?->cobrador ? $cliente->rutaCobro->cobrador->nombre.' '.$cliente->rutaCobro->cobrador->apellido : '—' }}</td>
                            <td>${{ number_format($cliente->saldo, 2) }}</td>
                            <td>
                                @if($fila['ultima_visita'])
                                    {{ $fila['ultima_visita']->format('d/m/Y') }} (hace {{ $fila['dias_sin_visita'] }} días)
                                @else
                                    <span class="nunca">Nunca visitado</span>
                                @endif
                            </td>
                            <td>
                                @if($fila['ultimo_pago'])
                                    {{ $fila['ultimo_pago']->format('d/m/Y') }} (hace {{ $fila['dias_sin_pago'] }} días)
                                @else
                                    <span class="nunca">Nunca ha abonado</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</body>
</html>
