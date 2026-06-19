@extends('pos._layout')

@section('page-title', 'Monitoreo de POS')

@php
    $activos     = $devices->filter(fn($d) => $d->estado_calc === 'activo')->count();
    $sinConexion = $devices->filter(fn($d) => $d->estado_calc === 'sin_conexion')->count();
    $bateriaBaja = $devices->filter(fn($d) => $d->estado_calc === 'bateria_baja')->count();
    $apagados    = $devices->filter(fn($d) => $d->estado_calc === 'apagado')->count();
    $total       = $devices->count();

    $alertas      = $devices->filter(fn($d) => in_array($d->estado_calc, ['sin_conexion','bateria_baja','apagado']));
    $alertasCount = $alertas->count();

    $pctVentas = $ventasAyer > 0 ? round((($ventasHoy - $ventasAyer) / $ventasAyer) * 100, 1) : 0;
    $pctCobros = $cobrosAyer > 0 ? round((($cobrosDia - $cobrosAyer) / $cobrosAyer) * 100, 1) : 0;

    $devicesCords = $devices->filter(fn($d) => $d->latitud && $d->longitud);
    $mapCenter    = $devicesCords->isNotEmpty()
        ? [$devicesCords->avg('latitud'), $devicesCords->avg('longitud')]
        : [13.7942, -88.8965];
@endphp

@section('styles')
<style>
    .pm-stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.25rem; }
    .pm-main-grid  { display:grid; grid-template-columns:1fr 360px; gap:1.25rem; align-items:start; }
    .pm-left-col   { display:flex; flex-direction:column; gap:1.25rem; }
    .pm-right-col  { display:flex; flex-direction:column; gap:1.25rem; }
    #pm-map        { height:300px; width:100%; }
    .leaflet-container { border-radius:0 0 .875rem .875rem; }
    .pm-chart-body { padding:.75rem 1rem 1rem; }
    .pm-metrics-row { display:grid; grid-template-columns:1fr 1fr; }
    .pm-metric + .pm-metric { border-left:1px solid #f3f4f6; }
    .pm-spark      { width:100%; height:40px; display:block; }
    .pm-empty      { padding:2.5rem; text-align:center; color:#9ca3af; font-size:.82rem; }
    @media (max-width:1100px) {
        .pm-main-grid  { grid-template-columns:1fr; }
        .pm-stats-grid { grid-template-columns:repeat(2,1fr); }
    }
</style>
@endsection

@section('content')
<div class="pm-page-header">
    <h1>Monitoreo de POS</h1>
    <p>Seguimiento en tiempo real de todos los dispositivos punto de venta activos</p>
</div>

{{-- ── Stat cards ── --}}
<div class="pm-stats-grid">
    <div class="pm-card">
        <div class="pm-stat">
            <div class="pm-stat-icon" style="background:#dcfce7;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div>
                <div class="pm-stat-label">Dispositivos activos</div>
                <div class="pm-stat-num" id="pm-stat-activos">{{ $activos }}</div>
                <div class="pm-stat-sub">de {{ $total }} registrados</div>
            </div>
        </div>
    </div>
    <div class="pm-card">
        <div class="pm-stat">
            <div class="pm-stat-icon" style="background:#fee2e2;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </div>
            <div>
                <div class="pm-stat-label">Sin conexión</div>
                <div class="pm-stat-num" id="pm-stat-sin-conexion">{{ $sinConexion }}</div>
                <div class="pm-stat-sub">requieren atención</div>
            </div>
        </div>
    </div>
    <div class="pm-card">
        <div class="pm-stat">
            <div class="pm-stat-icon" style="background:#fef9c3;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ca8a04" stroke-width="2" stroke-linecap="round"><rect x="2" y="7" width="18" height="10" rx="1"/><line x1="22" y1="11" x2="22" y2="13"/></svg>
            </div>
            <div>
                <div class="pm-stat-label">Batería baja</div>
                <div class="pm-stat-num" id="pm-stat-bateria-baja">{{ $bateriaBaja }}</div>
                <div class="pm-stat-sub">por debajo del 20%</div>
            </div>
        </div>
    </div>
    <div class="pm-card">
        <div class="pm-stat">
            <div class="pm-stat-icon" style="background:#f3f4f6;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
            </div>
            <div>
                <div class="pm-stat-label">Total registrados</div>
                <div class="pm-stat-num" id="pm-stat-total">{{ $total }}</div>
                <div class="pm-stat-sub" id="pm-stat-total-sub">{{ $apagados }} apagados</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Main grid ── --}}
