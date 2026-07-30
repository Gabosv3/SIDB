@extends('whatsapp-monitor._layout', ['activo' => 'dashboard'])

@section('page-title', 'Dashboard')

@section('styles')
<style>
    .wm-dash-grid { display:grid; grid-template-columns:1.3fr 1fr; gap:1rem; align-items:start; }
    @media (max-width:1000px) { .wm-dash-grid { grid-template-columns:1fr; } }

    .wm-card-header { padding:1rem 1.25rem; border-bottom:1px solid var(--wm-border); font-size:.86rem; font-weight:700; color:var(--wm-text); }

    .wm-msg-item { display:flex; align-items:flex-start; gap:.7rem; padding:.75rem 1.25rem; border-bottom:1px solid #F3F4F6; }
    .wm-msg-item:last-child { border-bottom:none; }
    .wm-msg-avatar { width:32px; height:32px; border-radius:50%; background:#EEF2FF; color:var(--wm-blue); font-size:.7rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .wm-msg-name { font-size:.8rem; font-weight:600; color:var(--wm-text); }
    .wm-msg-body { font-size:.78rem; color:var(--wm-gray); margin-top:.1rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:280px; }
    .wm-msg-meta { margin-left:auto; text-align:right; flex-shrink:0; }
    .wm-msg-time { font-size:.68rem; color:var(--wm-gray); }
    .wm-msg-dir { display:inline-block; margin-top:.2rem; font-size:.63rem; font-weight:700; padding:.05rem .4rem; border-radius:9999px; }
    .wm-msg-dir.out { background:#DCFCE7; color:#166534; }
    .wm-msg-dir.in { background:#EFF6FF; color:#1D4ED8; }

    .wm-dev-item { display:flex; align-items:center; gap:.7rem; padding:.7rem 1.25rem; border-bottom:1px solid #F3F4F6; }
    .wm-dev-item:last-child { border-bottom:none; }
    .wm-dev-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
    .wm-dev-name { font-size:.8rem; font-weight:600; color:var(--wm-text); }
    .wm-dev-sub { font-size:.7rem; color:var(--wm-gray); }
    .wm-dev-bat { margin-left:auto; font-size:.72rem; font-weight:700; }
</style>
@endsection

@section('content')
<div class="wm-stat-grid" id="wm-stats">
    <div class="wm-card"><div class="wm-stat-card">
        <div class="wm-stat-icon" style="background:#DCFCE7;">🟢</div>
        <div><div class="wm-stat-label">Dispositivos Online</div><div class="wm-stat-num" id="s-dispositivos">—</div><div class="wm-stat-sub" id="s-dispositivos-sub"></div></div>
    </div></div>
    <div class="wm-card"><div class="wm-stat-card">
        <div class="wm-stat-icon" style="background:#EFF6FF;">👨</div>
        <div><div class="wm-stat-label">Cobradores Activos</div><div class="wm-stat-num" id="s-cobradores">—</div><div class="wm-stat-sub" id="s-cobradores-sub"></div></div>
    </div></div>
    <div class="wm-card"><div class="wm-stat-card">
        <div class="wm-stat-icon" style="background:#FEF3C7;">💬</div>
        <div><div class="wm-stat-label">Conversaciones</div><div class="wm-stat-num" id="s-conversaciones">—</div><div class="wm-stat-sub">Activas ahora mismo</div></div>
    </div></div>
    <div class="wm-card"><div class="wm-stat-card">
        <div class="wm-stat-icon" style="background:#F3E8FF;">📨</div>
        <div><div class="wm-stat-label">Mensajes Hoy</div><div class="wm-stat-num" id="s-mensajes">—</div><div class="wm-stat-sub">Enviados y recibidos</div></div>
    </div></div>
</div>

<div class="wm-dash-grid">
    <div>
        <div class="wm-card" style="margin-bottom:1rem;">
            <div class="wm-card-header">Conversaciones por hora (hoy)</div>
            <div style="padding:1rem 1.25rem;"><canvas id="wm-chart-horas" height="90"></canvas></div>
        </div>
        <div class="wm-card">
            <div class="wm-card-header">Últimos mensajes</div>
            <div id="wm-ultimos-mensajes"></div>
        </div>
    </div>

    <div class="wm-card">
        <div class="wm-card-header">Dispositivos conectados</div>
        <div id="wm-dispositivos"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    var tenant = {{ (int) $tenant }};
    var estadoColores = { activo: '#22C55E', bateria_baja: '#F59E0B', sin_conexion: '#EF4444', apagado: '#9CA3AF' };
    var estadoLabels = { activo: 'En línea', bateria_baja: 'Batería baja', sin_conexion: 'Sin conexión', apagado: 'Apagado' };

    function iniciales(nombre) {
        var partes = (nombre || '?').trim().split(/\s+/);
        return ((partes[0] || '')[0] || '') + ((partes[1] || '')[0] || '');
    }

    fetch('/whatsapp-center/' + tenant + '/dashboard-data')
        .then(function (r) { return r.json(); })
        .then(function (data) {
            document.getElementById('s-dispositivos').textContent = data.dispositivos_online;
            document.getElementById('s-dispositivos-sub').textContent = 'de ' + data.dispositivos_total + ' registrados';
            document.getElementById('s-cobradores').textContent = data.cobradores_en_linea;
            document.getElementById('s-cobradores-sub').textContent = 'de ' + data.cobradores_total + ' activos';
            document.getElementById('s-conversaciones').textContent = data.conversaciones_activas;
            document.getElementById('s-mensajes').textContent = data.mensajes_hoy;

            // Últimos mensajes
            var cont = document.getElementById('wm-ultimos-mensajes');
            if (data.ultimos_mensajes.length === 0) {
                cont.innerHTML = '<div class="wm-empty">Todavía no hay mensajes registrados.</div>';
            } else {
                cont.innerHTML = data.ultimos_mensajes.map(function (m) {
                    return '<div class="wm-msg-item">' +
                        '<div class="wm-msg-avatar">' + iniciales(m.cliente).toUpperCase() + '</div>' +
                        '<div><div class="wm-msg-name">' + (m.cliente || '—') + '</div><div class="wm-msg-body">' + (m.body || '') + '</div></div>' +
                        '<div class="wm-msg-meta"><div class="wm-msg-time">' + m.hora + '</div><span class="wm-msg-dir ' + m.direction + '">' + (m.direction === 'out' ? (m.cobrador || 'Enviado') : 'Recibido') + '</span></div>' +
                    '</div>';
                }).join('');
            }

            // Dispositivos conectados
            var contDev = document.getElementById('wm-dispositivos');
            if (data.dispositivos.length === 0) {
                contDev.innerHTML = '<div class="wm-empty">No hay dispositivos registrados.</div>';
            } else {
                contDev.innerHTML = data.dispositivos.map(function (d) {
                    var color = estadoColores[d.estado] || '#9CA3AF';
                    var bat = d.bateria !== null ? d.bateria + '%' : '—';
                    return '<div class="wm-dev-item">' +
                        '<span class="wm-dev-dot" style="background:' + color + ';"></span>' +
                        '<div><div class="wm-dev-name">' + d.nombre + '</div><div class="wm-dev-sub">' + (d.cobrador || 'Sin asignar') + ' · ' + (estadoLabels[d.estado] || d.estado) + '</div></div>' +
                        '<span class="wm-dev-bat" style="color:' + color + ';">' + bat + '</span>' +
                    '</div>';
                }).join('');
            }

            // Gráfico de conversaciones por hora
            var ctx = document.getElementById('wm-chart-horas');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Array.from({length: 24}, function (_, i) { return i + 'h'; }),
                    datasets: [{ data: data.conversaciones_por_hora, backgroundColor: '#22C55E', borderRadius: 4, maxBarThickness: 18 }],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        });
})();
</script>
@endsection
