<div wire:ignore style="width: 100%;">
    <style>
        #map-{{ $ruta->id }} {
            height: 500px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <div class="mb-4">
        <h3 class="text-lg font-semibold">Mapa de la Ruta: {{ $ruta->nombre }}</h3>
        <p class="text-gray-600">{{ $ruta->clientes_count ?? $clientes->count() }} clientes asignados</p>
    </div>

    <div id="map-{{ $ruta->id }}"></div>

    <script>
        (function() {
            const mapId = 'map-{{ $ruta->id }}';
            const mapElement = document.getElementById(mapId);
            
            if (!mapElement) return;

            const initMap = () => {
                if (typeof L === 'undefined') {
                    setTimeout(initMap, 100);
                    return;
                }

                const map = L.map(mapId).setView([13.7942, -88.8965], 8);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19,
                }).addTo(map);

                const clientes = @json($clientes);

                let markers = [];
                let clientesConCoordenadas = 0;

                clientes.forEach(function(cliente) {
                    if (cliente.latitud && cliente.longitud) {
                        clientesConCoordenadas++;

                        const marker = L.marker([cliente.latitud, cliente.longitud], {
                            title: cliente.nombre_completo
                        }).addTo(map);

                        const popupContent = `
                            <div style="min-width: 200px;">
                                <strong>${cliente.nombre_completo}</strong><br>
                                <small class="text-gray-600">
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

                if (clientesConCoordenadas === 0 && clientes.length > 0) {
                    console.warn('Sin coordenadas');
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initMap);
            } else {
                initMap();
            }
        })();
    </script>
</div>