<div class="pm-main-grid">

    {{-- LEFT ─────────────────────────────────────────── --}}
    <div class="pm-left-col">

        {{-- Mapa --}}
        <div class="pm-card">
            <div class="pm-card-header">
                <span class="pm-card-title">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" style="display:inline;margin-right:.3rem;vertical-align:middle"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Mapa de ubicaciones
                </span>
                <span style="font-size:.68rem;color:#9ca3af;">{{ $devicesCords->count() }} con GPS activo</span>
            </div>
            <div id="pm-map"></div>
        </div>

        {{-- Tabla --}}
        <div class="pm-card">
            <div class="pm-card-header">
                <span class="pm-card-title">Dispositivos registrados</span>
                <span style="font-size:.68rem;color:#9ca3af;">Actualiza cada 30 s</span>
            </div>
            <div class="pm-table-wrap">
                @if($devices->isEmpty())
                    <div class="pm-empty">No hay dispositivos POS registrados aún.</div>
                @else
                <table class="pm-table">
                    <thead class="pm-thead">
                        <tr>
                            <th>POS</th>
                            <th>Usuario / Cobrador</th>
                            <th>Estado</th>
                            <th>Última conexión</th>
                            <th>Batería</th>
                            <th>Internet</th>
                            <th>Ubicación</th>
                        </tr>
                    </thead>
                    <tbody id="pm-devices-tbody">
                    @foreach($devices as $d)
                        @php
                            $estado = $d->estado_calc;
                            $badgeStyles = match($estado) {
                                'activo'       => 'background:#dcfce7;color:#166534;',
                                'bateria_baja' => 'background:#fef9c3;color:#854d0e;',
                                'sin_conexion' => 'background:#fee2e2;color:#991b1b;',
                                default        => 'background:#f3f4f6;color:#6b7280;',
                            };
                            $dotColor = match($estado) {
                                'activo'       => '#16a34a',
                                'bateria_baja' => '#d97706',
                                'sin_conexion' => '#dc2626',
                                default        => '#9ca3af',
                            };
                            $estadoLabel = match($estado) {
                                'activo'       => 'Activo',
                                'bateria_baja' => 'Batería baja',
                                'sin_conexion' => 'Sin conexión',
                                default        => 'Apagado',
                            };
                            $bat      = $d->bateria ?? 0;
                            $batColor = $bat > 50 ? '#16a34a' : ($bat > 20 ? '#d97706' : '#dc2626');
                        @endphp
                        <tr class="pm-tr" id="pm-row-{{ $d->id }}">
                            <td class="pm-td" style="font-weight:700;color:#111827;">
                                {{ $d->nombre }}
                                @if($d->serial)
                                <div style="font-size:.65rem;font-weight:400;color:#9ca3af;">{{ $d->serial }}</div>
                                @endif
                            </td>
                            <td class="pm-td">
                                <div style="font-weight:600;font-size:.8rem;color:#374151;">{{ optional($d->user)->name ?? '—' }}</div>
                                @if($d->cobrador)
                                <div style="font-size:.68rem;color:#9ca3af;">{{ $d->cobrador->nombre }} {{ $d->cobrador->apellido }}</div>
                                @endif
                            </td>
                            <td class="pm-td">
                                <span class="pm-badge" style="{{ $badgeStyles }}">
                                    <span class="pm-dot" style="background:{{ $dotColor }};"></span>
                                    {{ $estadoLabel }}
                                </span>
                            </td>
                            <td class="pm-td" style="color:#6b7280;font-size:.75rem;">
                                {{ $d->ultimo_ping ? $d->ultimo_ping->diffForHumans() : 'Nunca' }}
                            </td>
                            <td class="pm-td">
                                <div class="pm-bat-wrap">
                                    <div class="pm-bat-bar">
                                        <div class="pm-bat-fill" style="width:{{ $bat }}%;background:{{ $batColor }};"></div>
                                    </div>
                                    <span class="pm-bat-pct">{{ $bat }}%</span>
                                </div>
                            </td>
                            <td class="pm-td" style="text-align:center;">
                                @if($d->tiene_internet)
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>
                                @else
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/><path d="M10.71 5.05A16 16 0 0 1 22.56 9"/><path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>
                                @endif
                            </td>
                            <td class="pm-td">
                                @if($d->latitud && $d->longitud)
                                    <a href="https://www.google.com/maps?q={{ $d->latitud }},{{ $d->longitud }}" target="_blank" class="pm-loc">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        Ver mapa
                                    </a>
                                @else
                                    <span style="font-size:.72rem;color:#d1d5db;">Sin GPS</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>

    {{-- RIGHT ────────────────────────────────────────── --}}
    <div class="pm-right-col">

        {{-- Alertas --}}
        <div class="pm-card">
            <div class="pm-card-header">
                <span class="pm-card-title">Alertas activas</span>
                <span id="pm-alertas-badge">
                @if($alertasCount > 0)
                    <span class="pm-badge" style="background:#fee2e2;color:#991b1b;">{{ $alertasCount }}</span>
                @else
                    <span class="pm-badge" style="background:#dcfce7;color:#166534;">Todo OK</span>
                @endif
                </span>
            </div>
            <div id="pm-alertas-panel">
            @if($alertas->isEmpty())
                <div class="pm-empty">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="1.8" stroke-linecap="round" style="display:block;margin:0 auto .5rem"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Sin alertas activas
                </div>
            @else
                @foreach($alertas->sortByDesc('ultimo_ping') as $a)
                @php
                    $aEstado = $a->estado_calc;
                    [$aBg, $aClr] = match($aEstado) {
                        'sin_conexion' => ['#fee2e2', '#dc2626'],
                        'bateria_baja' => ['#fef9c3', '#d97706'],
                        default        => ['#f3f4f6', '#9ca3af'],
                    };
                    $aMsg = match($aEstado) {
                        'sin_conexion' => 'Sin conexión a internet',
                        'bateria_baja' => 'Batería al ' . ($a->bateria ?? 0) . '%',
                        default        => 'Dispositivo apagado',
                    };
                @endphp
                <div class="pm-alert-item">
                    <div class="pm-alert-icon" style="background:{{ $aBg }};">
                        @if($aEstado === 'sin_conexion')
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="{{ $aClr }}" stroke-width="2.5" stroke-linecap="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        @elseif($aEstado === 'bateria_baja')
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="{{ $aClr }}" stroke-width="2.5" stroke-linecap="round"><rect x="2" y="7" width="18" height="10" rx="1"/><line x1="22" y1="11" x2="22" y2="13"/></svg>
                        @else
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="{{ $aClr }}" stroke-width="2.5" stroke-linecap="round"><rect x="5" y="2" width="14" height="20" rx="2"/></svg>
                        @endif
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="pm-alert-name">{{ $a->nombre }}</div>
                        <div class="pm-alert-desc">{{ $aMsg }}</div>
                    </div>
                    <span class="pm-alert-time">{{ $a->ultimo_ping ? $a->ultimo_ping->diffForHumans(['short'=>true]) : '—' }}</span>
                </div>
                @endforeach
            @endif
            </div>
        </div>

        {{-- Conexiones por hora --}}
        <div class="pm-card">
            <div class="pm-card-header">
                <span class="pm-card-title">Conexiones por hora</span>
                <span style="font-size:.65rem;color:#9ca3af;">Hoy</span>
            </div>
            <div class="pm-chart-body">
                <canvas id="chartConexiones" height="130"></canvas>
            </div>
        </div>

        {{-- Ventas y cobros --}}
        <div class="pm-card">
            <div class="pm-card-header">
                <span class="pm-card-title">Ventas y cobros del día</span>
            </div>
            <div class="pm-metrics-row">
                <div class="pm-metric">
                    <div class="pm-metric-label">Ventas hoy</div>
                    <div class="pm-metric-num" id="pm-ventas-num">${{ number_format($ventasHoy, 2) }}</div>
                    <div id="pm-ventas-delta">
                    @if($pctVentas !== 0)
                    <div class="pm-metric-delta {{ $pctVentas >= 0 ? 'pm-delta-up' : 'pm-delta-down' }}">
                        {{ $pctVentas >= 0 ? '▲' : '▼' }} {{ abs($pctVentas) }}% vs ayer
                    </div>
                    @else
                    <div class="pm-metric-delta" style="color:#9ca3af;">Sin cambio vs ayer</div>
                    @endif
                    </div>
                    <canvas id="sparkVentas" class="pm-spark" style="margin-top:.5rem;"></canvas>
                </div>
                <div class="pm-metric">
                    <div class="pm-metric-label">Cobros hoy</div>
                    <div class="pm-metric-num" id="pm-cobros-num">{{ $cobrosDia }}</div>
                    <div id="pm-cobros-delta">
                    @if($pctCobros !== 0)
                    <div class="pm-metric-delta {{ $pctCobros >= 0 ? 'pm-delta-up' : 'pm-delta-down' }}">
                        {{ $pctCobros >= 0 ? '▲' : '▼' }} {{ abs($pctCobros) }}% vs ayer
                    </div>
                    @else
                    <div class="pm-metric-delta" style="color:#9ca3af;">Sin cambio vs ayer</div>
                    @endif
                    </div>
                    <canvas id="sparkCobros" class="pm-spark" style="margin-top:.5rem;"></canvas>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
