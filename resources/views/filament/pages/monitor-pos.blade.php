<x-filament-panels::page>
<div>
<style>
    /* ── Base ── */
    .mp-card { background:#fff; border:1px solid #e5e7eb; border-radius:.875rem; overflow:hidden; }
    .mp-section-title {
        display:flex; align-items:center; justify-content:space-between;
        padding:.7rem 1.1rem; font-size:.7rem; font-weight:700;
        text-transform:uppercase; letter-spacing:.06em; color:#6b7280;
        border-bottom:1px solid #e5e7eb;
    }

    /* ── Stat cards ── */
    .mp-stat { display:flex; align-items:center; gap:1rem; padding:1.25rem; }
    .mp-stat-icon {
        width:50px; height:50px; border-radius:.875rem; display:flex;
        align-items:center; justify-content:center; flex-shrink:0;
    }
    .mp-stat-label { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; margin-bottom:.15rem; }
    .mp-stat-num { font-size:2.1rem; font-weight:800; line-height:1; }
    .mp-stat-sub { font-size:.7rem; color:#9ca3af; margin-top:.2rem; }

    /* ── Table ── */
    .mp-table-wrap { overflow-x:auto; }
    .mp-table { width:100%; border-collapse:collapse; }
    .mp-thead th {
        padding:.6rem .9rem; font-size:.65rem; font-weight:700;
        text-transform:uppercase; letter-spacing:.05em; color:#6b7280;
        background:#f9fafb; border-bottom:1px solid #e5e7eb; white-space:nowrap;
    }
    .mp-thead th:first-child { padding-left:1.25rem; }
    .mp-thead th:last-child  { padding-right:1.25rem; }
    .mp-tr { border-bottom:1px solid #f3f4f6; transition:background .1s; }
    .mp-tr:hover { background:#f8fafc; }
    .mp-td { padding:.65rem .9rem; font-size:.82rem; color:#374151; white-space:nowrap; }
    .mp-td:first-child { padding-left:1.25rem; }
    .mp-td:last-child  { padding-right:1.25rem; }

    /* ── Status badge ── */
    .mp-badge {
        display:inline-flex; align-items:center; gap:.3rem;
        padding:.22rem .65rem; border-radius:9999px;
        font-size:.68rem; font-weight:700; letter-spacing:.02em;
    }
    .mp-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
    .mp-dot-pulse { animation:mp-pulse 2s infinite; }
    @keyframes mp-pulse {
        0%,100% { opacity:1; } 50% { opacity:.4; }
    }

    /* ── Battery bar ── */
    .mp-bat-wrap { display:inline-flex; align-items:center; gap:.4rem; }
    .mp-bat-bar  { width:52px; height:7px; background:#e5e7eb; border-radius:4px; overflow:hidden; }
    .mp-bat-fill { height:100%; border-radius:4px; transition:width .4s; }
    .mp-bat-pct  { font-size:.72rem; color:#6b7280; min-width:28px; }

    /* ── Alert list ── */
    .mp-alert-item {
        display:flex; align-items:center; gap:.7rem;
        padding:.65rem 1.1rem; border-bottom:1px solid #f3f4f6;
    }
    .mp-alert-icon {
        width:34px; height:34px; border-radius:.5rem;
        display:flex; align-items:center; justify-content:center; flex-shrink:0;
    }
    .mp-alert-name  { font-weight:600; font-size:.8rem; color:#111827; }
    .mp-alert-desc  { font-size:.7rem; color:#9ca3af; }
    .mp-alert-time  { font-size:.68rem; color:#9ca3af; white-space:nowrap; margin-left:auto; padding-left:.5rem; }

    /* ── Loc button ── */
    .mp-loc { display:inline-flex; align-items:center; gap:.25rem; font-size:.72rem; color:#6366f1; font-weight:600; text-decoration:none; }
    .mp-loc:hover { text-decoration:underline; }

    /* ── POS name ── */
    .mp-pos-name   { font-weight:700; font-size:.82rem; color:#111827; }
    .mp-pos-serial { font-size:.68rem; color:#9ca3af; margin-top:.1rem; }
    .mp-cobrador   { color:#374151; }

    /* ── Pulse indicator ── */
    .mp-live { display:inline-flex; align-items:center; gap:.35rem; font-size:.68rem; color:#34d399; font-weight:600; }
    .mp-live-dot { width:7px; height:7px; border-radius:50%; background:#34d399; animation:mp-pulse 1.8s infinite; }

    /* ═══ DARK MODE ═══ */
    .dark .mp-card          { background:#1e1e24; border-color:#2e2e3a; }
    .dark .mp-section-title { color:#475569; border-bottom-color:#2e2e3a; }
    .dark .mp-stat-label    { color:#475569; }
    .dark .mp-stat-sub      { color:#4b5563; }
    .dark .mp-thead th      { background:#252530; border-bottom-color:#2e2e3a; color:#475569; }
    .dark .mp-tr            { border-bottom-color:#2a2a35; }
    .dark .mp-tr:hover      { background:#252530; }
    .dark .mp-td            { color:#cbd5e1; }
    .dark .mp-bat-bar       { background:#2e2e3a; }
    .dark .mp-bat-pct       { color:#475569; }
    .dark .mp-alert-item    { border-bottom-color:#2a2a35; }
    .dark .mp-alert-name    { color:#f1f5f9; }
    .dark .mp-alert-desc    { color:#4b5563; }
    .dark .mp-alert-time    { color:#4b5563; }
    .dark .mp-pos-name      { color:#f1f5f9; }
    .dark .mp-pos-serial    { color:#3f3f50; }
    .dark .mp-cobrador      { color:#94a3b8; }
    .dark .mp-loc           { color:#818cf8; }

    /* Leaflet dark fix */
    .dark .leaflet-container { background:#18181f; }
    .dark .leaflet-popup-content-wrapper { background:#252530; color:#f1f5f9; border:1px solid #2e2e3a; }
    .dark .leaflet-popup-tip { background:#252530; }
</style>

@php
    $devices    = $this->getDevices();
    $ventasHoy  = $this->getVentasHoy();
    $cobrosDia  = $this->getCobrosDia();
    $total      = $devices->count();

    $enLinea     = $devices->filter(fn($d) => $d->estado_calc === 'activo')->count();
    $sinConexion = $devices->filter(fn($d) => $d->estado_calc === 'sin_conexion')->count();
    $bateriaLow  = $devices->filter(fn($d) => $d->estado_calc === 'bateria_baja')->count();

    $alertas = $devices->filter(fn($d) => $d->estado_calc !== 'activo');

    $estadoCfg = [
        'activo'       => ['label' => 'Online',    'color' => '#34d399', 'bg' => 'rgba(52,211,153,.12)'],
        'sin_conexion' => ['label' => 'Offline',   'color' => '#f87171', 'bg' => 'rgba(248,113,113,.12)'],
        'bateria_baja' => ['label' => 'Bat. baja', 'color' => '#fbbf24', 'bg' => 'rgba(251,191,36,.12)'],
        'apagado'      => ['label' => 'Apagado',   'color' => '#6b7280', 'bg' => 'rgba(107,114,128,.12)'],
    ];

    $devicesGeo = $devices->filter(fn($d) => $d->latitud && $d->longitud)->map(fn($d) => [
        'nombre'   => $d->nombre,
        'cobrador' => $d->cobrador ? $d->cobrador->nombre . ' ' . $d->cobrador->apellido : null,
        'lat'      => $d->latitud,
        'lng'      => $d->longitud,
        'estado'   => $d->estado_calc,
        'bateria'  => $d->bateria,
    ])->values();
@endphp

{{-- Auto-refresh cada 30 segundos --}}
<div wire:poll.30s>

    {{-- ── STAT CARDS ──────────────────────────────────────── --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem">

        {{-- En línea --}}
        <div class="mp-card">
            <div class="mp-stat">
                <div class="mp-stat-icon" style="background:rgba(52,211,153,.12)">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2.2" stroke-linecap="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><circle cx="12" cy="20" r="1.5" fill="#34d399" stroke="none"/></svg>
                </div>
                <div>
                    <p class="mp-stat-label">POS en línea</p>
                    <p class="mp-stat-num" style="color:#34d399">{{ $enLinea }}</p>
                    <p class="mp-stat-sub">de {{ $total }} dispositivos</p>
                </div>
            </div>
        </div>

        {{-- Sin conexión --}}
        <div class="mp-card">
            <div class="mp-stat">
                <div class="mp-stat-icon" style="background:rgba(248,113,113,.12)">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2.2" stroke-linecap="round"><line x1="2" y1="2" x2="22" y2="22"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/><path d="M10.71 5.05A16 16 0 0 1 22.56 9"/><path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><circle cx="12" cy="20" r="1.5" fill="#f87171" stroke="none"/></svg>
                </div>
                <div>
                    <p class="mp-stat-label">Sin conexión</p>
                    <p class="mp-stat-num" style="color:#f87171">{{ $sinConexion }}</p>
                    <p class="mp-stat-sub">{{ $total > 0 ? round($sinConexion / $total * 100) : 0 }}% del total</p>
                </div>
            </div>
        </div>

        {{-- Batería baja --}}
        <div class="mp-card">
            <div class="mp-stat">
                <div class="mp-stat-icon" style="background:rgba(251,191,36,.12)">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2.2" stroke-linecap="round"><rect x="2" y="7" width="18" height="10" rx="2"/><path d="M22 11v2"/><line x1="6" y1="11" x2="6" y2="13"/></svg>
                </div>
                <div>
                    <p class="mp-stat-label">Batería baja</p>
                    <p class="mp-stat-num" style="color:#fbbf24">{{ $bateriaLow }}</p>
                    <p class="mp-stat-sub">menos de 20%</p>
                </div>
            </div>
        </div>

        {{-- Ventas hoy --}}
        <div class="mp-card">
            <div class="mp-stat">
                <div class="mp-stat-icon" style="background:rgba(96,165,250,.12)">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2.2" stroke-linecap="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div>
                    <p class="mp-stat-label">Cobrado hoy</p>
                    <p class="mp-stat-num" style="color:#60a5fa;font-size:1.6rem">${{ number_format($ventasHoy, 2) }}</p>
                    <p class="mp-stat-sub">{{ $cobrosDia }} clientes</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── MAPA + ALERTAS ───────────────────────────────────── --}}
    <div style="display:grid;grid-template-columns:1fr 300px;gap:1rem;margin-bottom:1.5rem">

        {{-- Mapa --}}
        <div class="mp-card">
            <div class="mp-section-title">
                <span>Mapa en vivo</span>
                <span class="mp-live"><span class="mp-live-dot"></span> En tiempo real</span>
            </div>
            <div wire:ignore id="pos-map" style="height:350px"></div>
        </div>

        {{-- Alertas --}}
        <div class="mp-card" style="display:flex;flex-direction:column">
            <div class="mp-section-title">
                <span>Alertas</span>
                @if($alertas->isNotEmpty())
                    <span style="background:rgba(248,113,113,.15);color:#f87171;padding:.1rem .5rem;border-radius:9999px;font-size:.65rem;font-weight:700">{{ $alertas->count() }}</span>
                @endif
            </div>
            <div style="flex:1;overflow-y:auto;max-height:310px">
                @forelse($alertas->sortByDesc('ultimo_ping') as $d)
                    @php $st = $estadoCfg[$d->estado_calc]; @endphp
                    <div class="mp-alert-item">
                        <div class="mp-alert-icon" style="background:{{ $st['bg'] }}">
                            @if($d->estado_calc === 'sin_conexion')
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="{{ $st['color'] }}" stroke-width="2.5" stroke-linecap="round"><line x1="2" y1="2" x2="22" y2="22"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55M5 12.55a10.94 10.94 0 0 1 5.17-2.39M8.53 16.11a6 6 0 0 1 6.95 0"/><circle cx="12" cy="20" r="1" fill="{{ $st['color'] }}" stroke="none"/></svg>
                            @elseif($d->estado_calc === 'bateria_baja')
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="{{ $st['color'] }}" stroke-width="2.5" stroke-linecap="round"><rect x="2" y="7" width="18" height="10" rx="2"/><path d="M22 11v2"/></svg>
                            @else
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="{{ $st['color'] }}" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><circle cx="12" cy="16" r=".5" fill="{{ $st['color'] }}" stroke="none"/></svg>
                            @endif
                        </div>
                        <div style="flex:1;min-width:0">
                            <p class="mp-alert-name">{{ $d->nombre }}</p>
                            <p class="mp-alert-desc" style="color:{{ $st['color'] }}">
                                {{ $st['label'] }}@if($d->bateria !== null && $d->estado_calc === 'bateria_baja') · {{ $d->bateria }}%@endif
                            </p>
                        </div>
                        <p class="mp-alert-time">{{ $d->ultimo_ping ? $d->ultimo_ping->diffForHumans(null, true, true) : 'Nunca' }}</p>
                    </div>
                @empty
                    <div style="padding:2.5rem 1rem;text-align:center;font-size:.825rem">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="1.8" stroke-linecap="round" style="margin:0 auto .5rem"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <p style="color:#34d399;font-weight:600">Todo en orden</p>
                        <p style="color:#6b7280;margin-top:.2rem">Sin alertas activas</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ── TABLA DE DISPOSITIVOS ──────────────────────────── --}}
    <div class="mp-card">
        <div class="mp-section-title">
            <span>Estado de dispositivos</span>
            <span style="font-size:.7rem;color:#6b7280;font-weight:500">Actualiza cada 30s</span>
        </div>
        <div class="mp-table-wrap">
            <table class="mp-table">
                <thead class="mp-thead">
                    <tr>
                        <th>POS</th>
                        <th>Cobrador / Usuario</th>
                        <th>Estado</th>
                        <th>Última conexión</th>
                        <th>Batería</th>
                        <th style="text-align:center">Internet</th>
                        <th>Versión</th>
                        <th>Ubicación</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($devices as $d)
                        @php $st = $estadoCfg[$d->estado_calc]; @endphp
                        <tr class="mp-tr">
                            <td class="mp-td">
                                <p class="mp-pos-name">{{ $d->nombre }}</p>
                                <p class="mp-pos-serial">{{ $d->serial }}</p>
                            </td>
                            <td class="mp-td mp-cobrador">
                                @if($d->cobrador)
                                    {{ $d->cobrador->nombre }} {{ $d->cobrador->apellido }}
                                @elseif($d->user)
                                    {{ $d->user->name }}
                                @else
                                    <span style="color:#4b5563">—</span>
                                @endif
                            </td>
                            <td class="mp-td">
                                <span class="mp-badge" style="background:{{ $st['bg'] }};color:{{ $st['color'] }}">
                                    <span class="mp-dot {{ $d->estado_calc === 'activo' ? 'mp-dot-pulse' : '' }}" style="background:{{ $st['color'] }}"></span>
                                    {{ $st['label'] }}
                                </span>
                            </td>
                            <td class="mp-td" style="color:#6b7280;font-size:.78rem">
                                {{ $d->ultimo_ping ? $d->ultimo_ping->diffForHumans() : 'Nunca' }}
                            </td>
                            <td class="mp-td">
                                @if($d->bateria !== null)
                                    <div class="mp-bat-wrap">
                                        <div class="mp-bat-bar">
                                            <div class="mp-bat-fill" style="width:{{ $d->bateria }}%;background:{{ $d->bateria_color }}"></div>
                                        </div>
                                        <span class="mp-bat-pct">{{ $d->bateria }}%</span>
                                    </div>
                                @else
                                    <span style="color:#4b5563">—</span>
                                @endif
                            </td>
                            <td class="mp-td" style="text-align:center">
                                @if($d->tiene_internet)
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2.5" stroke-linecap="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><circle cx="12" cy="20" r="1.5" fill="#34d399" stroke="none"/></svg>
                                @else
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#4b5563" stroke-width="2" stroke-linecap="round"><line x1="2" y1="2" x2="22" y2="22"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/></svg>
                                @endif
                            </td>
                            <td class="mp-td" style="font-size:.72rem;color:#6b7280">
                                {{ $d->app_version ?? '—' }}
                            </td>
                            <td class="mp-td">
                                @if($d->latitud && $d->longitud)
                                    <a href="https://maps.google.com/?q={{ $d->latitud }},{{ $d->longitud }}" target="_blank" class="mp-loc">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        Ver
                                    </a>
                                @else
                                    <span style="color:#3f3f50">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:2.5rem;text-align:center;color:#6b7280;font-size:.825rem">
                                Sin dispositivos POS registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>{{-- end wire:poll --}}

<script>
(function () {
    let posMap = null;

    function loadLeaflet(cb) {
        if (window.L) { cb(); return; }
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);
        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = cb;
        document.head.appendChild(script);
    }

    function initMap() {
        const el = document.getElementById('pos-map');
        if (!el || posMap) return;

        loadLeaflet(function () {
            posMap = L.map('pos-map', { zoomControl: true, attributionControl: false })
                      .setView([13.7942, -88.8965], 9);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(posMap);
            L.control.attribution({ prefix: '© <a href="https://openstreetmap.org">OSM</a>' }).addTo(posMap);

            const colors = { activo:'#34d399', sin_conexion:'#f87171', bateria_baja:'#fbbf24', apagado:'#6b7280' };
            const devices = @json($devicesGeo);
            const bounds  = [];

            devices.forEach(function (d) {
                const color = colors[d.estado] || '#6b7280';
                const popup = `<div style="font-family:system-ui;min-width:140px">
                    <p style="font-weight:700;font-size:13px;margin:0 0 4px">${d.nombre}</p>
                    ${d.cobrador ? `<p style="font-size:11px;margin:0 0 3px">👤 ${d.cobrador}</p>` : ''}
                    <p style="font-size:11px;margin:0 0 3px">🔋 ${d.bateria ?? '—'}%</p>
                    <span style="font-size:11px;font-weight:700;color:${color}">● ${d.estado}</span>
                </div>`;
                L.circleMarker([d.lat, d.lng], {
                    radius: 11, color: '#fff', weight: 2.5, fillColor: color, fillOpacity: .9,
                }).bindPopup(popup).addTo(posMap);
                bounds.push([d.lat, d.lng]);
            });

            if (bounds.length > 1)     posMap.fitBounds(bounds, { padding: [30, 30] });
            else if (bounds.length === 1) posMap.setView(bounds[0], 14);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMap);
    } else {
        initMap();
    }
    document.addEventListener('livewire:navigated', function () { posMap = null; initMap(); });
}());
</script>

</div>
</x-filament-panels::page>
