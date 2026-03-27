<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de Ruta - {{ $record->nombre }}</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f3f4f6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .header h1 {
            font-size: 24px;
            font-weight: bold;
            color: #111;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .info-card {
            padding: 15px;
            background: #f9fafb;
            border-radius: 6px;
            border-left: 4px solid #3b82f6;
        }
        .info-card .label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .info-card .value {
            font-size: 16px;
            font-weight: bold;
            color: #111;
            margin-top: 5px;
        }
        .map-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .map-section h3 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #111;
        }
        #map {
            height: 500px;
            border-radius: 6px;
        }
        .table-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .table-section h3 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #111;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: #f9fafb;
        }
        th {
            padding: 12px;
            text-align: left;
            font-size: 12px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            border-bottom: 1px solid #e5e7eb;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        tbody tr:hover {
            background: #f9fafb;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-green {
            background: #dcfce7;
            color: #166534;
        }
        .badge-gray {
            background: #f3f4f6;
            color: #6b7280;
        }
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 16px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
        }
        .back-btn:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="back-btn">← Volver</a>

        <div class="header">
            <h1>{{ $record->nombre }}</h1>
            <p>{{ $record->descripcion }}</p>
            
            <div class="info-grid">
                <div class="info-card">
                    <div class="label">Cobrador</div>
                    <div class="value">{{ $record->cobrador->nombre_completo }}</div>
                </div>
                <div class="info-card">
                    <div class="label">Total de Clientes</div>
                    <div class="value">{{ $record->clientes->count() }}</div>
                </div>
                <div class="info-card">
                    <div class="label">Estado</div>
                    <div class="value">
                        @if ($record->activa)
                            <span class="badge badge-green">Activa</span>
                        @else
                            <span class="badge badge-gray">Inactiva</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="map-section">
            <h3>Mapa de la Ruta</h3>
            <div id="map"></div>
        </div>

        <div class="table-section">
            <h3>Clientes en esta ruta</h3>
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Municipio</th>
                        <th>Coordenadas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($record->clientes as $cliente)
                        <tr>
                            <td>
                                <strong>{{ $cliente->nombre_completo }}</strong>
                                <div style="color: #999; font-size: 12px;">{{ $cliente->email }}</div>
                            </td>
                            <td>{{ $cliente->telefono_normal ?? '—' }}</td>
                            <td>{{ $cliente->municipio ?? '—' }}</td>
                            <td>
                                @if ($cliente->latitud && $cliente->longitud)
                                    <span class="badge badge-green">✓ Sí</span>
                                @else
                                    <span class="badge badge-gray">— No</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; color: #999;">No hay clientes en esta ruta</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const map = L.map('map').setView([13.7942, -88.8965], 8);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(map);

            const clientes = @json($record->clientes);

            let markers = [];
            clientes.forEach(function(cliente) {
                if (cliente.latitud && cliente.longitud) {
                    const marker = L.marker([cliente.latitud, cliente.longitud]).addTo(map);
                    
                    const popupContent = `
                        <div style="font-weight: bold; margin-bottom: 5px;">${cliente.nombre_completo}</div>
                        <div style="font-size: 12px;">
                            ${cliente.direccion || 'Sin dirección'}<br>
                            ${cliente.municipio || ''} - ${cliente.departamento || ''}<br>
                            📱 ${cliente.telefono_normal || 'Sin teléfono'}<br>
                            📧 ${cliente.email || 'Sin email'}
                        </div>
                    `;
                    
                    marker.bindPopup(popupContent);
                    markers.push(marker);
                }
            });

            if (markers.length > 0) {
                const group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
        });
    </script>
</body>
</html>