var DATA_URL = '{{ route('pos.monitor.data', $tenant) }}';
var POLL_MS  = 10000; // 10 segundos

// ── Estado de badges / colores ─────────────────────────────────────────────
var ESTADO = {
    activo:       { label:'Activo',       bg:'#dcfce7', color:'#166534', dot:'#16a34a' },
    bateria_baja: { label:'Batería baja', bg:'#fef9c3', color:'#854d0e', dot:'#d97706' },
    sin_conexion: { label:'Sin conexión', bg:'#fee2e2', color:'#991b1b', dot:'#dc2626' },
    apagado:      { label:'Apagado',      bg:'#f3f4f6', color:'#6b7280', dot:'#9ca3af' },
};

// ── Mapa Leaflet (se inicializa una vez) ───────────────────────────────────
var map = L.map('pm-map', { zoomControl: true }).setView(
    [{{ $mapCenter[0] }}, {{ $mapCenter[1] }}], 13
);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://openstreetmap.org">OSM</a>',
    maxZoom: 18
}).addTo(map);

var mapMarkers = {};

function refreshMap(devices) {
    var seen = {};
    var bounds = [];
    devices.forEach(function (d) {
        if (!d.latitud || !d.longitud) return;
        seen[d.id] = true;
        var color = ESTADO[d.estado] ? ESTADO[d.estado].dot : '#9ca3af';
        var popup = '<b style="font-size:13px;">' + d.nombre + '</b><br>' +
            (d.cobrador || d.usuario || '') + '<br>' +
            '<span style="color:' + color + ';font-weight:700;">' + d.ultimo_ping + '</span>';
        if (mapMarkers[d.id]) {
            mapMarkers[d.id].setLatLng([d.latitud, d.longitud]);
            mapMarkers[d.id].setStyle({ fillColor: color });
            mapMarkers[d.id].setPopupContent(popup);
        } else {
            mapMarkers[d.id] = L.circleMarker([d.latitud, d.longitud], {
                radius: 8, color: '#fff', weight: 2,
                fillColor: color, fillOpacity: 0.9
            }).addTo(map).bindPopup(popup);
        }
        bounds.push([d.latitud, d.longitud]);
    });
    // Quitar markers de dispositivos eliminados
    Object.keys(mapMarkers).forEach(function (id) {
        if (!seen[id]) { map.removeLayer(mapMarkers[id]); delete mapMarkers[id]; }
    });
    if (bounds.length > 1 && Object.keys(mapMarkers).length === bounds.length) {
        // Solo ajusta la primera vez que se cargan markers
    }
}

