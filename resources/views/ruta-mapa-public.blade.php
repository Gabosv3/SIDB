<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de Ruta — {{ $record->nombre }}</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function () {
            if (localStorage.getItem('sidb-theme') === 'dark') document.documentElement.classList.add('dark');
        })();
    </script>
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }

        :root {
            --bg:#f3f4f6; --card:#fff; --subtle:#f9fafb; --border:#e5e7eb; --border-2:#f3f4f6;
            --text:#1a1a2e; --text-2:#374151; --muted:#6b7280; --muted-2:#9ca3af;
        }
        html.dark {
            --bg:#15151a; --card:#1e1e24; --subtle:#252530; --border:#2e2e3a; --border-2:#2a2a35;
            --text:#f1f5f9; --text-2:#cbd5e1; --muted:#94a3b8; --muted-2:#64748b;
        }
        html, body { min-height:100vh; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); font-size:14px; }

        /* ── Topbar ── */
        .rm-topbar {
            height:60px; background:var(--card); border-bottom:1px solid var(--border);
            display:flex; align-items:center; justify-content:space-between;
            padding:0 1.5rem; gap:1rem; position:sticky; top:0; z-index:200;
            box-shadow:0 1px 4px rgba(0,0,0,.06);
        }
        html.dark .rm-topbar { box-shadow:none; }
        .rm-topbar-left { display:flex; align-items:center; gap:.9rem; min-width:0; }
        .rm-back { display:inline-flex; align-items:center; gap:.4rem; color:var(--muted); background:none; border:none; cursor:pointer; font-size:.85rem; font-weight:600; padding:.4rem .6rem; border-radius:.5rem; }
        .rm-back:hover { background:var(--subtle); color:var(--text-2); }
        .rm-title { font-size:.92rem; font-weight:700; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

        .theme-toggle { width:36px; height:36px; border-radius:50%; border:1px solid var(--border); background:none; cursor:pointer; display:flex; align-items:center; justify-content:center; color:var(--muted); flex-shrink:0; }
        .theme-toggle:hover { background:var(--subtle); }
        .theme-toggle svg { width:18px; height:18px; }
        .theme-toggle .icon-sun { display:none; }
        html.dark .theme-toggle .icon-moon { display:none; }
        html.dark .theme-toggle .icon-sun { display:block; }

        /* ── Body ── */
        .rm-body { padding:1.5rem; max-width:1200px; margin:0 auto; }

        .rm-card { background:var(--card); border:1px solid var(--border); border-radius:.875rem; box-shadow:0 1px 3px rgba(0,0,0,.05); margin-bottom:1.25rem; overflow:hidden; }
        html.dark .rm-card { box-shadow:none; }
        .rm-card-header { padding:1rem 1.25rem; border-bottom:1px solid var(--border-2); display:flex; align-items:center; gap:.5rem; }
        .rm-card-header h3 { font-size:.85rem; font-weight:700; color:var(--text); }
        .rm-card-body { padding:1.25rem; }

        .rm-header-top h1 { font-size:1.375rem; font-weight:700; color:var(--text); margin-bottom:.2rem; }
        .rm-header-top p { font-size:.85rem; color:var(--muted); }

        .rm-info-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1rem; margin-top:1.25rem; }
        .rm-info-card { display:flex; align-items:center; gap:.75rem; padding:1rem; background:var(--subtle); border-radius:.75rem; border:1px solid var(--border); }
        .rm-info-icon { width:40px; height:40px; border-radius:.6rem; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .rm-info-label { font-size:.65rem; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; }
        .rm-info-value { font-size:1rem; font-weight:700; color:var(--text); margin-top:.1rem; }

        #map { height:480px; width:100%; }
        .leaflet-container { background:var(--subtle); }
        html.dark .leaflet-popup-content-wrapper, html.dark .leaflet-popup-tip { background:var(--card); color:var(--text); }
        html.dark .leaflet-bar a { background:var(--card); border-color:var(--border); color:var(--text); }

        table { width:100%; border-collapse:collapse; }
        thead th { padding:.6rem 1rem; text-align:left; font-size:.65rem; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; background:var(--subtle); border-bottom:1px solid var(--border); white-space:nowrap; }
        tbody td { padding:.7rem 1rem; border-bottom:1px solid var(--border-2); font-size:.82rem; color:var(--text-2); }
        tbody tr:hover { background:var(--subtle); }
        .rm-cliente-email { color:var(--muted-2); font-size:.72rem; margin-top:.1rem; }

        .rm-badge { display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .65rem; border-radius:9999px; font-size:.7rem; font-weight:700; }
        .rm-dot { width:6px; height:6px; border-radius:50%; flex-shrink:0; }
        .rm-badge-green { background:#dcfce7; color:#166534; }
        .rm-badge-gray  { background:var(--border); color:var(--muted); }
        html.dark .rm-badge-green { background:rgba(34,197,94,.18); color:#86efac; }

        .rm-empty { text-align:center; color:var(--muted-2); padding:2.5rem; font-size:.85rem; }

        ::-webkit-scrollbar { width:5px; }
        ::-webkit-scrollbar-track { background:transparent; }
        ::-webkit-scrollbar-thumb { background:var(--border); border-radius:3px; }
    </style>
</head>
<body>

<div class="rm-topbar">
    <div class="rm-topbar-left">
        <button type="button" class="rm-back" onclick="history.back()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            Volver
        </button>
        <span class="rm-title">{{ $record->nombre }}</span>
    </div>
    <button type="button" class="theme-toggle" onclick="toggleTheme()" title="Cambiar modo claro/oscuro">
        <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
    </button>
</div>

<div class="rm-body">

    <div class="rm-card">
        <div class="rm-card-body">
            <div class="rm-header-top">
                <h1>{{ $record->nombre }}</h1>
                <p>{{ $record->descripcion ?: 'Sin descripción registrada.' }}</p>
            </div>

            <div class="rm-info-grid">
                <div class="rm-info-card">
                    <div class="rm-info-icon" style="background:#dbeafe;color:#1d4ed8;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div>
                        <div class="rm-info-label">Cobrador</div>
                        <div class="rm-info-value">{{ $record->cobrador?->nombre_completo ?? '—' }}</div>
                    </div>
                </div>
                <div class="rm-info-card">
                    <div class="rm-info-icon" style="background:#dcfce7;color:#15803d;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div>
                        <div class="rm-info-label">Total de clientes</div>
                        <div class="rm-info-value">{{ $record->clientes->count() }}</div>
                    </div>
                </div>
                <div class="rm-info-card">
                    <div class="rm-info-icon" style="background:#fef9c3;color:#a16207;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div>
                        <div class="rm-info-label">Con coordenadas GPS</div>
                        <div class="rm-info-value">{{ $record->clientes->filter(fn ($c) => $c->latitud && $c->longitud)->count() }}</div>
                    </div>
                </div>
                <div class="rm-info-card">
                    <div class="rm-info-icon" style="background:{{ $record->activa ? '#dcfce7' : 'var(--border)' }};color:{{ $record->activa ? '#15803d' : 'var(--muted)' }};">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
                    </div>
                    <div>
                        <div class="rm-info-label">Estado</div>
                        <div class="rm-info-value">{{ $record->activa ? 'Activa' : 'Inactiva' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="rm-card">
        <div class="rm-card-header"><h3>Mapa de la ruta</h3></div>
        <div id="map"></div>
    </div>

    <div class="rm-card">
        <div class="rm-card-header"><h3>Clientes en esta ruta</h3></div>
        <div style="overflow-x:auto;">
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
                                <strong style="color:var(--text);">{{ $cliente->nombre_completo }}</strong>
                                @if($cliente->email)
                                    <div class="rm-cliente-email">{{ $cliente->email }}</div>
                                @endif
                            </td>
                            <td>{{ $cliente->telefono_normal ?? '—' }}</td>
                            <td>{{ $cliente->municipio ?? '—' }}</td>
                            <td>
                                @if ($cliente->latitud && $cliente->longitud)
                                    <span class="rm-badge rm-badge-green"><span class="rm-dot" style="background:#16a34a;"></span>Sí</span>
                                @else
                                    <span class="rm-badge rm-badge-gray"><span class="rm-dot" style="background:var(--muted-2);"></span>No</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="rm-empty">No hay clientes en esta ruta.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@php
    $clientesParaMapa = $record->clientes->map(function ($c) {
        return [
            'nombre_completo' => $c->nombre_completo,
            'direccion' => $c->direccion,
            'municipio' => $c->municipio,
            'departamento' => $c->departamento,
            'telefono_normal' => $c->telefono_normal,
            'email' => $c->email,
            'latitud' => $c->latitud,
            'longitud' => $c->longitud,
        ];
    });
@endphp

<script>
    function toggleTheme() {
        var isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('sidb-theme', isDark ? 'dark' : 'light');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const map = L.map('map').setView([13.7942, -88.8965], 8);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);

        const clientes = @json($clientesParaMapa);

        let markers = [];
        clientes.forEach(function (cliente) {
            if (cliente.latitud && cliente.longitud) {
                const marker = L.marker([cliente.latitud, cliente.longitud]).addTo(map);

                const popupContent =
                    '<div style="font-weight:700; margin-bottom:4px;">' + (cliente.nombre_completo || 'Sin nombre') + '</div>' +
                    '<div style="font-size:12px; line-height:1.5;">' +
                        (cliente.direccion || 'Sin dirección') + '<br>' +
                        [cliente.municipio, cliente.departamento].filter(Boolean).join(' - ') + '<br>' +
                        '📱 ' + (cliente.telefono_normal || 'Sin teléfono') + '<br>' +
                        '📧 ' + (cliente.email || 'Sin email') +
                    '</div>';

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
