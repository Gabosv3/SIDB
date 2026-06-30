@extends('pos._layout')

@section('page-title', 'Clientes por Ruta')

@section('styles')
<style>
    /* ── Filtros ── */
    .cr-filter-bar   { display:flex; flex-wrap:wrap; align-items:flex-end; gap:.75rem; margin-bottom:1.25rem; }
    .cr-filter-group { display:flex; flex-direction:column; gap:.35rem; }
    .cr-filter-label { font-size:.7rem; font-weight:600; color:var(--muted); }
    .cr-filter-input { border:1px solid var(--border); border-radius:.625rem; padding:.55rem .8rem; font-size:.84rem; color:var(--text-2); background:var(--card); outline:none; min-width:240px; transition:border-color .15s, box-shadow .15s; }
    .cr-filter-input:focus { border-color:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,.12); }
    .cr-search-wrap { position:relative; flex:1; min-width:220px; }
    .cr-search-wrap .cr-filter-input { width:100%; padding-left:2.1rem; }
    .cr-search-icon { position:absolute; left:.75rem; bottom:.62rem; color:var(--muted-2); pointer-events:none; }

    /* ── Stat cards ── */
    .cr-stat-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-bottom:1.25rem; }
    @media (max-width:760px) { .cr-stat-grid { grid-template-columns:repeat(2,1fr); gap:.75rem; } }
    @media (max-width:480px) { .cr-stat-grid { grid-template-columns:1fr; } }

    /* ── Tabla: scroll horizontal con columnas clave fijas ── */
    .pm-table-wrap { -webkit-overflow-scrolling:touch; }
    .cr-sticky-1 { position:sticky; left:0; z-index:2; background:var(--card); width:36px; min-width:36px; max-width:36px; }
    .cr-sticky-2 { position:sticky; left:36px; z-index:2; background:var(--card); width:34px; min-width:34px; max-width:34px; }
    .cr-sticky-3 { position:sticky; left:70px; z-index:2; background:var(--card); box-shadow:4px 0 6px -4px rgba(0,0,0,.12); }
    .pm-thead th.cr-sticky-1, .pm-thead th.cr-sticky-2, .pm-thead th.cr-sticky-3 { background:var(--subtle); z-index:3; }
    .pm-tr:hover .cr-sticky-1, .pm-tr:hover .cr-sticky-2, .pm-tr:hover .cr-sticky-3 { background:var(--subtle); }

    .cr-handle { cursor:grab; color:var(--muted-2); touch-action:none; display:inline-flex; padding:.35rem; border-radius:.4rem; }
    .cr-handle:hover { background:var(--subtle); }
    .cr-handle:active { cursor:grabbing; }
    .cr-row.sortable-ghost { opacity:.35; }
    .cr-row.sortable-chosen { background:var(--subtle); }

    .cr-orden-badge { display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:50%; background:var(--subtle); border:1px solid var(--border); font-size:.7rem; font-weight:700; color:var(--muted); flex-shrink:0; }

    .cr-saldo-pos { color:#dc2626; font-weight:700; }
    .cr-saldo-zero { color:#16a34a; font-weight:700; }

    .cr-pill { display:inline-flex; align-items:center; justify-content:center; min-width:22px; height:20px; padding:0 .4rem; border-radius:9999px; font-size:.7rem; font-weight:700; background:var(--subtle); color:var(--muted); }
    .cr-pill.has { background:#fee2e2; color:#dc2626; }
    html.dark .cr-pill.has { background:rgba(220,38,38,.18); color:#fca5a5; }

    .cr-ruta-select { border:1px solid var(--border); border-radius:.5rem; padding:.4rem .6rem; font-size:.78rem; color:var(--text-2); background:var(--card); outline:none; max-width:190px; }
    .cr-ruta-select:focus { border-color:#10b981; }

    .cr-warn-badge { display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .55rem; border-radius:9999px; font-size:.66rem; font-weight:700; background:#fef9c3; color:#854d0e; white-space:nowrap; }
    html.dark .cr-warn-badge { background:rgba(202,138,4,.18); color:#fde68a; }

    .cr-abono-wrap { display:inline-flex; align-items:center; gap:.4rem; }
    .cr-abono-edit { background:none; border:1px solid transparent; cursor:pointer; color:var(--muted-2); padding:.3rem; border-radius:.4rem; display:inline-flex; }
    .cr-abono-edit:hover { background:var(--subtle); border-color:var(--border); color:#10b981; }

    .cr-save-toast {
        position:fixed; bottom:1.25rem; right:1.25rem; background:#16a34a; color:#fff;
        padding:.6rem 1rem; border-radius:.625rem; font-size:.8rem; font-weight:600;
        box-shadow:0 4px 16px rgba(0,0,0,.18); opacity:0; transform:translateY(8px);
        transition:all .2s; pointer-events:none; z-index:500; max-width:calc(100vw - 2.5rem);
    }
    .cr-save-toast.show { opacity:1; transform:translateY(0); }

    /* ── Botón importar ── */
    .cr-import-btn { display:inline-flex; align-items:center; gap:.5rem; background:#10b981; color:#fff; border:none; border-radius:.625rem; padding:.6rem 1.1rem; font-size:.82rem; font-weight:600; cursor:pointer; transition:background .15s; }
    .cr-import-btn:hover { background:#059669; }
    .cr-import-btn-secundario { background:var(--subtle); color:var(--text-2); border:1px solid var(--border); border-radius:.625rem; padding:.6rem 1.1rem; font-size:.82rem; font-weight:600; cursor:pointer; }
    .cr-import-btn-secundario:hover { background:var(--border); }

    /* ── Modal ── */
    .cr-modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; align-items:center; justify-content:center; padding:1rem; }
    .cr-modal-overlay.show { display:flex; }
    .cr-modal { background:var(--card); border-radius:1rem; width:100%; max-width:620px; max-height:90vh; display:flex; flex-direction:column; box-shadow:0 20px 60px rgba(0,0,0,.3); }
    .cr-modal-header { display:flex; align-items:center; justify-content:space-between; padding:1.1rem 1.4rem; border-bottom:1px solid var(--border); font-weight:700; font-size:.95rem; color:var(--text); }
    .cr-modal-close { background:none; border:none; font-size:1.5rem; line-height:1; cursor:pointer; color:var(--muted); padding:0; }
    .cr-modal-close:hover { color:var(--text); }
    .cr-modal-body { padding:1.4rem; overflow-y:auto; }

    .cr-import-hint { font-size:.82rem; color:var(--muted); margin-bottom:.75rem; line-height:1.5; }
    .cr-import-error { color:#dc2626; font-size:.78rem; margin-top:.6rem; min-height:1em; }
    #cr-import-file { width:100%; padding:.6rem; border:1px dashed var(--border); border-radius:.625rem; font-size:.82rem; background:var(--subtle); color:var(--text-2); }

    .cr-import-mapeo-row { display:grid; grid-template-columns:1fr 1fr; gap:.6rem; align-items:center; margin-bottom:.6rem; }
    .cr-import-mapeo-row label { font-size:.8rem; font-weight:600; color:var(--text-2); }
    .cr-import-mapeo-row select { width:100%; }

    .cr-import-divider { height:1px; background:var(--border); margin:1.1rem 0; }

    .cr-import-ruta-modo { display:flex; gap:1.25rem; margin-bottom:1rem; font-size:.84rem; color:var(--text-2); }
    .cr-import-ruta-modo label { display:flex; align-items:center; gap:.4rem; cursor:pointer; }

    .cr-import-ruta-form { display:flex; flex-direction:column; gap:.6rem; }
    .cr-import-field label { display:block; font-size:.72rem; font-weight:600; color:var(--muted); margin-bottom:.25rem; }
    .cr-import-field select, .cr-import-field input { width:100%; }

    .cr-import-actions { display:flex; justify-content:space-between; gap:.75rem; margin-top:1.25rem; }

    .cr-import-resultado-icon { width:56px; height:56px; border-radius:50%; background:#dcfce7; color:#16a34a; font-size:1.75rem; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; }
    html.dark .cr-import-resultado-icon { background:rgba(34,197,94,.15); }
    .cr-import-resultado-stats { display:grid; grid-template-columns:repeat(2,1fr); gap:.6rem; margin-top:1rem; }
    .cr-import-resultado-stat { background:var(--subtle); border-radius:.625rem; padding:.7rem; text-align:center; }
    .cr-import-resultado-stat-num { font-size:1.25rem; font-weight:800; color:var(--text); }
    .cr-import-resultado-stat-label { font-size:.66rem; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; margin-top:.15rem; }

    /* ── Responsive: telefono ── */
    @media (max-width:768px) {
        .pm-body { padding:.875rem; }
        .pm-page-header h1 { font-size:1.15rem; }
        .pm-page-header p { font-size:.76rem; }

        .cr-filter-bar { gap:.6rem; }
        .cr-filter-group, .cr-search-wrap { width:100%; min-width:0; flex:1 1 100%; }
        .cr-filter-input { min-width:0; width:100%; font-size:.9rem; padding:.65rem .8rem; }
        .cr-search-wrap .cr-filter-input { padding-left:2.1rem; }

        .pm-card-header { padding:.75rem .9rem; }
        .pm-card-title { font-size:.74rem; }

        .pm-td, .pm-thead th { padding:.55rem .6rem; font-size:.78rem; }
        .pm-td:first-child, .pm-thead th:first-child { padding-left:.7rem; }

        .cr-ruta-select { max-width:150px; font-size:.74rem; padding:.45rem .5rem; }
        .cr-handle { padding:.45rem; }
        .cr-handle svg, .cr-handle { font-size:1.05rem; }
        .cr-abono-edit { padding:.4rem; }
        .cr-abono-edit svg { width:15px; height:15px; }

        .cr-save-toast { left:.875rem; right:.875rem; bottom:.875rem; text-align:center; max-width:none; }

        .pm-page-header { flex-direction:column; }
        .cr-import-btn { width:100%; justify-content:center; }
        .cr-modal { max-height:95vh; }
        .cr-modal-body { padding:1rem; }
        .cr-import-mapeo-row { grid-template-columns:1fr; gap:.25rem; }
        .cr-import-ruta-modo { flex-direction:column; gap:.5rem; }
        .cr-import-actions { flex-direction:column-reverse; }
        .cr-import-actions button { width:100%; }
    }
</style>
@endsection

@section('content')
<div class="pm-page-header" style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
    <div>
        <h1>Clientes por Ruta</h1>
        <p>Ordena la secuencia de visita y revisa que cada cliente esté en la ruta correcta.</p>
    </div>
    <button type="button" class="cr-import-btn" id="cr-abrir-importar">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Importar Excel
    </button>
</div>

<div class="cr-filter-bar">
    <div class="cr-filter-group">
        <label class="cr-filter-label">Ruta de cobro</label>
        <select id="cr-ruta-filter" class="cr-filter-input">
            @foreach($rutas as $r)
                <option value="{{ $r->id }}" {{ (string) $rutaId === (string) $r->id ? 'selected' : '' }}>
                    {{ $r->nombre }} — {{ ucfirst($r->dia_semana) }} ({{ $r->clientes_count }})
                </option>
            @endforeach
            <option value="sin_ruta" {{ $rutaId === 'sin_ruta' ? 'selected' : '' }}>
                ⚠ Sin ruta asignada ({{ $sinRuta }})
            </option>
            <option value="todos" {{ $rutaId === 'todos' ? 'selected' : '' }}>
                Todos los clientes (todas las rutas)
            </option>
        </select>
    </div>
    <div class="cr-filter-group cr-search-wrap">
        <label class="cr-filter-label">Buscar por código o nombre</label>
        <span class="cr-search-icon">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </span>
        <input type="text" id="cr-buscar" class="cr-filter-input" placeholder="Ej. 7304 o nombre del cliente...">
    </div>
</div>

<div class="cr-stat-grid">
    <div class="pm-card">
        <div class="pm-stat">
            <div class="pm-stat-icon" style="background:#dbeafe;color:#1d4ed8;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <div class="pm-stat-label">Clientes en la ruta</div>
                <div class="pm-stat-num" id="cr-total-clientes">—</div>
            </div>
        </div>
    </div>
    <div class="pm-card">
        <div class="pm-stat">
            <div class="pm-stat-icon" style="background:#fee2e2;color:#dc2626;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div>
                <div class="pm-stat-label">Saldo total pendiente</div>
                <div class="pm-stat-num" id="cr-total-saldo">—</div>
            </div>
        </div>
    </div>
    <div class="pm-card">
        <div class="pm-stat">
            <div class="pm-stat-icon" style="background:#fef9c3;color:#a16207;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 10V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h6"/><circle cx="17" cy="17" r="4"/><path d="M17 15.5v1.5l1 1"/></svg>
            </div>
            <div>
                <div class="pm-stat-label">Sin coordenadas GPS</div>
                <div class="pm-stat-num" id="cr-sin-gps">—</div>
            </div>
        </div>
    </div>
</div>

<div class="pm-card">
    <div class="pm-card-header">
        <span class="pm-card-title">Listado — arrastra el ícono para reordenar</span>
        <span class="pm-card-link" id="cr-refresh-link" style="cursor:pointer;">↻ Actualizar</span>
    </div>
    <div class="pm-table-wrap">
        <table class="pm-table">
            <thead class="pm-thead">
                <tr>
                    <th class="cr-sticky-1"></th>
                    <th class="cr-sticky-2">#</th>
                    <th class="cr-sticky-3" style="min-width:160px;">Cliente</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Saldo</th>
                    <th>Precio</th>
                    <th>Abono inicial</th>
                    <th>Ventas</th>
                    <th>Ruta asignada</th>
                </tr>
            </thead>
            <tbody id="cr-tbody">
                <tr><td class="pm-td" colspan="10">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="cr-save-toast" id="cr-toast">Guardado</div>

<div class="cr-modal-overlay" id="cr-import-overlay">
    <div class="cr-modal">
        <div class="cr-modal-header">
            <span>Importar clientes desde Excel</span>
            <button type="button" class="cr-modal-close" id="cr-import-close">&times;</button>
        </div>
        <div class="cr-modal-body" id="cr-import-body">

            <div id="cr-import-step-upload">
                <p class="cr-import-hint">Sube el archivo Excel (.xlsx, .xls o .csv) con los clientes de la ruta.</p>
                <input type="file" id="cr-import-file" accept=".xlsx,.xls,.csv">
                <button type="button" class="cr-import-btn" id="cr-import-subir" style="margin-top:1rem;">Subir y previsualizar</button>
                <p class="cr-import-error" id="cr-import-upload-error"></p>
            </div>

            <div id="cr-import-step-mapeo" style="display:none;">
                <p class="cr-import-hint">Detectamos <strong id="cr-import-total-filas">0</strong> filas de datos. Indica qué columna corresponde a cada campo (los marcados con * son obligatorios):</p>

                <div id="cr-import-mapeo-campos"></div>

                <div class="cr-import-divider"></div>

                <p class="cr-import-hint"><strong>¿A qué ruta van estos clientes?</strong></p>
                <div class="cr-import-ruta-modo">
                    <label><input type="radio" name="cr-ruta-modo" value="nueva" checked> Crear ruta nueva</label>
                    <label><input type="radio" name="cr-ruta-modo" value="existente"> Agregar a ruta existente</label>
                </div>

                <div id="cr-import-ruta-nueva" class="cr-import-ruta-form">
                    <div class="cr-import-field">
                        <label>Nombre de la ruta</label>
                        <input type="text" id="cr-import-ruta-nombre" class="cr-filter-input" placeholder="Ej. Zona 2 Ruta día Jueves">
                    </div>
                    <div class="cr-import-field">
                        <label>Día de cobro</label>
                        <select id="cr-import-ruta-dia" class="cr-filter-input">
                            <option value="lunes">Lunes</option>
                            <option value="martes">Martes</option>
                            <option value="miércoles">Miércoles</option>
                            <option value="jueves">Jueves</option>
                            <option value="viernes">Viernes</option>
                            <option value="sábado">Sábado</option>
                            <option value="domingo">Domingo</option>
                        </select>
                    </div>
                    <div class="cr-import-field">
                        <label>Cobrador</label>
                        <select id="cr-import-ruta-cobrador" class="cr-filter-input">
                            @foreach($cobradores as $c)
                                <option value="{{ $c->id }}">{{ trim($c->nombre.' '.$c->apellido) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="cr-import-ruta-existente" class="cr-import-ruta-form" style="display:none;">
                    <div class="cr-import-field">
                        <label>Ruta</label>
                        <select id="cr-import-ruta-existente-select" class="cr-filter-input">
                            @foreach($rutas as $r)
                                <option value="{{ $r->id }}">{{ $r->nombre }} — {{ ucfirst($r->dia_semana) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <p class="cr-import-error" id="cr-import-mapeo-error"></p>

                <div class="cr-import-actions">
                    <button type="button" class="cr-import-btn-secundario" id="cr-import-volver">← Volver</button>
                    <button type="button" class="cr-import-btn" id="cr-import-confirmar">Importar</button>
                </div>
            </div>

            <div id="cr-import-step-resultado" style="display:none;">
                <div class="cr-import-resultado-icon">✓</div>
                <p id="cr-import-resultado-msg" style="text-align:center; font-weight:600;"></p>
                <div class="cr-import-resultado-stats" id="cr-import-resultado-stats"></div>
                <button type="button" class="cr-import-btn" id="cr-import-cerrar-final" style="width:100%; margin-top:1rem;">Listo</button>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    var tenant = {{ (int) $tenant }};
    var baseUrl = '/clientes-ruta/' + tenant;
    var rutaSelect = document.getElementById('cr-ruta-filter');
    var buscarInput = document.getElementById('cr-buscar');
    var tbody = document.getElementById('cr-tbody');
    var toast = document.getElementById('cr-toast');
    var rutasDisponibles = @json($rutas->map(fn($r) => ['id' => $r->id, 'nombre' => $r->nombre])->values());
    var sortable = null;
    var buscarTimeout = null;

    function showToast(msg) {
        toast.textContent = msg;
        toast.classList.add('show');
        setTimeout(function () { toast.classList.remove('show'); }, 1800);
    }

    function money(n) {
        return '$' + Number(n).toLocaleString('es-SV', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function editIcon() {
        return '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>';
    }

    function campoEditBtn(clienteId, campo, valorCrudo, label) {
        var v = (valorCrudo === null || valorCrudo === undefined) ? '' : String(valorCrudo);
        return '<button type="button" class="cr-abono-edit cr-campo-edit" data-cliente="' + clienteId + '" data-campo="' + campo + '" data-valor="' + v.replace(/"/g, '&quot;') + '" data-label="' + label + '" title="Editar ' + label.toLowerCase() + '">' + editIcon() + '</button>';
    }

    function rutaOptionsHtml(clienteRutaId) {
        var html = '<option value="">— Sin ruta —</option>';
        rutasDisponibles.forEach(function (r) {
            html += '<option value="' + r.id + '"' + (String(clienteRutaId) === String(r.id) ? ' selected' : '') + '>' + r.nombre + '</option>';
        });
        return html;
    }

    function render(data) {
        document.getElementById('cr-total-clientes').textContent = data.total_clientes;
        document.getElementById('cr-total-saldo').textContent = money(data.total_saldo);
        document.getElementById('cr-sin-gps').textContent = data.clientes.filter(function (c) { return !c.tiene_ubicacion; }).length;

        if (data.clientes.length === 0) {
            tbody.innerHTML = '<tr><td class="pm-td" colspan="10">No hay clientes en esta selección.</td></tr>';
            return;
        }

        var modo = rutaSelect.value;
        var rows = data.clientes.map(function (c, idx) {
            var saldoClass = c.saldo > 0 ? 'cr-saldo-pos' : 'cr-saldo-zero';
            var dirWarn = !c.direccion ? '<span class="cr-warn-badge" title="Sin dirección registrada">⚠</span>' : '';
            var codigoHtml = c.codigo_anterior ? ' <span style="color:var(--muted-2);">(' + c.codigo_anterior + ')</span>' : '';
            var rutaActualHtml = modo === 'todos' ? '<div style="font-size:.68rem; color:var(--muted-2); margin-top:.15rem;">' + (c.ruta_nombre || 'Sin ruta') + '</div>' : '';

            var abonoHtml;
            var precioHtml;
            var nombreAttr = c.nombre.replace(/"/g, '&quot;');
            if (c.ventas_credito && c.ventas_credito.length > 0) {
                precioHtml = c.ventas_credito.map(function (v, i) {
                    var etiqueta = c.ventas_credito.length > 1 ? '<span style="color:var(--muted-2); font-size:.68rem;">V' + (i + 1) + ': </span>' : '';
                    return '<div class="cr-abono-wrap" style="margin-bottom:2px;">' +
                        etiqueta +
                        '<span>' + money(v.total) + '</span>' +
                        '<button type="button" class="cr-abono-edit cr-precio-edit" data-cliente="' + c.id + '" data-venta="' + v.venta_id + '" data-total="' + v.total + '" data-pagado="' + (v.abono_inicial || 0) + '" data-nombre="' + nombreAttr + '" title="Editar precio de la venta">' +
                            '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>' +
                        '</button>' +
                    '</div>';
                }).join('');

                abonoHtml = c.ventas_credito.map(function (v, i) {
                    var etiqueta = c.ventas_credito.length > 1 ? '<span style="color:var(--muted-2); font-size:.68rem;">V' + (i + 1) + ': </span>' : '';
                    return '<div class="cr-abono-wrap" style="margin-bottom:2px;">' +
                        etiqueta +
                        '<span>' + (v.abono_inicial !== null ? money(v.abono_inicial) : '—') + '</span>' +
                        '<button type="button" class="cr-abono-edit cr-venta-edit" data-cliente="' + c.id + '" data-venta="' + v.venta_id + '" data-monto="' + (v.abono_inicial || 0) + '" data-total="' + v.total + '" data-nombre="' + nombreAttr + '" title="Editar abono inicial">' +
                            '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>' +
                        '</button>' +
                    '</div>';
                }).join('');
            } else {
                precioHtml = '<span style="color:var(--muted-2);">—</span>';
                abonoHtml = '<span style="color:var(--muted-2);">— sin crédito —</span>';
            }

            var ventasClass = c.ventas_pendientes > 0 ? 'cr-pill has' : 'cr-pill';

            return '' +
                '<tr class="pm-tr cr-row" data-id="' + c.id + '">' +
                    '<td class="pm-td cr-sticky-1"><span class="cr-handle">⠿⠿</span></td>' +
                    '<td class="pm-td cr-sticky-2"><span class="cr-orden-badge">' + (idx + 1) + '</span></td>' +
                    '<td class="pm-td cr-sticky-3"><div class="cr-abono-wrap"><strong>' + c.nombre + '</strong>' + codigoHtml + campoEditBtn(c.id, 'nombre', c.nombre, 'Nombre') + '</div></td>' +
                    '<td class="pm-td"><div class="cr-abono-wrap"><span>' + (c.telefono || '—') + '</span>' + campoEditBtn(c.id, 'telefono', c.telefono, 'Teléfono') + '</div></td>' +
                    '<td class="pm-td"><div class="cr-abono-wrap"><span>' + (c.direccion || '—') + '</span> ' + dirWarn + campoEditBtn(c.id, 'direccion', c.direccion_raw, 'Dirección') + '</div></td>' +
                    '<td class="pm-td"><div class="cr-abono-wrap"><span class="' + saldoClass + '">' + money(c.saldo) + '</span>' + campoEditBtn(c.id, 'saldo', c.saldo, 'Saldo') + '</div></td>' +
                    '<td class="pm-td">' + precioHtml + '</td>' +
                    '<td class="pm-td">' + abonoHtml + '</td>' +
                    '<td class="pm-td"><span class="' + ventasClass + '">' + c.ventas_pendientes + '</span></td>' +
                    '<td class="pm-td"><select class="cr-ruta-select" data-id="' + c.id + '">' + rutaOptionsHtml(c.ruta_cobro_id) + '</select>' + rutaActualHtml + '</td>' +
                '</tr>';
        }).join('');

        tbody.innerHTML = rows;

        tbody.querySelectorAll('.cr-campo-edit').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var clienteId = this.dataset.cliente;
                var campo = this.dataset.campo;
                var valorActual = this.dataset.valor;
                var label = this.dataset.label;
                var nuevo = window.prompt(label + ':', valorActual);
                if (nuevo === null) return;
                nuevo = nuevo.trim();
                if (campo === 'saldo') {
                    nuevo = nuevo.replace(',', '.');
                    if (nuevo === '' || isNaN(nuevo) || Number(nuevo) < 0) {
                        showToast('Monto inválido.');
                        return;
                    }
                } else if (nuevo === '') {
                    showToast('El valor no puede quedar vacío.');
                    return;
                }
                fetch(baseUrl + '/clientes/' + clienteId + '/campo', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ campo: campo, valor: nuevo }),
                }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
                  .then(function (res) {
                    showToast(res.body.mensaje || (res.ok ? 'Actualizado.' : 'Error.'));
                    if (res.ok) cargar();
                });
            });
        });

        tbody.querySelectorAll('.cr-venta-edit').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var clienteId = this.dataset.cliente;
                var ventaId = this.dataset.venta;
                var montoActual = this.dataset.monto;
                var total = this.dataset.total;
                var nombre = this.dataset.nombre;
                var nuevo = window.prompt('Abono inicial de ' + nombre + ' (venta de ' + money(total) + '):', montoActual);
                if (nuevo === null) return;
                nuevo = nuevo.replace(',', '.').trim();
                if (nuevo === '' || isNaN(nuevo) || Number(nuevo) < 0) {
                    showToast('Monto inválido.');
                    return;
                }
                fetch(baseUrl + '/clientes/' + clienteId + '/abono-inicial', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ venta_id: Number(ventaId), monto: Number(nuevo) }),
                }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
                  .then(function (res) {
                    showToast(res.body.mensaje || (res.ok ? 'Actualizado.' : 'Error.'));
                    if (res.ok) cargar();
                });
            });
        });

        tbody.querySelectorAll('.cr-precio-edit').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var clienteId = this.dataset.cliente;
                var ventaId = this.dataset.venta;
                var totalActual = this.dataset.total;
                var pagado = this.dataset.pagado;
                var nombre = this.dataset.nombre;
                var nuevo = window.prompt('Precio de la venta de ' + nombre + ' (ya pagado: ' + money(pagado) + '):', totalActual);
                if (nuevo === null) return;
                nuevo = nuevo.replace(',', '.').trim();
                if (nuevo === '' || isNaN(nuevo) || Number(nuevo) <= 0) {
                    showToast('Precio inválido.');
                    return;
                }
                fetch(baseUrl + '/clientes/' + clienteId + '/precio-venta', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ venta_id: Number(ventaId), total: Number(nuevo) }),
                }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
                  .then(function (res) {
                    showToast(res.body.mensaje || (res.ok ? 'Actualizado.' : 'Error.'));
                    if (res.ok) cargar();
                });
            });
        });

        tbody.querySelectorAll('.cr-ruta-select').forEach(function (sel) {
            sel.addEventListener('change', function () {
                var clienteId = this.dataset.id;
                var nuevaRuta = this.value || null;
                fetch(baseUrl + '/clientes/' + clienteId + '/ruta', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ ruta_cobro_id: nuevaRuta }),
                }).then(function (r) { return r.json(); }).then(function () {
                    showToast('Cliente movido de ruta.');
                    cargar();
                });
            });
        });

        if (sortable) sortable.destroy();
        if (modo !== 'todos') {
            sortable = Sortable.create(tbody, {
                handle: '.cr-handle',
                animation: 150,
                onEnd: function () {
                    var ids = Array.from(tbody.querySelectorAll('.cr-row')).map(function (tr) { return tr.dataset.id; });
                    tbody.querySelectorAll('.cr-orden-badge').forEach(function (b, i) { b.textContent = i + 1; });
                    fetch(baseUrl + '/reordenar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ orden: ids }),
                    }).then(function (r) { return r.json(); }).then(function () {
                        showToast('Orden guardado.');
                    });
                },
            });
        } else {
            tbody.querySelectorAll('.cr-handle').forEach(function (h) { h.style.visibility = 'hidden'; });
        }
    }

    function cargar() {
        var rutaId = rutaSelect.value;
        var buscar = buscarInput.value.trim();
        var url = baseUrl + '/data?ruta_cobro_id=' + encodeURIComponent(rutaId);
        if (buscar !== '') url += '&buscar=' + encodeURIComponent(buscar);
        fetch(url)
            .then(function (r) { return r.json(); })
            .then(render);
    }

    rutaSelect.addEventListener('change', function () {
        var url = new URL(window.location);
        url.searchParams.set('ruta_cobro_id', rutaSelect.value);
        window.history.replaceState({}, '', url);
        cargar();
    });

    buscarInput.addEventListener('input', function () {
        clearTimeout(buscarTimeout);
        buscarTimeout = setTimeout(cargar, 300);
    });

    document.getElementById('cr-refresh-link').addEventListener('click', cargar);

    // ── Importar Excel ───────────────────────────────────────────────────
    var camposImportacion = @json($camposImportacion);
    var overlay = document.getElementById('cr-import-overlay');
    var stepUpload = document.getElementById('cr-import-step-upload');
    var stepMapeo = document.getElementById('cr-import-step-mapeo');
    var stepResultado = document.getElementById('cr-import-step-resultado');
    var importToken = null;

    function resetImportModal() {
        stepUpload.style.display = '';
        stepMapeo.style.display = 'none';
        stepResultado.style.display = 'none';
        document.getElementById('cr-import-file').value = '';
        document.getElementById('cr-import-upload-error').textContent = '';
        document.getElementById('cr-import-mapeo-error').textContent = '';
        importToken = null;
    }

    function abrirImportModal() {
        resetImportModal();
        overlay.classList.add('show');
    }
    function cerrarImportModal() {
        overlay.classList.remove('show');
    }

    document.getElementById('cr-abrir-importar').addEventListener('click', abrirImportModal);
    document.getElementById('cr-import-close').addEventListener('click', cerrarImportModal);
    document.getElementById('cr-import-cerrar-final').addEventListener('click', function () {
        cerrarImportModal();
        cargar();
    });
    overlay.addEventListener('click', function (e) { if (e.target === overlay) cerrarImportModal(); });

    document.getElementById('cr-import-subir').addEventListener('click', function () {
        var fileInput = document.getElementById('cr-import-file');
        var errorEl = document.getElementById('cr-import-upload-error');
        errorEl.textContent = '';

        if (!fileInput.files.length) {
            errorEl.textContent = 'Selecciona un archivo primero.';
            return;
        }

        var formData = new FormData();
        formData.append('archivo', fileInput.files[0]);

        var btn = this;
        btn.disabled = true;
        btn.textContent = 'Subiendo...';

        fetch(baseUrl + '/importar/preview', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: formData,
        })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
        .then(function (res) {
            btn.disabled = false;
            btn.textContent = 'Subir y previsualizar';
            if (!res.ok) {
                errorEl.textContent = res.body.mensaje || 'Error al leer el archivo.';
                return;
            }
            importToken = res.body.token;
            document.getElementById('cr-import-total-filas').textContent = res.body.total_filas_detectadas;
            renderMapeo(res.body.encabezados, camposImportacion);
            stepUpload.style.display = 'none';
            stepMapeo.style.display = '';
        })
        .catch(function () {
            btn.disabled = false;
            btn.textContent = 'Subir y previsualizar';
            errorEl.textContent = 'Error de conexión al subir el archivo.';
        });
    });

    function renderMapeo(encabezados, campos) {
        var cont = document.getElementById('cr-import-mapeo-campos');
        var requeridos = ['nombre', 'valor_total'];
        var html = '';
        Object.keys(campos).forEach(function (clave) {
            var esRequerido = requeridos.indexOf(clave) !== -1;
            html += '<div class="cr-import-mapeo-row">' +
                '<label>' + campos[clave] + (esRequerido ? ' *' : '') + '</label>' +
                '<select class="cr-filter-input cr-import-mapeo-select" data-campo="' + clave + '">' +
                    '<option value="">— No usar —</option>' +
                    encabezados.map(function (h) {
                        var autoSel = h.toLowerCase().indexOf(clave.split('_')[0]) !== -1 ? ' selected' : '';
                        return '<option value="' + h.replace(/"/g, '&quot;') + '"' + autoSel + '>' + h + '</option>';
                    }).join('') +
                '</select>' +
            '</div>';
        });
        cont.innerHTML = html;
    }

    document.querySelectorAll('input[name="cr-ruta-modo"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.getElementById('cr-import-ruta-nueva').style.display = this.value === 'nueva' ? '' : 'none';
            document.getElementById('cr-import-ruta-existente').style.display = this.value === 'existente' ? '' : 'none';
        });
    });

    document.getElementById('cr-import-volver').addEventListener('click', function () {
        stepMapeo.style.display = 'none';
        stepUpload.style.display = '';
    });

    document.getElementById('cr-import-confirmar').addEventListener('click', function () {
        var errorEl = document.getElementById('cr-import-mapeo-error');
        errorEl.textContent = '';

        var mapeo = {};
        document.querySelectorAll('.cr-import-mapeo-select').forEach(function (sel) {
            if (sel.value) mapeo[sel.dataset.campo] = sel.value;
        });

        if (!mapeo.nombre || !mapeo.valor_total) {
            errorEl.textContent = 'Debes mapear al menos "Nombre completo" y "Valor total de la venta".';
            return;
        }

        var rutaModo = document.querySelector('input[name="cr-ruta-modo"]:checked').value;
        var payload = {
            token: importToken,
            mapeo: mapeo,
            fila_inicio: 1,
            ruta_modo: rutaModo,
        };

        if (rutaModo === 'nueva') {
            var nombreRuta = document.getElementById('cr-import-ruta-nombre').value.trim();
            if (!nombreRuta) {
                errorEl.textContent = 'Escribe un nombre para la ruta nueva.';
                return;
            }
            payload.ruta_nombre = nombreRuta;
            payload.ruta_dia = document.getElementById('cr-import-ruta-dia').value;
            payload.ruta_cobrador_id = document.getElementById('cr-import-ruta-cobrador').value;
        } else {
            payload.ruta_cobro_id = document.getElementById('cr-import-ruta-existente-select').value;
        }

        var btn = this;
        btn.disabled = true;
        btn.textContent = 'Importando...';

        fetch(baseUrl + '/importar/procesar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(payload),
        })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
        .then(function (res) {
            btn.disabled = false;
            btn.textContent = 'Importar';
            if (!res.ok) {
                errorEl.textContent = res.body.mensaje || 'Error al importar.';
                return;
            }

            stepMapeo.style.display = 'none';
            stepResultado.style.display = '';
            document.getElementById('cr-import-resultado-msg').textContent = res.body.mensaje;

            var stats = [
                ['Clientes', res.body.clientes],
                ['Ventas', res.body.ventas],
                ['Abonos', res.body.pagos],
                ['Cuotas', res.body.cuotas],
            ];
            document.getElementById('cr-import-resultado-stats').innerHTML = stats.map(function (s) {
                return '<div class="cr-import-resultado-stat"><div class="cr-import-resultado-stat-num">' + s[1] + '</div><div class="cr-import-resultado-stat-label">' + s[0] + '</div></div>';
            }).join('');

            if (res.body.filas_omitidas && res.body.filas_omitidas.length) {
                document.getElementById('cr-import-resultado-msg').textContent += ' (' + res.body.filas_omitidas.length + ' filas omitidas por datos incompletos)';
            }
        })
        .catch(function () {
            btn.disabled = false;
            btn.textContent = 'Importar';
            errorEl.textContent = 'Error de conexión al importar.';
        });
    });

    cargar();
})();
</script>
@endsection