// Carga inicial del mapa con datos del servidor
(function initMap() {
    var bounds = [];
    @foreach($devicesCords as $d)
    @php $mc = match($d->estado_calc) { 'activo'=>'#16a34a','bateria_baja'=>'#d97706','sin_conexion'=>'#dc2626',default=>'#9ca3af' }; @endphp
    mapMarkers[{{ $d->id }}] = L.circleMarker([{{ $d->latitud }}, {{ $d->longitud }}], {
        radius:8, color:'#fff', weight:2, fillColor:'{{ $mc }}', fillOpacity:0.9
    }).addTo(map).bindPopup('<b>{{ $d->nombre }}</b><br>{{ $d->cobrador ? $d->cobrador->nombre." ".$d->cobrador->apellido : optional($d->user)->name ?? "" }}');
    bounds.push([{{ $d->latitud }}, {{ $d->longitud }}]);
    @endforeach
    if (bounds.length > 1)        map.fitBounds(bounds, { padding: [30, 30] });
    else if (bounds.length === 1) map.setView(bounds[0], 14);
}());

// ── Chart.js ── conexiones por hora ───────────────────────────────────────
var chartConex = new Chart(document.getElementById('chartConexiones'), {
    type: 'line',
    data: {
        labels: Array.from({length:24}, function(_,i){ return i+'h'; }),
        datasets: [{
            data: @json(array_values($conexionesPorHora)),
            borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.08)',
            borderWidth: 2, pointRadius: 2, pointHoverRadius: 5, fill: true, tension: 0.4
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize:1, font:{size:10} }, grid: { color:'#f3f4f6' } },
            x: { ticks: { font:{size:9}, maxTicksLimit:12 }, grid: { display: false } }
        },
        animation: { duration: 300 }
    }
});

