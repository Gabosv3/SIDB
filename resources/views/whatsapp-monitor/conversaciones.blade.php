@extends('whatsapp-monitor._layout', ['activo' => 'conversaciones'])

@section('page-title', 'Conversaciones')

@section('styles')
<style>
    .wm-conv-shell { display:grid; grid-template-columns:340px 1fr 300px; gap:1rem; align-items:start; height:calc(100vh - 60px - 3rem); }
    @media (max-width:1200px) { .wm-conv-shell { grid-template-columns:300px 1fr; } .wm-conv-info { display:none; } }
    @media (max-width:800px) { .wm-conv-shell { grid-template-columns:1fr; } .wm-conv-list { display:none; } }

    .wm-conv-list, .wm-conv-chat, .wm-conv-info { height:100%; display:flex; flex-direction:column; overflow:hidden; }

    .wm-search-wrap { padding:.9rem 1rem .7rem; }
    .wm-search { width:100%; padding:.55rem .8rem; border:1px solid var(--wm-border); border-radius:.6rem; font-size:.8rem; outline:none; background:#F9FAFB; }
    .wm-search:focus { border-color:var(--wm-green); background:#fff; }

    .wm-filter-select { width:100%; padding:.5rem .7rem; border:1px solid var(--wm-border); border-radius:.6rem; font-size:.78rem; background:#fff; outline:none; }

    .wm-list-scroll { flex:1; overflow-y:auto; }
    .wm-conv-item { display:flex; align-items:center; gap:.65rem; padding:.7rem 1rem; cursor:pointer; border-left:3px solid transparent; }
    .wm-conv-item:hover { background:#F9FAFB; }
    .wm-conv-item.active { background:#F0FDF4; border-left-color:var(--wm-green); }
    .wm-conv-avatar { width:38px; height:38px; border-radius:50%; background:#EEF2FF; color:var(--wm-blue); font-size:.78rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .wm-conv-name { font-size:.83rem; font-weight:600; color:var(--wm-text); }
    .wm-conv-preview { font-size:.74rem; color:var(--wm-gray); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:170px; }
    .wm-conv-meta { margin-left:auto; text-align:right; flex-shrink:0; }
    .wm-conv-time { font-size:.68rem; color:var(--wm-gray); }

    .wm-chat-header { display:flex; align-items:center; gap:.7rem; padding:.9rem 1.1rem; border-bottom:1px solid var(--wm-border); }
    .wm-chat-avatar { width:42px; height:42px; border-radius:50%; background:#EEF2FF; color:var(--wm-blue); font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .wm-chat-name { font-size:.92rem; font-weight:700; color:var(--wm-text); }
    .wm-chat-phone { font-size:.75rem; color:var(--wm-gray); }
    .wm-chat-badge { margin-left:auto; padding:.2rem .65rem; border-radius:9999px; font-size:.68rem; font-weight:700; background:#DCFCE7; color:#166534; }

    .wm-chat-body { flex:1; overflow-y:auto; padding:1.1rem; display:flex; flex-direction:column; gap:.5rem; background:#FAFBFC; }
    .wm-bubble-row { display:flex; }
    .wm-bubble-row.out { justify-content:flex-end; }
    .wm-bubble { max-width:65%; padding:.55rem .85rem; border-radius:.9rem; font-size:.82rem; line-height:1.4; }
    .wm-bubble.in { background:#fff; border:1px solid var(--wm-border); color:var(--wm-text); border-bottom-left-radius:.2rem; }
    .wm-bubble.out { background:#DCFCE7; color:#14532D; border-bottom-right-radius:.2rem; }
    .wm-bubble-time { display:block; font-size:.64rem; color:var(--wm-gray); margin-top:.2rem; text-align:right; }

    .wm-readonly-banner { display:flex; align-items:center; gap:.5rem; padding:.75rem 1.1rem; background:#EFF6FF; border-top:1px solid var(--wm-border); font-size:.78rem; color:var(--wm-blue); font-weight:600; }

    .wm-info-section { padding:1rem 1.1rem; border-bottom:1px solid var(--wm-border); }
    .wm-info-section:last-child { border-bottom:none; }
    .wm-info-title { font-size:.75rem; font-weight:700; color:var(--wm-text); margin-bottom:.7rem; }
    .wm-info-avatar-big { width:56px; height:56px; border-radius:50%; background:#EEF2FF; color:var(--wm-blue); font-weight:700; font-size:1.1rem; display:flex; align-items:center; justify-content:center; margin:0 auto .5rem; }
    .wm-info-name { text-align:center; font-size:.86rem; font-weight:700; color:var(--wm-text); }
    .wm-info-phone { text-align:center; font-size:.74rem; color:var(--wm-gray); margin-top:.1rem; }
    .wm-info-row { display:flex; justify-content:space-between; align-items:center; padding:.4rem 0; font-size:.78rem; }
    .wm-info-label { color:var(--wm-gray); }
    .wm-info-value { color:var(--wm-text); font-weight:600; text-align:right; }
</style>
@endsection

@section('content')
<div class="wm-conv-shell">
    <div class="wm-card wm-conv-list">
        <div class="wm-search-wrap" style="display:flex; flex-direction:column; gap:.5rem;">
            <input type="text" id="wm-buscar" class="wm-search" placeholder="Buscar cliente o número...">
            <select id="wm-cobrador-filter" class="wm-filter-select">
                <option value="">Todos los cobradores</option>
            </select>
        </div>
        <div class="wm-list-scroll" id="wm-lista-conversaciones">
            <div class="wm-empty">Cargando...</div>
        </div>
    </div>

    <div class="wm-card wm-conv-chat" id="wm-chat-panel">
        <div class="wm-empty" style="margin:auto;">Seleccioná una conversación para ver los mensajes.</div>
    </div>

    <div class="wm-card wm-conv-info" id="wm-info-panel" style="display:none;"></div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    var tenant = {{ (int) $tenant }};
    var baseUrl = '/whatsapp-center/' + tenant;
    var lista = document.getElementById('wm-lista-conversaciones');
    var chatPanel = document.getElementById('wm-chat-panel');
    var infoPanel = document.getElementById('wm-info-panel');
    var cobradorSelect = document.getElementById('wm-cobrador-filter');
    var buscarInput = document.getElementById('wm-buscar');
    var buscarTimeout = null;
    var conversacionActivaId = null;
    var cobradoresCargados = false;

    function iniciales(nombre) {
        var partes = (nombre || '?').trim().split(/\s+/);
        return ((partes[0] || '')[0] || '') + ((partes[1] || '')[0] || '');
    }

    function cargarLista() {
        var url = baseUrl + '/data';
        var params = [];
        if (cobradorSelect.value) params.push('cobrador_id=' + encodeURIComponent(cobradorSelect.value));
        if (buscarInput.value.trim() !== '') params.push('buscar=' + encodeURIComponent(buscarInput.value.trim()));
        if (params.length) url += '?' + params.join('&');

        fetch(url).then(function (r) { return r.json(); }).then(function (data) {
            if (!cobradoresCargados) {
                data.cobradores.forEach(function (c) {
                    var opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    cobradorSelect.appendChild(opt);
                });
                cobradoresCargados = true;
            }

            if (data.conversaciones.length === 0) {
                lista.innerHTML = '<div class="wm-empty">Todavía no hay conversaciones registradas.<br><span style="font-size:.72rem;">Van a aparecer acá en cuanto se conecte WhatsApp Coexistence.</span></div>';
                return;
            }

            lista.innerHTML = data.conversaciones.map(function (c) {
                return '<div class="wm-conv-item ' + (conversacionActivaId === c.id ? 'active' : '') + '" data-id="' + c.id + '">' +
                    '<div class="wm-conv-avatar">' + iniciales(c.cliente).toUpperCase() + '</div>' +
                    '<div style="min-width:0;"><div class="wm-conv-name">' + c.cliente + '</div><div class="wm-conv-preview">' + (c.ultimo_mensaje || '—') + '</div></div>' +
                    '<div class="wm-conv-meta"><div class="wm-conv-time">' + (c.ultimo_mensaje_hora || '') + '</div></div>' +
                '</div>';
            }).join('');

            lista.querySelectorAll('.wm-conv-item').forEach(function (el) {
                el.addEventListener('click', function () { abrirConversacion(parseInt(this.dataset.id, 10)); });
            });
        });
    }

    function abrirConversacion(id) {
        conversacionActivaId = id;
        lista.querySelectorAll('.wm-conv-item').forEach(function (el) {
            el.classList.toggle('active', parseInt(el.dataset.id, 10) === id);
        });

        fetch(baseUrl + '/conversaciones/' + id + '/mensajes')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var burbujas = data.mensajes.map(function (m) {
                    return '<div class="wm-bubble-row ' + m.direction + '"><div class="wm-bubble ' + m.direction + '">' + m.body + '<span class="wm-bubble-time">' + m.hora + '</span></div></div>';
                }).join('') || '<div class="wm-empty">Sin mensajes en esta conversación.</div>';

                chatPanel.innerHTML =
                    '<div class="wm-chat-header">' +
                        '<div class="wm-chat-avatar">' + iniciales(data.cliente).toUpperCase() + '</div>' +
                        '<div><div class="wm-chat-name">' + data.cliente + '</div><div class="wm-chat-phone">' + (data.telefono || '') + '</div></div>' +
                        '<span class="wm-chat-badge">' + (data.estado === 'abierta' ? 'Activo' : 'Cerrada') + '</span>' +
                    '</div>' +
                    '<div class="wm-chat-body">' + burbujas + '</div>' +
                    '<div class="wm-readonly-banner">🔒 Modo solo lectura: no podés enviar mensajes desde acá</div>';

                infoPanel.style.display = '';
                infoPanel.innerHTML =
                    '<div class="wm-info-section">' +
                        '<div class="wm-info-avatar-big">' + iniciales(data.cliente).toUpperCase() + '</div>' +
                        '<div class="wm-info-name">' + data.cliente + '</div>' +
                        '<div class="wm-info-phone">' + (data.telefono || '') + '</div>' +
                    '</div>' +
                    '<div class="wm-info-section">' +
                        '<div class="wm-info-title">Información del contacto</div>' +
                        '<div class="wm-info-row"><span class="wm-info-label">Cobrador asignado</span><span class="wm-info-value">' + (data.cobrador || '—') + '</span></div>' +
                        '<div class="wm-info-row"><span class="wm-info-label">Estado</span><span class="wm-info-value">' + (data.estado === 'abierta' ? 'Activo' : 'Cerrada') + '</span></div>' +
                        '<div class="wm-info-row"><span class="wm-info-label">Última actividad</span><span class="wm-info-value">' + (data.ultima_actividad || '—') + '</span></div>' +
                    '</div>' +
                    '<div class="wm-info-section">' +
                        '<div class="wm-info-title">Resumen de la conversación</div>' +
                        '<div class="wm-info-row"><span class="wm-info-label">Mensajes hoy</span><span class="wm-info-value">' + data.resumen.mensajes_hoy + '</span></div>' +
                        '<div class="wm-info-row"><span class="wm-info-label">Primera conversación</span><span class="wm-info-value">' + (data.resumen.primera_conversacion || '—') + '</span></div>' +
                        '<div class="wm-info-row"><span class="wm-info-label">Total conversaciones</span><span class="wm-info-value">' + data.resumen.total_conversaciones + '</span></div>' +
                    '</div>';
            });
    }

    cobradorSelect.addEventListener('change', cargarLista);
    buscarInput.addEventListener('input', function () {
        clearTimeout(buscarTimeout);
        buscarTimeout = setTimeout(cargarLista, 300);
    });

    cargarLista();
})();
</script>
@endsection
