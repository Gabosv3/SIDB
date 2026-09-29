<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes Seleccionados</title>
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
            border-bottom: 3px solid #16a34a;
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

        .sin-dato { color: #dc2626; font-weight: bold; }

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
            <h1>Listado de Clientes</h1>
            <p>Generado el {{ $fecha->format('d/m/Y') }}</p>
            <p>Total: {{ $clientes->count() }} clientes</p>
        </div>

        @if($clientes->isEmpty())
            <div class="no-data">No se seleccionó ningún cliente.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Dirección</th>
                        <th>Ruta</th>
                        <th>Producto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clientes as $cliente)
                        <tr>
                            <td>
                                @if($cliente->codigo_anterior)
                                    {{ $cliente->codigo_anterior }}
                                @else
                                    <span class="sin-dato">Sin código</span>
                                @endif
                            </td>
                            <td>{{ $cliente->nombre_completo }}</td>
                            <td>{{ $cliente->telefono_whatsapp ?: $cliente->telefono_normal ?: '—' }}</td>
                            <td>{{ $cliente->direccion ?: '—' }}</td>
                            <td>
                                @if($cliente->rutaCobro)
                                    {{ $cliente->rutaCobro->nombre }}
                                @else
                                    <span class="sin-dato">Sin ruta</span>
                                @endif
                            </td>
                            <td>{{ $productosPorCliente[$cliente->id] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</body>
</html>