// ── Sparklines ─────────────────────────────────────────────────────────────
function mkSpark(id, color) {
    return new Chart(document.getElementById(id), {
        type: 'line',
        data: {
            labels: ['L','M','X','J','V','S','D'],
            datasets: [{ data:[3,5,4,6,4,7,5], borderColor:color, borderWidth:1.5, pointRadius:0, fill:false, tension:0.4 }]
        },
        options: {
            plugins: { legend:{ display:false } },
            scales: { x:{ display:false }, y:{ display:false } },
            animation: false
        }
    });
}
mkSpark('sparkVentas','#10b981');
mkSpark('sparkCobros','#6366f1');

// ── Helpers DOM ────────────────────────────────────────────────────────────
function setText(id, val) {
    var el = document.getElementById(id);
    if (el) el.textContent = val;
}
function setHtml(id, val) {
    var el = document.getElementById(id);
    if (el) el.innerHTML = val;
}

function deltaHtml(pct, prefix) {
    if (pct === 0) return '<div class="pm-metric-delta" style="color:#9ca3af;">Sin cambio vs ayer</div>';
    var arrow = pct > 0 ? '▲' : '▼';
    var cls   = pct > 0 ? 'pm-delta-up' : 'pm-delta-down';
    return '<div class="pm-metric-delta ' + cls + '">' + arrow + ' ' + Math.abs(pct) + '% vs ayer</div>';
}

function batHtml(bat, color) {
    return '<div class="pm-bat-wrap">' +
        '<div class="pm-bat-bar"><div class="pm-bat-fill" style="width:' + bat + '%;background:' + color + ';"></div></div>' +
        '<span class="pm-bat-pct">' + bat + '%</span></div>';
}

function wifiIcon(ok) {
    if (ok) return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>';
    return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>';
}

// ── Actualizar tabla de dispositivos ──────────────────────────────────────
function refreshTable(devices) {
    var tbody = document.getElementById('pm-devices-tbody');
    if (!tbody) return;
    if (!devices.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="pm-empty">No hay dispositivos POS registrados.</td></tr>';
        return;
    }
    devices.forEach(function (d) {
        var row = document.getElementById('pm-row-' + d.id);
        var st  = ESTADO[d.estado] || ESTADO['apagado'];
        var locHtml = d.latitud
            ? '<a href="https://www.google.com/maps?q=' + d.latitud + ',' + d.longitud + '" target="_blank" class="pm-loc">' +
              '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> Ver mapa</a>'
            : '<span style="font-size:.72rem;color:#d1d5db;">Sin GPS</span>';

        if (!row) {
            var tr = document.createElement('tr');
            tr.id = 'pm-row-' + d.id;
            tr.className = 'pm-tr';
            tbody.appendChild(tr);
            row = tr;
        }
        row.innerHTML =
            '<td class="pm-td" style="font-weight:700;color:#111827;">' + d.nombre +
                (d.serial ? '<div style="font-size:.65rem;font-weight:400;color:#9ca3af;">' + d.serial + '</div>' : '') +
            '</td>' +
            '<td class="pm-td">' +
                '<div style="font-weight:600;font-size:.8rem;color:#374151;">' + (d.usuario || '—') + '</div>' +
                (d.cobrador ? '<div style="font-size:.68rem;color:#9ca3af;">' + d.cobrador + '</div>' : '') +
            '</td>' +
            '<td class="pm-td"><span class="pm-badge" style="background:' + st.bg + ';color:' + st.color + ';">' +
                '<span class="pm-dot" style="background:' + st.dot + ';"></span>' + st.label + '</span></td>' +
            '<td class="pm-td" style="color:#6b7280;font-size:.75rem;">' + d.ultimo_ping + '</td>' +
            '<td class="pm-td">' + batHtml(d.bateria, d.bateria_color) + '</td>' +
            '<td class="pm-td" style="text-align:center;">' + wifiIcon(d.tiene_internet) + '</td>' +
            '<td class="pm-td">' + locHtml + '</td>';
    });
}

