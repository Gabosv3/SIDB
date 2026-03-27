<x-filament-panels::page>
    <div class="space-y-6">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">{{ $record->nombre }}</h2>
                <p class="text-gray-600 mt-1">{{ $record->descripcion }}</p>
            </div>

            <!-- Información de la ruta -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="text-sm font-medium text-gray-600">Cobrador</div>
                    <div class="text-lg font-semibold text-gray-900">{{ $record->cobrador->nombre_completo }}</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="text-sm font-medium text-gray-600">Total de Clientes</div>
                    <div class="text-lg font-semibold text-gray-900">{{ $record->clientes_count ?? $record->clientes()->count() }}</div>
                </div>
                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="text-sm font-medium text-gray-600">Estado</div>
                    <div class="text-lg font-semibold text-gray-900">
                        @if ($record->activa)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Activa</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactiva</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Mapa -->
            <div class="mb-6 bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Mapa de la Ruta</h3>
                <div id="map-{{ $record->id }}" style="height: 500px; border-radius: 8px;"></div>
            </div>

            <!-- Tabla de clientes -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Clientes en esta ruta</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Teléfono</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Municipio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Coordenadas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($record->clientes as $cliente)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $cliente->nombre_completo }}</div>
                                        <div class="text-sm text-gray-500">{{ $cliente->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $cliente->telefono_normal ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $cliente->municipio ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        @if ($cliente->latitud && $cliente->longitud)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                ✓ Sí
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                — No
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                        No hay clientes en esta ruta
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const mapId = 'map-{{ $record->id }}';
                const mapElement = document.getElementById(mapId);
                
                if (!mapElement || typeof L === 'undefined') return;

                const map = L.map(mapId).setView([13.7942, -88.8965], 8);

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
                            <div style="min-width: 200px;">
                                <strong>${cliente.nombre_completo}</strong><br>
                                <small>
                                    ${cliente.direccion || 'Sin dirección'}<br>
                                    ${cliente.municipio || ''} - ${cliente.departamento || ''}<br>
                                    📱 ${cliente.telefono_normal || 'Sin teléfono'}<br>
                                    📧 ${cliente.email || 'Sin email'}
                                </small>
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
    </div>
</x-filament-panels::page>
