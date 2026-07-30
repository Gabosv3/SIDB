@extends('pos._layout')

@section('page-title', 'Perfil del cliente')

@section('styles')
<style>
    .cp-back { display:inline-flex; align-items:center; gap:.35rem; font-size:.78rem; color:var(--muted); text-decoration:none; margin-bottom:1rem; }
    .cp-back:hover { color:var(--text); }

    /* ── Encabezado ── */
    .cp-header-card { background:var(--card); border:1px solid var(--border); border-radius:.875rem; box-shadow:0 1px 3px rgba(0,0,0,.05); padding:1.25rem 1.4rem; margin-bottom:1.25rem; display:flex; align-items:center; gap:1.1rem; flex-wrap:wrap; }
    html.dark .cp-header-card { box-shadow:none; }
    .cp-avatar { width:56px; height:56px; border-radius:50%; background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; font-size:1.3rem; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .cp-header-info { flex:1; min-width:200px; }
    .cp-nombre { font-size:1.3rem; font-weight:800; color:var(--text); display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .cp-codigo-badge { font-size:.7rem; font-weight:700; color:var(--muted); background:var(--subtle); border:1px solid var(--border); border-radius:9999px; padding:.15rem .6rem; }
    .cp-header-meta { display:flex; flex-wrap:wrap; gap:.35rem 1.1rem; margin-top:.4rem; font-size:.8rem; color:var(--text-2); }
    .cp-header-meta span { display:inline-flex; align-items:center; gap:.3rem; }
    .cp-ruta-warn { color:#dc2626; font-weight:600; }

    .cp-eliminar-btn { display:inline-flex; align-items:center; gap:.4rem; flex-shrink:0; padding:.55rem .9rem; border-radius:.55rem; border:1px solid #fecaca; background:#fef2f2; color:#dc2626; font-size:.78rem; font-weight:700; cursor:pointer; }
    .cp-eliminar-btn:hover { background:#fee2e2; }
    html.dark .cp-eliminar-btn { background:rgba(220,38,38,.12); border-color:rgba(220,38,38,.3); color:#fca5a5; }
    html.dark .cp-eliminar-btn:hover { background:rgba(220,38,38,.2); }

    /* ── Layout de 2 columnas ── */
    .cp-layout { display:grid; grid-template-columns:1.7fr 1fr; gap:1.25rem; align-items:start; }
    @media (max-width:960px) { .cp-layout { grid-template-columns:1fr; } }

    .cp-section-title { font-size:.85rem; font-weight:700; color:var(--text); margin:0 0 .75rem; display:flex; align-items:center; gap:.4rem; }
    .cp-main > .cp-section-title:not(:first-child) { margin-top:1.5rem; }

    /* ── Info sidebar cards ── */
    .cp-info-card + .cp-info-card { margin-top:1.1rem; }
    .cp-info-row { display:flex; justify-content:space-between; gap:.75rem; padding:.5rem 0; border-bottom:1px solid var(--border-2); font-size:.8rem; }
    .cp-info-row:last-child { border-bottom:none; }
    .cp-info-label { color:var(--muted); flex-shrink:0; }
    .cp-info-value { color:var(--text-2); font-weight:600; text-align:right; }
    .cp-empty-hint { color:var(--muted); font-size:.78rem; padding:.4rem 0; }

    .cp-ref-item { padding:.6rem 0; border-bottom:1px dashed var(--border-2); font-size:.8rem; }
    .cp-ref-item:last-child { border-bottom:none; }
    .cp-ref-nombre { font-weight:700; color:var(--text); }
    .cp-ref-detalle { color:var(--muted); font-size:.75rem; margin-top:.1rem; }

    .cp-docs-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:.6rem; }
    .cp-doc-thumb { display:block; border-radius:.6rem; overflow:hidden; border:1px solid var(--border); aspect-ratio:4/3; background:var(--subtle); }
    .cp-doc-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
    .cp-doc-label { font-size:.66rem; color:var(--muted); text-align:center; margin-top:.25rem; }

    /* ── Ventas ── */
    .cr-venta-card { border:1px solid var(--border); border-radius:.75rem; padding:1rem; margin-bottom:1rem; background:var(--card); }
    .cr-venta-fecha-group { border:1px solid var(--border); border-radius:.85rem; padding:.75rem .75rem 0; margin-bottom:1rem; background:var(--subtle); }
    .cr-venta-fecha-group .cr-venta-card { margin-bottom:.75rem; }
    .cr-venta-fecha-group-label { font-size:.68rem; font-weight:700; color:var(--muted-2); text-transform:uppercase; letter-spacing:.03em; padding:0 .1rem .5rem; display:flex; align-items:center; gap:.3rem; }
    .cr-venta-card-header { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.6rem; flex-wrap:wrap; }
    .cr-venta-badge { display:inline-flex; align-items:center; padding:.2rem .6rem; border-radius:9999px; font-size:.68rem; font-weight:700; }
    .cp-productos { display:flex; flex-wrap:wrap; gap:.4rem; margin-bottom:.6rem; }
    .cp-producto-pill { display:inline-flex; align-items:center; gap:.3rem; background:var(--subtle); border:1px solid var(--border); border-radius:9999px; padding:.2rem .65rem; font-size:.72rem; color:var(--text-2); }
    .cp-producto-pill b { color:var(--text); }
    .cr-venta-cuotas-bar { height:6px; border-radius:3px; background:var(--border); overflow:hidden; display:flex; margin-top:.5rem; }
    .cr-venta-pagos-list { margin-top:.75rem; border-top:1px solid var(--border-2); padding-top:.6rem; }
    .cr-venta-pago-row { display:flex; justify-content:space-between; font-size:.78rem; padding:.25rem 0; color:var(--text-2); }
    .cr-abono-edit { background:none; border:1px solid transparent; cursor:pointer; color:var(--muted-2); padding:.3rem; border-radius:.4rem; display:inline-flex; }
    .cr-abono-edit:hover { background:var(--subtle); border-color:var(--border); color:#10b981; }

    .cp-historial-item { display:flex; flex-direction:column; gap:.15rem; padding:.65rem 0; border-bottom:1px dashed var(--border-2); }
    .cp-historial-item:last-child { border-bottom:none; }

    .cp-loading { text-align:center; padding:3rem; color:var(--muted); font-size:.88rem; }
    .cp-vacio { color:var(--muted); font-size:.85rem; padding:.4rem 0 1rem; }

    .cr-save-toast {
        position:fixed; bottom:1.25rem; right:1.25rem; background:#16a34a; color:#fff;
        padding:.6rem 1rem; border-radius:.625rem; font-size:.8rem; font-weight:600;
        box-shadow:0 4px 16px rgba(0,0,0,.18); opacity:0; transform:translateY(8px);
        transition:all .2s; pointer-events:none; z-index:500; max-width:calc(100vw - 2.5rem);
    }
    .cr-save-toast.show { opacity:1; transform:translateY(0); }
</style>
@endsection

@section('content')
<a href="{{ route('clientes-ruta.index', $tenant) }}" class="cp-back">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Volver a Clientes por Ruta
</a>

<div id="cp-body">
    <div class="cp-loading">Cargando...</div>
</div>

<div class="cr-save-toast" id="cp-toast">Guardado</div>
@endsection

@section('scripts')
<script>
(function () {
    var tenant = {{ (int) $tenant }};
    var clienteId = {{ (int) $cliente->id }};
    var esSuperAdmin = @json($esSuperAdmin);
    var baseUrl = '/clientes-ruta/' + tenant;
    var body = document.getElementById('cp-body');
    var toast = document.getElementById('cp-toast');

    function showToast(msg) {
        toast.textContent = msg;
        toast.classList.add('show');
        setTimeout(function () { toast.classList.remove('show'); }, 1800);
    }

    function money(n) {
        return '$' + Number(n).toLocaleString('es-SV', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function iniciales(nombre) {
        var partes = nombre.trim().split(/\s+/);
        return ((partes[0] || '')[0] || '') + ((partes[1] || '')[0] || '');
    }

    var estadoLabels = { pendiente: 'Pendiente', completada: 'Completada', cancelada: 'Cancelada', devuelta: 'Devuelta' };
    var estadoColores = {
        pendiente: ['#fef9c3', '#854d0e'], completada: ['#dcfce7', '#16a34a'],
        cancelada: ['#fee2e2', '#dc2626'], devuelta: ['#e0f2fe', '#0369a1'],
    };

    function cargar() {
        body.innerHTML = '<div class="cp-loading">Cargando...</div>';

        var url = baseUrl + '/clientes/' + clienteId + '/detalle';
        var inicio = Date.now();
        var controller = new AbortController();
        // Si el servidor mata el proceso sin responder nada, el navegador se
        // queda esperando indefinidamente — este límite lo corta y lo distingue
        // de un error real de respuesta (status, JSON inválido, etc.).
        var timeoutId = setTimeout(function () { controller.abort(); }, 45000);

        console.log('[perfil-cliente] Pidiendo ' + url + ' ...');

        fetch(url, { signal: controller.signal })
            .then(function (r) {
                clearTimeout(timeoutId);
                var segundos = ((Date.now() - inicio) / 1000).toFixed(1);
                console.log('[perfil-cliente] Respuesta recibida en ' + segundos + 's — status ' + r.status + ' ' + r.statusText);

                return r.text().then(function (texto) {
                    if (!r.ok) {
                        console.error('[perfil-cliente] status=' + r.status + ' body=', texto);
                        throw new Error('HTTP ' + r.status + ': ' + texto.slice(0, 500));
                    }
                    try {
                        return JSON.parse(texto);
                    } catch (e) {
                        console.error('[perfil-cliente] La respuesta no es JSON válido. Cuerpo crudo:', texto);
                        throw new Error('Respuesta no es JSON válido (revisa la consola para ver el contenido completo).');
                    }
                });
            })
            .then(render)
            .catch(function (err) {
                clearTimeout(timeoutId);
                var segundos = ((Date.now() - inicio) / 1000).toFixed(1);
                var motivo = (err && err.name === 'AbortError')
                    ? 'El servidor no respondió en ' + segundos + 's (tiempo agotado) — probablemente el proceso se cortó del lado del servidor.'
                    : (err && err.message ? err.message : 'Error de red desconocido.');
                console.error('[perfil-cliente] Falló después de ' + segundos + 's:', err);
                body.innerHTML = '<div class="cp-loading">Error al cargar el perfil del cliente.<br><span style="font-size:.75rem; color:var(--muted-2);">' + motivo + ' — abre la consola del navegador (F12) para más detalle.</span></div>';
            });
    }

    // ── Tarjeta de resumen (estilo pm-stat, igual al resto del sistema) ──────
    function statCardHtml(icon, bg, color, label, valor) {
        return '<div class="pm-card"><div class="pm-stat">' +
            '<div class="pm-stat-icon" style="background:' + bg + '; color:' + color + ';">' + icon + '</div>' +
            '<div><div class="pm-stat-label">' + label + '</div><div class="pm-stat-num" style="font-size:1.3rem;">' + valor + '</div></div>' +
        '</div></div>';
    }

    function infoRow(label, valor) {
        if (!valor) return '';
        return '<div class="cp-info-row"><span class="cp-info-label">' + label + '</span><span class="cp-info-value">' + valor + '</span></div>';
    }

    function infoCardHtml(c) {
        var filas = infoRow('DUI', c.dui) + infoRow('NIT', c.nit) + infoRow('Email', c.email) +
            infoRow('Teléfono', c.telefono) + infoRow('WhatsApp', c.whatsapp) +
            infoRow('Dirección', c.direccion) +
            infoRow('Depto / Municipio', [c.departamento, c.municipio].filter(Boolean).join(' / ')) +
            infoRow('Distrito', c.distrito) +
            infoRow('Límite de crédito', c.limite_credito !== null ? money(c.limite_credito) : null);

        return '<div class="pm-card cp-info-card">' +
            '<div class="pm-card-header"><span class="pm-card-title">📇 Información del cliente</span></div>' +
            '<div style="padding:.3rem 1.1rem 1rem;">' + (filas || '<div class="cp-empty-hint">Sin datos adicionales registrados.</div>') + '</div>' +
        '</div>';
    }

    function referenciasCardHtml(c) {
        function lista(items, detalleKey) {
            if (!items || items.length === 0) return '<div class="cp-empty-hint">No hay referencias registradas.</div>';
            return items.map(function (r) {
                var detalle = [r.telefono, r[detalleKey]].filter(Boolean).join(' — ');
                return '<div class="cp-ref-item"><div class="cp-ref-nombre">' + r.nombre + '</div>' + (detalle ? '<div class="cp-ref-detalle">' + detalle + '</div>' : '') + '</div>';
            }).join('');
        }

        return '<div class="pm-card cp-info-card">' +
            '<div class="pm-card-header"><span class="pm-card-title">👥 Referencias familiares</span></div>' +
            '<div style="padding:.2rem 1.1rem .6rem;">' + lista(c.referencias_familiares, 'parentesco') + '</div>' +
        '</div>' +
        '<div class="pm-card cp-info-card">' +
            '<div class="pm-card-header"><span class="pm-card-title">🤝 Referencias conocidas</span></div>' +
            '<div style="padding:.2rem 1.1rem .6rem;">' + lista(c.referencias_conocidas, 'trabajo') + '</div>' +
        '</div>';
    }

    function documentosCardHtml(c) {
        var docs = [
            [c.dui_foto_frente, 'DUI — Frente'],
            [c.dui_foto_reverso, 'DUI — Reverso'],
            [c.foto_casa, 'Fachada de la casa'],
        ].filter(function (d) { return d[0]; });

        if (docs.length === 0) return '';

        var grid = docs.map(function (d) {
            return '<div><a class="cp-doc-thumb" href="' + d[0] + '" target="_blank" rel="noopener"><img src="' + d[0] + '" loading="lazy" alt="' + d[1] + '"></a><div class="cp-doc-label">' + d[1] + '</div></div>';
        }).join('');

        return '<div class="pm-card cp-info-card">' +
            '<div class="pm-card-header"><span class="pm-card-title">🖼️ Documentos</span></div>' +
            '<div style="padding:.9rem 1.1rem;"><div class="cp-docs-grid">' + grid + '</div></div>' +
        '</div>';
    }

    function historialHtml(historial) {
        var html = '<div class="cp-section-title">📋 Historial de movimiento de ruta</div>';

        if (!historial || historial.length === 0) {
            return html + '<div class="pm-card"><div class="cp-vacio" style="padding:.9rem 1.1rem;">Sin cambios de ruta registrados.</div></div>';
        }

        html += '<div class="pm-card" style="padding:0 1.1rem;">';
        historial.forEach(function (h) {
            html += '<div class="cp-historial-item">' +
                '<strong style="font-size:.85rem;">' + (h.ruta_anterior || 'Sin ruta') + (h.cobrador_anterior ? ' (' + h.cobrador_anterior + ')' : '') + ' → ' + (h.ruta_nueva || 'Sin ruta') + (h.cobrador_nuevo ? ' (' + h.cobrador_nuevo + ')' : '') + '</strong>' +
                '<span style="color:var(--muted-2); font-size:.72rem;">' + h.fecha + ' — ' + h.usuario + '</span>' +
            '</div>';
        });
        html += '</div>';
        return html;
    }

    function ventaCardHtml(v) {
        var colores = estadoColores[v.estado] || ['#f1f5f9', '#475569'];
        var pct = v.total > 0 ? Math.min(100, Math.round((v.monto_pagado / v.total) * 100)) : 0;

        var productosHtml = (v.productos && v.productos.length > 0)
            ? '<div class="cp-productos">' + v.productos.map(function (p) {
                return '<span class="cp-producto-pill"><b>' + p.cantidad + 'x</b> ' + p.nombre + ' — ' + money(p.subtotal) + '</span>';
            }).join('') + '</div>'
            : '';

        var card = '<div class="cr-venta-card">' +
            '<div class="cr-venta-card-header">' +
                '<div><strong>' + v.numero_venta + '</strong> <span style="color:var(--muted-2); font-size:.78rem;">— ' + v.fecha_venta + ' (' + (v.tipo_pago === 'credito' ? 'Crédito' : 'Contado') + ')' + (v.vendedor_nombre ? ' · Vendedor: ' + v.vendedor_nombre : '') + '</span></div>' +
                '<span class="cr-venta-badge" style="background:' + colores[0] + '; color:' + colores[1] + ';">' + (estadoLabels[v.estado] || v.estado) + '</span>' +
            '</div>' +
            productosHtml +
            '<div style="display:flex; gap:1.5rem; font-size:.82rem; flex-wrap:wrap;">' +
                '<div>Total: <strong>' + money(v.total) + '</strong></div>' +
                '<div style="color:#16a34a;">Pagado: <strong>' + money(v.monto_pagado) + '</strong></div>' +
                '<div style="color:' + (v.saldo_pendiente > 0 ? '#dc2626' : '#16a34a') + ';">Saldo: <strong>' + money(v.saldo_pendiente) + '</strong></div>' +
            '</div>' +
            '<div class="cr-venta-cuotas-bar"><div style="width:' + pct + '%; background:#16a34a;"></div></div>';

        if (v.cuotas_resumen) {
            card += '<div style="font-size:.74rem; color:var(--muted); margin-top:.4rem;">' +
                v.cuotas_resumen.cobradas + ' de ' + v.cuotas_resumen.total + ' cuotas cobradas' +
                (v.cuotas_resumen.vencidas > 0 ? ' · <span style="color:#dc2626; font-weight:600;">' + v.cuotas_resumen.vencidas + ' vencidas</span>' : '') +
            '</div>';
        }

        if (v.proxima_cuota) {
            card += '<div style="font-size:.74rem; color:var(--muted-2); margin-top:.2rem;">Próxima cuota: #' + v.proxima_cuota.numero_cuota + '/' + v.proxima_cuota.total_cuotas + ' — vence ' + v.proxima_cuota.fecha_vencimiento.substring(8,10) + '/' + v.proxima_cuota.fecha_vencimiento.substring(5,7) + '/' + v.proxima_cuota.fecha_vencimiento.substring(0,4) + '</div>';
        }

        if (v.pagos.length > 0) {
            card += '<div class="cr-venta-pagos-list">';
            v.pagos.forEach(function (p) {
                var cantidadNota = p.cantidad > 1 ? ' <span style="color:var(--muted-2);">(' + p.cantidad + ' pagos)</span>' : '';
                var reciboNota = p.numero_recibo ? ' <span style="color:var(--muted-2); font-size:.68rem;">· ' + p.numero_recibo + '</span>' : '';
                var reciboBtn = p.numero_recibo
                    ? '<a class="cr-abono-edit" href="' + baseUrl + '/recibo/' + p.numero_recibo + '" target="_blank" rel="noopener" title="Generar recibo">' +
                        '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/><path d="M14 2v6h6"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></svg>' +
                      '</a>'
                    : '';

                if (p.anulado) {
                    var motivoTxt = p.motivo_anulacion ? ' — ' + p.motivo_anulacion : '';
                    card += '<div class="cr-venta-pago-row" style="flex-direction:column; align-items:flex-start; gap:.15rem;">' +
                        '<div style="display:flex; justify-content:space-between; width:100%; align-items:center;">' +
                            '<span style="text-decoration:line-through; color:var(--muted-2);">' + p.fecha + ' — ' + p.metodo_pago + cantidadNota + reciboNota + '</span>' +
                            '<span style="display:inline-flex; align-items:center; gap:.3rem;">' +
                                '<strong style="text-decoration:line-through; color:var(--muted-2);">' + money(p.monto) + '</strong>' +
                                '<span class="cr-venta-badge" style="background:#fee2e2; color:#dc2626;">ANULADO</span>' +
                                reciboBtn +
                            '</span>' +
                        '</div>' +
                        '<span style="color:#dc2626; font-size:.7rem;">Anulado ' + p.anulado_en + (p.anulado_por ? ' por ' + p.anulado_por : '') + motivoTxt + '</span>' +
                    '</div>';
                    return;
                }

                var anularBtn = (esSuperAdmin && p.numero_recibo)
                    ? '<button type="button" class="cr-abono-edit cp-anular-recibo" data-numero-recibo="' + p.numero_recibo + '" data-nombre-cliente="' + c.nombre.replace(/"/g, '&quot;') + '" title="Anular este recibo" style="color:#dc2626;">' +
                        '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.9" y1="4.9" x2="19.1" y2="19.1"/></svg>' +
                      '</button>'
                    : '';

                card += '<div class="cr-venta-pago-row">' +
                    '<span>' + p.fecha + ' — ' + p.metodo_pago + (p.observaciones ? ' (' + p.observaciones + ')' : '') + cantidadNota + reciboNota + '</span>' +
                    '<span style="display:inline-flex; align-items:center; gap:.3rem;">' +
                        '<strong style="color:#16a34a;">' + money(p.monto) + '</strong>' +
                        reciboBtn +
                        '<button type="button" class="cr-abono-edit cp-pago-fecha-edit" data-venta="' + v.id + '" data-fecha-iso="' + p.fecha_iso + '" data-fecha="' + p.fecha + '" data-monto="' + p.monto + '" data-numero-recibo="' + (p.numero_recibo || '') + '" title="Corregir este pago">' +
                            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>' +
                        '</button>' +
                        anularBtn +
                    '</span>' +
                '</div>';
            });
            card += '</div>';
        }

        return card + '</div>';
    }

    function render(data) {
        var c = data.cliente;
        var r = data.resumen;

        var eliminarBtn = esSuperAdmin
            ? '<button type="button" class="cp-eliminar-btn" id="cp-eliminar-cliente" title="Eliminar cliente con toda su gestión">' +
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>' +
                ' Eliminar cliente' +
            '</button>'
            : '';

        document.title = c.nombre + ' — Perfil del cliente';

        // ── Encabezado ──
        var html = '<div class="cp-header-card">' +
            '<div class="cp-avatar">' + iniciales(c.nombre).toUpperCase() + '</div>' +
            '<div class="cp-header-info">' +
                '<div class="cp-nombre">' + c.nombre + (c.codigo_anterior ? '<span class="cp-codigo-badge">Cód. ' + c.codigo_anterior + '</span>' : '') + '</div>' +
                '<div class="cp-header-meta">' +
                    (c.telefono ? '<span>📞 ' + c.telefono + '</span>' : '') +
                    (c.direccion ? '<span>📍 ' + c.direccion + '</span>' : '') +
                    (c.ruta_nombre ? '<span>🚚 ' + c.ruta_nombre + (c.ruta_dia ? ' (' + c.ruta_dia + ')' : '') + '</span>' : '<span class="cp-ruta-warn">⚠ Sin ruta asignada</span>') +
                '</div>' +
            '</div>' + eliminarBtn +
        '</div>';

        // ── Resumen ──
        html += '<div class="cr-stat-grid" style="display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-bottom:0;">' +
            statCardHtml('🧾', '#dbeafe', '#1d4ed8', 'Ventas', r.total_ventas) +
            statCardHtml('💵', '#dcfce7', '#16a34a', 'Pagado', money(r.total_pagado)) +
            statCardHtml('⏳', '#fee2e2', '#dc2626', 'Pendiente', money(r.total_pendiente)) +
        '</div>';

        // ── Layout 2 columnas ──
        html += '<div class="cp-layout" style="margin-top:1.25rem;"><div class="cp-main">';

        html += '<div class="cp-section-title">🛒 Ventas</div>';

        if (data.ventas.length === 0) {
            html += '<div class="cp-vacio">Este cliente no tiene ventas registradas.</div>';
        }

        var gruposPorFecha = {};
        var ordenFechas = [];
        data.ventas.forEach(function (v) {
            if (!gruposPorFecha[v.fecha_venta]) {
                gruposPorFecha[v.fecha_venta] = [];
                ordenFechas.push(v.fecha_venta);
            }
            gruposPorFecha[v.fecha_venta].push(v);
        });

        ordenFechas.forEach(function (fecha) {
            var ventasDelDia = gruposPorFecha[fecha];
            if (ventasDelDia.length > 1) {
                html += '<div class="cr-venta-fecha-group">' +
                    '<div class="cr-venta-fecha-group-label">🔗 ' + ventasDelDia.length + ' ventas del ' + fecha + '</div>' +
                    ventasDelDia.map(ventaCardHtml).join('') +
                '</div>';
            } else {
                html += ventaCardHtml(ventasDelDia[0]);
            }
        });

        html += historialHtml(data.historial_ruta);

        html += '</div><div class="cp-sidebar">' +
            infoCardHtml(c) +
            referenciasCardHtml(c) +
            documentosCardHtml(c) +
        '</div></div>';

        body.innerHTML = html;

        var eliminarBtnEl = document.getElementById('cp-eliminar-cliente');
        if (eliminarBtnEl) {
            eliminarBtnEl.addEventListener('click', function () {
                var advertencia = '¿ELIMINAR a ' + c.nombre + ' con TODA su gestión?\n\n' +
                    'Se borrarán ' + r.total_ventas + ' venta(s), sus pagos y cuotas' +
                    (r.total_pendiente > 0 ? ' (saldo pendiente: ' + money(r.total_pendiente) + ')' : '') + '.\n\n' +
                    'Esta acción NO se puede deshacer.';
                if (!window.confirm(advertencia)) return;

                var motivo = window.prompt('Motivo de la eliminación (opcional):', '') || '';
                var password = window.prompt('Confirma tu contraseña para eliminar a ' + c.nombre + ':');
                if (password === null) return;
                if (password.trim() === '') {
                    showToast('Debes ingresar tu contraseña para confirmar.');
                    return;
                }

                fetch(baseUrl + '/clientes/' + clienteId, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ password: password, motivo: motivo }),
                }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
                  .then(function (res) {
                    if (res.ok) {
                        window.location.href = '{{ route('clientes-ruta.index', $tenant) }}';
                    } else {
                        var msg = res.body.mensaje
                            || (res.body.errors && res.body.errors.password && res.body.errors.password[0])
                            || 'No se pudo eliminar el cliente.';
                        showToast(msg);
                    }
                });
            });
        }

        body.querySelectorAll('.cp-pago-fecha-edit').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var ventaId = this.dataset.venta;
                var fechaIso = this.dataset.fechaIso;
                var fecha = this.dataset.fecha;
                var montoActual = this.dataset.monto;
                var numeroRecibo = this.dataset.numeroRecibo;

                var nuevo = window.prompt('Monto total del recibo del ' + fecha + ':', montoActual);
                if (nuevo === null) return;
                nuevo = nuevo.replace(',', '.').trim();
                if (nuevo === '' || isNaN(nuevo) || Number(nuevo) < 0) {
                    showToast('Monto inválido.');
                    return;
                }

                fetch(baseUrl + '/clientes/' + clienteId + '/pago-fecha', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ venta_id: Number(ventaId), fecha_pago: fechaIso, numero_recibo: numeroRecibo || null, monto: Number(nuevo) }),
                }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
                  .then(function (res) {
                    showToast(res.body.mensaje || (res.ok ? 'Actualizado.' : 'Error.'));
                    if (res.ok) cargar();
                });
            });
        });

        body.querySelectorAll('.cp-anular-recibo').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var numeroRecibo = this.dataset.numeroRecibo;
                var nombreCliente = this.dataset.nombreCliente;

                if (!window.confirm('¿ANULAR el recibo ' + numeroRecibo + ' de ' + nombreCliente + '?\n\nEl registro no se borra, pero deja de contar en el saldo y las cuotas de la venta — como si ese pago no se hubiera hecho.\n\nEsta acción se puede ver siempre en el historial, pero no se puede deshacer desde aquí.')) return;

                var motivo = window.prompt('Motivo de la anulación (obligatorio):', '');
                if (motivo === null) return;
                motivo = motivo.trim();
                if (motivo === '') {
                    showToast('Debes indicar el motivo de la anulación.');
                    return;
                }

                var password = window.prompt('Confirma tu contraseña para anular este recibo:');
                if (password === null) return;
                if (password.trim() === '') {
                    showToast('Debes ingresar tu contraseña para confirmar.');
                    return;
                }

                fetch(baseUrl + '/recibo/' + numeroRecibo + '/anular', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ password: password, motivo: motivo }),
                }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
                  .then(function (res) {
                    if (res.ok) {
                        showToast(res.body.mensaje || 'Recibo anulado.');
                        cargar();
                    } else {
                        var msg = res.body.mensaje
                            || (res.body.errors && res.body.errors.password && res.body.errors.password[0])
                            || (res.body.errors && res.body.errors.motivo && res.body.errors.motivo[0])
                            || 'No se pudo anular el recibo.';
                        showToast(msg);
                    }
                });
            });
        });
    }

    cargar();
})();
</script>
@endsection