// ── Actualizar panel de alertas ────────────────────────────────────────────
function refreshAlertas(devices, count) {
    var badge = document.getElementById('pm-alertas-badge');
    var panel = document.getElementById('pm-alertas-panel');
    var bellBadge = document.getElementById('pm-bell-badge');

    if (badge) {
        if (count > 0) {
            badge.innerHTML = '<span class="pm-badge" style="background:#fee2e2;color:#991b1b;">' + count + '</span>';
        } else {
            badge.innerHTML = '<span class="pm-badge" style="background:#dcfce7;color:#166534;">Todo OK</span>';
        }
    }
    if (bellBadge) {
        bellBadge.textContent = count;
        bellBadge.style.display = count > 0 ? '' : 'none';
    }

    if (!panel) return;
    var alertDevices = devices.filter(function (d) {
        return d.estado === 'sin_conexion' || d.estado === 'bateria_baja' || d.estado === 'apagado';
    });

    if (!alertDevices.length) {
        panel.innerHTML = '<div class="pm-empty">' +
            '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="1.8" stroke-linecap="round" style="display:block;margin:0 auto .5rem"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' +
            'Sin alertas activas</div>';
        return;
    }
    var ICONS = {
        sin_conexion: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
        bateria_baja: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round"><rect x="2" y="7" width="18" height="10" rx="1"/><line x1="22" y1="11" x2="22" y2="13"/></svg>',
        apagado:      '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round"><rect x="5" y="2" width="14" height="20" rx="2"/></svg>',
    };
    var MSG = { sin_conexion:'Sin conexión a internet', apagado:'Dispositivo apagado' };

    panel.innerHTML = alertDevices.map(function (a) {
        var st   = ESTADO[a.estado] || ESTADO['apagado'];
        var msg  = a.estado === 'bateria_baja' ? 'Batería al ' + a.bateria + '%' : (MSG[a.estado] || a.estado);
        var icon = (ICONS[a.estado] || ICONS['apagado']).replace('stroke-width', 'stroke="' + st.dot + '" stroke-width');
        return '<div class="pm-alert-item">' +
            '<div class="pm-alert-icon" style="background:' + st.bg + ';">' + icon + '</div>' +
            '<div style="flex:1;min-width:0;"><div class="pm-alert-name">' + a.nombre + '</div>' +
            '<div class="pm-alert-desc">' + msg + '</div></div>' +
            '<span class="pm-alert-time">' + a.ultimo_ping + '</span></div>';
    }).join('');
}

// ── Actualizar stat cards ──────────────────────────────────────────────────
function refreshStats(s) {
    setText('pm-stat-activos', s.activos);
    setText('pm-stat-sin-conexion', s.sin_conexion);
    setText('pm-stat-bateria-baja', s.bateria_baja);
    setText('pm-stat-total', s.total);
    document.getElementById('pm-stat-total-sub') && (document.getElementById('pm-stat-total-sub').textContent = s.apagados + ' apagados');

    setText('pm-ventas-num', '$' + s.ventas_hoy.toLocaleString('es-SV', {minimumFractionDigits:2, maximumFractionDigits:2}));
    setText('pm-cobros-num', s.cobros_dia);
    setHtml('pm-ventas-delta', deltaHtml(s.pct_ventas));
    setHtml('pm-cobros-delta', deltaHtml(s.pct_cobros));
}

// ── Polling principal ──────────────────────────────────────────────────────
var liveBadge = document.getElementById('pm-live-badge');

function poll() {
    fetch(DATA_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function (json) {
            refreshStats(json.stats);
            refreshTable(json.devices);
            refreshAlertas(json.devices, json.alertas_count);
            refreshMap(json.devices);

            // Actualizar chart conexiones
            chartConex.data.datasets[0].data = json.conexiones_por_hora;
            chartConex.update();

            // Badge "En vivo" en verde
            if (liveBadge) {
                liveBadge.className = 'pm-live';
                liveBadge.innerHTML = '<span class="pm-live-dot"></span> En vivo · ' + json.timestamp;
            }
        })
        .catch(function () {
            if (liveBadge) {
                liveBadge.className = 'pm-live error';
                liveBadge.innerHTML = '<span class="pm-live-dot"></span> Sin conexión';
            }
        });
}

// Primera actualización a los 2s (para no esperar 10s completos), luego cada POLL_MS
setTimeout(poll, 2000);
setInterval(poll, POLL_MS);
</script>
@endsection
