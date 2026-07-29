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
    .cr-stat-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:1rem; margin-bottom:1.25rem; }
    @media (max-width:1300px) { .cr-stat-grid { grid-template-columns:repeat(3,1fr); } }
    @media (max-width:760px) { .cr-stat-grid { grid-template-columns:repeat(2,1fr); } }
    @media (max-width:480px) { .cr-stat-grid { grid-template-columns:1fr; } }

    /* ── Revisión temporal ── */
    .cr-check { width:18px; height:18px; cursor:pointer; accent-color:#10b981; }
    .cr-row.cr-revisado { background:rgba(16,185,129,.05); }
    .cr-revision-reset { font-size:.68rem; color:var(--muted); text-decoration:underline; cursor:pointer; background:none; border:none; padding:0; margin-top:.3rem; }
    .cr-revision-reset:hover { color:#dc2626; }
    .cr-toggle-wrap { display:flex; align-items:center; gap:.4rem; font-size:.78rem; color:var(--text-2); white-space:nowrap; }
    .cr-toggle-wrap input { width:16px; height:16px; accent-color:#10b981; cursor:pointer; }

    /* ── Tabla: scroll horizontal con columnas clave fijas ── */
    .pm-table-wrap { -webkit-overflow-scrolling:touch; }
    .cr-sticky-1 { position:sticky; left:0; z-index:2; background:var(--card); width:36px; min-width:36px; max-width:36px; }
    .cr-sticky-2 { position:sticky; left:36px; z-index:2; background:var(--card); width:34px; min-width:34px; max-width:34px; }
    .cr-sticky-3 { position:sticky; left:70px; z-index:2; background:var(--card); box-shadow:4px 0 6px -4px rgba(0,0,0,.12); }
    .pm-thead th.cr-sticky-1, .pm-thead th.cr-sticky-2, .pm-thead th.cr-sticky-3 { background:var(--subtle); z-index:3; }
    .pm-tr:hover .cr-sticky-1, .pm-tr:hover .cr-sticky-2, .pm-tr:hover .cr-sticky-3 { background:var(--subtle); }

    .cr-th-sort { cursor:pointer; user-select:none; white-space:nowrap; }
    .cr-th-sort:hover { color:var(--text); }
    .cr-sort-arrow { display:inline-block; margin-left:.25rem; font-size:.7rem; color:var(--muted-2); }
    .cr-th-sort.cr-sort-active .cr-sort-arrow { color:#10b981; }

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
    .cr-venta-group { display:flex; flex-direction:column; gap:1px; }
    .cr-venta-group .cr-abono-wrap { padding:1px 0; }
    .cr-venta-group .cr-abono-wrap + .cr-abono-wrap { border-top:1px dashed var(--border); padding-top:2px; }
    .cr-abono-edit:hover { background:var(--subtle); border-color:var(--border); color:#10b981; }

    .cr-ver-detalle { background:none; border:1px solid transparent; cursor:pointer; color:#6366f1; padding:.3rem; border-radius:.4rem; display:inline-flex; flex-shrink:0; text-decoration:none; }
    .cr-ver-detalle:hover { background:var(--subtle); border-color:var(--border); }

    /* ── Modal de detalle ── */
    .cr-detalle-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1rem; padding-bottom:1rem; border-bottom:1px solid var(--border); }
    .cr-eliminar-cliente-btn { display:inline-flex; align-items:center; gap:.4rem; flex-shrink:0; padding:.5rem .8rem; border-radius:.55rem; border:1px solid #fecaca; background:#fef2f2; color:#dc2626; font-size:.76rem; font-weight:700; cursor:pointer; }
    .cr-eliminar-cliente-btn:hover { background:#fee2e2; }
    html.dark .cr-eliminar-cliente-btn { background:rgba(220,38,38,.12); border-color:rgba(220,38,38,.3); color:#fca5a5; }
    html.dark .cr-eliminar-cliente-btn:hover { background:rgba(220,38,38,.2); }
    .cr-detalle-nombre { font-size:1.1rem; font-weight:700; color:var(--text); }
    .cr-detalle-sub { font-size:.78rem; color:var(--muted); margin-top:.2rem; line-height:1.5; }
    .cr-detalle-resumen { display:grid; grid-template-columns:repeat(3,1fr); gap:.6rem; margin-bottom:1.25rem; }
    .cr-detalle-resumen-item { background:var(--subtle); border-radius:.625rem; padding:.7rem; text-align:center; }
    .cr-detalle-resumen-num { font-size:1.05rem; font-weight:800; color:var(--text); }
    .cr-detalle-resumen-label { font-size:.64rem; color:var(--muted); text-transform:uppercase; letter-spacing:.03em; margin-top:.15rem; }
    .cr-venta-card { border:1px solid var(--border); border-radius:.75rem; padding:1rem; margin-bottom:1rem; }
    .cr-venta-fecha-group { border:1px solid var(--border); border-radius:.85rem; padding:.75rem .75rem 0; margin-bottom:1rem; background:var(--subtle, transparent); }
    .cr-venta-fecha-group .cr-venta-card { margin-bottom:.75rem; background:var(--card, #fff); }
    .cr-venta-fecha-group-label { font-size:.68rem; font-weight:700; color:var(--muted-2); text-transform:uppercase; letter-spacing:.03em; padding:0 .1rem .5rem; display:flex; align-items:center; gap:.3rem; }
    .cr-venta-card-header { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.75rem; flex-wrap:wrap; }
    .cr-venta-badge { display:inline-flex; align-items:center; padding:.2rem .6rem; border-radius:9999px; font-size:.68rem; font-weight:700; }
    .cr-venta-cuotas-bar { height:6px; border-radius:3px; background:var(--border); overflow:hidden; display:flex; margin-top:.5rem; }
    .cr-venta-pagos-list { margin-top:.75rem; border-top:1px solid var(--border-2); padding-top:.6rem; }
    .cr-venta-pago-row { display:flex; justify-content:space-between; font-size:.78rem; padding:.25rem 0; color:var(--text-2); }
    .cr-detalle-loading { text-align:center; padding:2rem; color:var(--muted); font-size:.85rem; }

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
    <div style="display:flex; gap:.6rem; flex-wrap:wrap;">
        <a href="{{ route('clientes-ruta.historial', $tenant) }}" class="cr-import-btn-secundario" style="text-decoration:none; display:inline-flex; align-items:center;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px; margin-right:.4rem;"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg>
            Historial de movimientos
        </a>
        <button type="button" class="cr-import-btn-secundario" id="cr-abrir-nuevo-cliente">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px; margin-right:.4rem;"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            Nuevo Cliente
        </button>
        <button type="button" class="cr-import-btn" id="cr-abrir-importar">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Importar Excel
        </button>
    </div>
</div>

<div class="cr-filter-bar">
    <div class="cr-filter-group">
        <label class="cr-filter-label">Cobrador</label>
        <select id="cr-cobrador-filter" class="cr-filter-input">
            <option value="">Todos los cobradores</option>
            @foreach($cobradores as $c)
                <option value="{{ $c->id }}">{{ $c->nombre }} {{ $c->apellido }}</option>
            @endforeach
        </select>
    </div>
    <div class="cr-filter-group">
        <label class="cr-filter-label">Semana</label>
        <select id="cr-semana-filter" class="cr-filter-input">
            <option value="">Todas las semanas</option>
            <option value="1">Semana 1</option>
            <option value="2">Semana 2</option>
        </select>
    </div>
    <div class="cr-filter-group">
        <label class="cr-filter-label">Ruta de cobro</label>
        <select id="cr-ruta-filter" class="cr-filter-input">
            @foreach($rutas as $r)
                <option value="{{ $r->id }}" data-cobrador-id="{{ $r->cobrador_id }}" data-semana-ciclo="{{ $r->semana_ciclo }}" {{ (string) $rutaId === (string) $r->id ? 'selected' : '' }}>
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
    <div class="cr-filter-group" style="justify-content:flex-end;">
        <label class="cr-filter-label">&nbsp;</label>
        <label class="cr-toggle-wrap">
            <input type="checkbox" id="cr-solo-sin-revisar">
            Mostrar solo sin revisar
        </label>
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
            <div class="pm-stat-icon" style="background:#dbeafe;color:#2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div>
                <div class="pm-stat-label">Total cobrado a clientes</div>
                <div class="pm-stat-num" id="cr-total-pagado">—</div>
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
    <div class="pm-card">
        <div class="pm-stat">
            <div class="pm-stat-icon" style="background:#dcfce7;color:#16a34a;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </div>
            <div>
                <div class="pm-stat-label">Revisados (temporal)</div>
                <div class="pm-stat-num" id="cr-revisados">—</div>
                <button type="button" class="cr-revision-reset" id="cr-revision-limpiar">Limpiar revisión de esta ruta</button>
            </div>
        </div>
    </div>
</div>

<div class="pm-card">
    <div class="pm-card-header" style="flex-wrap:wrap; gap:.6rem;">
        <span class="pm-card-title" id="cr-card-title">Listado — arrastra el ícono para reordenar</span>
        <div style="display:flex; align-items:center; gap:.9rem;">
            <label style="display:flex; align-items:center; gap:.4rem; font-size:.74rem; color:var(--muted);">
                Mostrar
                <select id="cr-por-pagina" class="cr-filter-input" style="min-width:0; padding:.3rem .5rem; font-size:.74rem;">
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100" selected>100</option>
                    <option value="200">200</option>
                    <option value="500">500</option>
                </select>
                por página
            </label>
            <span class="pm-card-link" id="cr-quitar-orden" style="cursor:pointer; display:none;">✕ Quitar orden por columna</span>
            <span class="pm-card-link" id="cr-sugerir-orden" style="cursor:pointer; display:none;" title="Calcula un orden de visita según cercanía GPS (aproximado, línea recta)">🧭 Sugerir orden por GPS</span>
            <span class="pm-card-link" id="cr-refresh-link" style="cursor:pointer;">↻ Actualizar</span>
        </div>
    </div>
    <div class="pm-table-wrap">
        <table class="pm-table">
            <thead class="pm-thead">
                <tr>
                    <th class="cr-sticky-1"></th>
                    <th class="cr-sticky-2">#</th>
                    <th class="cr-sticky-3 cr-th-sort" data-sort="nombre" style="min-width:160px;">Cliente<span class="cr-sort-arrow"></span></th>
                    <th title="Marca aquí mientras comparas con tus tarjetas físicas">✓</th>
                    <th class="cr-th-sort" data-sort="telefono">Teléfono<span class="cr-sort-arrow"></span></th>
                    <th class="cr-th-sort" data-sort="direccion">Dirección<span class="cr-sort-arrow"></span></th>
                    <th class="cr-th-sort" data-sort="saldo">Saldo<span class="cr-sort-arrow"></span></th>
                    <th>Precio</th>
                    <th>Abono inicial</th>
                    <th class="cr-th-sort" data-sort="ventas_pendientes">Ventas<span class="cr-sort-arrow"></span></th>
                    <th class="cr-th-sort" data-sort="ruta_nombre">Ruta asignada<span class="cr-sort-arrow"></span></th>
                </tr>
            </thead>
            <tbody id="cr-tbody">
                <tr><td class="pm-td" colspan="11">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
    <div id="cr-paginacion" style="display:none; align-items:center; justify-content:space-between; gap:.75rem; padding:.75rem 1rem; border-top:1px solid var(--border-2);">
        <span id="cr-pagina-info" style="font-size:.75rem; color:var(--muted);"></span>
        <div style="display:flex; gap:.5rem;">
            <button type="button" id="cr-pagina-anterior" class="cr-import-btn-secundario" style="padding:.35rem .8rem; font-size:.75rem;">← Anterior</button>
            <button type="button" id="cr-pagina-siguiente" class="cr-import-btn-secundario" style="padding:.35rem .8rem; font-size:.75rem;">Siguiente →</button>
        </div>
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

<div class="cr-modal-overlay" id="cr-nuevo-overlay">
    <div class="cr-modal">
        <div class="cr-modal-header">
            <span>Nuevo cliente</span>
            <button type="button" class="cr-modal-close" id="cr-nuevo-close">&times;</button>
        </div>
        <div class="cr-modal-body">
            <div class="cr-import-ruta-form">
                <div class="cr-import-field">
                    <label>Nombre completo *</label>
                    <input type="text" id="cr-nuevo-nombre" class="cr-filter-input" placeholder="Ej. María Pérez">
                </div>
                <div class="cr-import-field">
                    <label>Código (anterior)</label>
                    <input type="text" id="cr-nuevo-codigo" class="cr-filter-input" placeholder="Opcional">
                </div>
                <div class="cr-import-field">
                    <label>Teléfono</label>
                    <input type="text" id="cr-nuevo-telefono" class="cr-filter-input" placeholder="Opcional">
                </div>
                <div class="cr-import-field">
                    <label>Dirección</label>
                    <input type="text" id="cr-nuevo-direccion" class="cr-filter-input" placeholder="Opcional">
                </div>
                <div class="cr-import-field">
                    <label>Ruta</label>
                    <select id="cr-nuevo-ruta" class="cr-filter-input">
                        <option value="">— Sin ruta —</option>
                        @foreach($rutas as $r)
                            <option value="{{ $r->id }}">{{ $r->nombre }} — {{ ucfirst($r->dia_semana) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="cr-import-divider"></div>

            <label class="cr-toggle-wrap" style="margin-bottom:.75rem;">
                <input type="checkbox" id="cr-nuevo-tiene-venta" checked>
                Tiene venta a crédito (cuotas)
            </label>

            <div id="cr-nuevo-venta-form" class="cr-import-ruta-form">
                <div class="cr-import-field">
                    <label>Producto</label>
                    <input type="text" id="cr-nuevo-producto" class="cr-filter-input" placeholder="Ej. Ropero Teresa">
                </div>
                <div class="cr-import-field">
                    <label>Valor total *</label>
                    <input type="number" step="0.01" min="0" id="cr-nuevo-valor" class="cr-filter-input" placeholder="160.00">
                </div>
                <div class="cr-import-field">
                    <label>Monto ya cobrado (abono inicial)</label>
                    <input type="number" step="0.01" min="0" id="cr-nuevo-cobrado" class="cr-filter-input" placeholder="0.00">
                </div>
                <div class="cr-import-field">
                    <label>Fecha de venta</label>
                    <input type="date" id="cr-nuevo-fecha" class="cr-filter-input">
                </div>
            </div>

            <p class="cr-import-error" id="cr-nuevo-error"></p>

            <div class="cr-import-actions">
                <button type="button" class="cr-import-btn-secundario" id="cr-nuevo-cancelar">Cancelar</button>
                <button type="button" class="cr-import-btn" id="cr-nuevo-guardar">Crear cliente</button>
            </div>
        </div>
    </div>
</div>

<div class="cr-modal-overlay" id="cr-cambiar-cobrador-overlay">
    <div class="cr-modal" style="max-width:420px;">
        <div class="cr-modal-header">
            <span>Cambiar de cobrador</span>
            <button type="button" class="cr-modal-close" id="cr-cambiar-cobrador-close">&times;</button>
        </div>
        <div class="cr-modal-body">
            <p class="cr-import-hint" id="cr-cambiar-cobrador-cliente-nombre"></p>
            <div class="cr-import-ruta-form">
                <div class="cr-import-field">
                    <label>Nuevo cobrador</label>
                    <select id="cr-cambiar-cobrador-cobrador" class="cr-filter-input">
                        <option value="">Selecciona un cobrador</option>
                        @foreach($cobradores as $c)
                            <option value="{{ $c->id }}">{{ trim($c->nombre.' '.$c->apellido) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="cr-import-field">
                    <label>Ruta del nuevo cobrador</label>
                    <select id="cr-cambiar-cobrador-ruta" class="cr-filter-input">
                        <option value="">— Selecciona un cobrador primero —</option>
                    </select>
                </div>
            </div>
            <p class="cr-import-error" id="cr-cambiar-cobrador-error"></p>
            <div class="cr-import-actions">
                <button type="button" class="cr-import-btn-secundario" id="cr-cambiar-cobrador-cancelar">Cancelar</button>
                <button type="button" class="cr-import-btn" id="cr-cambiar-cobrador-confirmar">Mover cliente</button>
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
    var esSuperAdmin = @json($esSuperAdmin);
    var rutaSelect = document.getElementById('cr-ruta-filter');
    var cobradorSelect = document.getElementById('cr-cobrador-filter');
    var semanaSelect = document.getElementById('cr-semana-filter');
    var buscarInput = document.getElementById('cr-buscar');
    var tbody = document.getElementById('cr-tbody');
    var toast = document.getElementById('cr-toast');
    var rutasDisponibles = @json($rutasParaJs);
    var sortable = null;
    var buscarTimeout = null;
    var soloSinRevisarInput = document.getElementById('cr-solo-sin-revisar');
    var paginaActual = 1;
    var paginacionDiv = document.getElementById('cr-paginacion');
    var paginaInfo = document.getElementById('cr-pagina-info');
    var btnPaginaAnterior = document.getElementById('cr-pagina-anterior');
    var btnPaginaSiguiente = document.getElementById('cr-pagina-siguiente');
    var porPaginaSelect = document.getElementById('cr-por-pagina');
    var ordenColActual = null;
    var ordenDirActual = 'asc';

    // ── Revisión (checklist para comparar contra tarjetas físicas) ──────────
    // Se guarda en el servidor (clientes.revisado_en), compartida entre
    // cualquiera que entre a esta pantalla — ya no es por navegador/dispositivo.
    var ultimoData = null;

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

    // Restringido a las rutas del MISMO cobrador que ya tiene el cliente — evita
    // que un cambio rápido y accidental lo mande con otro cobrador. Si el cliente
    // todavía no tiene ruta (primera asignación) se muestran todas.
    function rutaOptionsHtml(clienteRutaId, cobradorIdActual) {
        var html = '<option value="">— Sin ruta —</option>';
        rutasDisponibles.forEach(function (r) {
            if (cobradorIdActual && String(r.cobrador_id) !== String(cobradorIdActual)) return;
            html += '<option value="' + r.id + '"' + (String(clienteRutaId) === String(r.id) ? ' selected' : '') + '>' + r.nombre + '</option>';
        });
        return html;
    }

    function cambiarCobradorBtnHtml(clienteId, nombreCliente) {
        return '<button type="button" class="cr-abono-edit cr-cambiar-cobrador-btn" data-cliente="' + clienteId + '" data-nombre="' + nombreCliente.replace(/"/g, '&quot;') + '" title="Cambiar de cobrador">' +
            '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>' +
        '</button>';
    }

    function render(data) {
        document.getElementById('cr-total-clientes').textContent = data.total_clientes;
        document.getElementById('cr-total-saldo').textContent = money(data.total_saldo);
        document.getElementById('cr-total-pagado').textContent = money(data.total_pagado);
        document.getElementById('cr-sin-gps').textContent = data.total_sin_gps;
        document.getElementById('cr-revisados').textContent = data.total_revisados + ' / ' + data.total_clientes;

        var clientesFiltrados = soloSinRevisarInput.checked
            ? data.clientes.filter(function (c) { return !c.revisado; })
            : data.clientes;

        if (clientesFiltrados.length === 0) {
            var msg = data.clientes.length === 0 ? 'No hay clientes en esta selección.' : 'Todos los clientes de esta lista ya están marcados como revisados. 🎉';
            tbody.innerHTML = '<tr><td class="pm-td" colspan="11">' + msg + '</td></tr>';
            return;
        }

        var modo = rutaSelect.value;
        var offsetActual = ((data.pagina_actual || 1) - 1) * (data.por_pagina || 100);
        var rows = clientesFiltrados.map(function (c, idx) {
            var saldoClass = c.saldo > 0 ? 'cr-saldo-pos' : 'cr-saldo-zero';
            var dirWarn = !c.direccion ? '<span class="cr-warn-badge" title="Sin dirección registrada">⚠</span>' : '';
            var wazeBtn = c.latitud && c.longitud
                ? '<a class="cr-abono-edit" href="https://waze.com/ul?ll=' + c.latitud + ',' + c.longitud + '&navigate=yes" target="_blank" rel="noopener" title="Abrir en Waze">' +
                    '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>' +
                  '</a>'
                : '';
            var codigoHtml = '<div class="cr-abono-wrap" style="font-size:.7rem; color:var(--muted-2);">' +
                'Cód: ' + (c.codigo_anterior || '—') +
                campoEditBtn(c.id, 'codigo_anterior', c.codigo_anterior, 'Código anterior') +
                '</div>';
            var rutaActualHtml = modo === 'todos' ? '<div style="font-size:.68rem; color:var(--muted-2); margin-top:.15rem;">' + (c.ruta_nombre || 'Sin ruta') + '</div>' : '';

            var abonoHtml;
            var precioHtml;
            var nombreAttr = c.nombre.replace(/"/g, '&quot;');
            if (c.ventas_credito && c.ventas_credito.length > 0) {
                var multiple = c.ventas_credito.length > 1;

                precioHtml = c.ventas_credito.map(function (v, i) {
                    var etiqueta = multiple ? '<span style="color:var(--muted-2); font-size:.68rem;">V' + (i + 1) + ': </span>' : '';
                    return '<div class="cr-abono-wrap">' +
                        etiqueta +
                        '<span>' + money(v.total) + '</span>' +
                        '<button type="button" class="cr-abono-edit cr-precio-edit" data-cliente="' + c.id + '" data-venta="' + v.venta_id + '" data-total="' + v.total + '" data-pagado="' + (v.abono_inicial || 0) + '" data-nombre="' + nombreAttr + '" title="Editar precio de la venta">' +
                            '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>' +
                        '</button>' +
                    '</div>';
                }).join('');
                if (multiple) precioHtml = '<div class="cr-venta-group">' + precioHtml + '</div>';

                abonoHtml = c.ventas_credito.map(function (v, i) {
                    var etiqueta = multiple ? '<span style="color:var(--muted-2); font-size:.68rem;">V' + (i + 1) + ': </span>' : '';
                    return '<div class="cr-abono-wrap">' +
                        etiqueta +
                        '<span>' + (v.abono_inicial !== null ? money(v.abono_inicial) : '—') + '</span>' +
                        '<button type="button" class="cr-abono-edit cr-venta-edit" data-cliente="' + c.id + '" data-venta="' + v.venta_id + '" data-monto="' + (v.abono_inicial || 0) + '" data-total="' + v.total + '" data-nombre="' + nombreAttr + '" title="Editar abono inicial">' +
                            '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>' +
                        '</button>' +
                    '</div>';
                }).join('');
                if (multiple) abonoHtml = '<div class="cr-venta-group">' + abonoHtml + '</div>';
            } else {
                precioHtml = '<span style="color:var(--muted-2);">—</span>';
                abonoHtml = '<span style="color:var(--muted-2);">— sin crédito —</span>';
            }

            var ventasClass = c.ventas_pendientes > 0 ? 'cr-pill has' : 'cr-pill';
            var estaRevisado = !!c.revisado;

            return '' +
                '<tr class="pm-tr cr-row' + (estaRevisado ? ' cr-revisado' : '') + '" data-id="' + c.id + '">' +
                    '<td class="pm-td cr-sticky-1"><span class="cr-handle">⠿⠿</span></td>' +
                    '<td class="pm-td cr-sticky-2"><span class="cr-orden-badge">' + (offsetActual + idx + 1) + '</span></td>' +
                    '<td class="pm-td cr-sticky-3"><div class="cr-abono-wrap"><a class="cr-ver-detalle" href="' + baseUrl + '/clientes/' + c.id + '/perfil" title="Ver perfil completo del cliente"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a><strong>' + c.nombre + '</strong>' + campoEditBtn(c.id, 'nombre', c.nombre, 'Nombre') + '</div>' + codigoHtml + '</td>' +
                    '<td class="pm-td" style="text-align:center;"><input type="checkbox" class="cr-check cr-revisar-check" data-cliente="' + c.id + '"' + (estaRevisado ? ' checked' : '') + '></td>' +
                    '<td class="pm-td"><div class="cr-abono-wrap"><span>' + (c.telefono || '—') + '</span>' + campoEditBtn(c.id, 'telefono', c.telefono, 'Teléfono') + '</div></td>' +
                    '<td class="pm-td"><div class="cr-abono-wrap"><span>' + (c.direccion || '—') + '</span> ' + dirWarn + campoEditBtn(c.id, 'direccion', c.direccion_raw, 'Dirección') + wazeBtn + '</div></td>' +
                    '<td class="pm-td"><div class="cr-abono-wrap"><span class="' + saldoClass + '">' + money(c.saldo) + '</span>' + campoEditBtn(c.id, 'saldo', c.saldo, 'Saldo') + '</div></td>' +
                    '<td class="pm-td">' + precioHtml + '</td>' +
                    '<td class="pm-td">' + abonoHtml + '</td>' +
                    '<td class="pm-td"><span class="' + ventasClass + '">' + c.ventas_pendientes + '</span></td>' +
                    '<td class="pm-td"><div class="cr-abono-wrap"><select class="cr-ruta-select" data-id="' + c.id + '">' + rutaOptionsHtml(c.ruta_cobro_id, c.cobrador_id_ruta) + '</select>' + cambiarCobradorBtnHtml(c.id, c.nombre) + '</div>' + rutaActualHtml + '</td>' +
                '</tr>';
        }).join('');

        tbody.innerHTML = rows;

        tbody.querySelectorAll('.cr-revisar-check').forEach(function (chk) {
            chk.addEventListener('change', function () {
                var clienteId = Number(this.dataset.cliente);
                var marcado = this.checked;

                // Optimista: refleja el cambio de una vez en ultimoData y vuelve a
                // pintar, sin esperar la respuesta del servidor.
                var cliente = ultimoData.clientes.find(function (c) { return c.id === clienteId; });
                if (cliente) {
                    var yaEstaba = !!cliente.revisado;
                    cliente.revisado = marcado;
                    if (marcado && !yaEstaba) ultimoData.total_revisados++;
                    else if (!marcado && yaEstaba) ultimoData.total_revisados--;
                }
                render(ultimoData);

                fetch(baseUrl + '/clientes/' + clienteId + '/revisado', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ revisado: marcado }),
                }).catch(function () {
                    showToast('No se pudo guardar la revisión, intenta de nuevo.');
                });
            });
        });

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

        tbody.querySelectorAll('.cr-cambiar-cobrador-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                abrirCambiarCobradorModal(this.dataset.cliente, this.dataset.nombre);
            });
        });

        if (sortable) sortable.destroy();
        var multiPagina = data.paginado && data.total_paginas > 1;
        var puedeReordenar = modo !== 'todos' && !soloSinRevisarInput.checked && !ordenColActual;
        var cardTitle = document.getElementById('cr-card-title');

        if (puedeReordenar) {
            sortable = Sortable.create(tbody, {
                handle: '.cr-handle',
                animation: 150,
                onEnd: function () {
                    var ids = Array.from(tbody.querySelectorAll('.cr-row')).map(function (tr) { return tr.dataset.id; });
                    tbody.querySelectorAll('.cr-orden-badge').forEach(function (b, i) { b.textContent = offsetActual + i + 1; });
                    fetch(baseUrl + '/reordenar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ orden: ids, offset: offsetActual }),
                    }).then(function (r) { return r.json(); }).then(function () {
                        showToast('Orden guardado.');
                    });
                },
            });
            cardTitle.textContent = multiPagina
                ? 'Listado — arrastra el ícono para reordenar (solo dentro de esta página)'
                : 'Listado — arrastra el ícono para reordenar';
        } else {
            tbody.querySelectorAll('.cr-handle').forEach(function (h) { h.style.visibility = 'hidden'; });
            cardTitle.textContent = ordenColActual ? 'Listado — ordenado por columna (arrastre desactivado)' : 'Listado';
        }

        document.getElementById('cr-sugerir-orden').style.display = puedeReordenar ? '' : 'none';

        if (data.paginado && data.total_paginas > 1) {
            paginacionDiv.style.display = 'flex';
            paginaInfo.textContent = 'Página ' + data.pagina_actual + ' de ' + data.total_paginas + ' (' + data.total_clientes + ' clientes en total)' +
                (puedeReordenar ? ' — el orden solo se ajusta dentro de cada página' : '');
            btnPaginaAnterior.disabled = data.pagina_actual <= 1;
            btnPaginaSiguiente.disabled = data.pagina_actual >= data.total_paginas;
        } else {
            paginacionDiv.style.display = 'none';
        }
    }

    function cargar() {
        var rutaId = rutaSelect.value;
        var buscar = buscarInput.value.trim();
        var url = baseUrl + '/data?ruta_cobro_id=' + encodeURIComponent(rutaId) + '&page=' + paginaActual + '&por_pagina=' + porPaginaSelect.value;
        if (buscar !== '') url += '&buscar=' + encodeURIComponent(buscar);
        if (ordenColActual) url += '&orden_col=' + encodeURIComponent(ordenColActual) + '&orden_dir=' + encodeURIComponent(ordenDirActual);
        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                ultimoData = data;
                ordenColActual = data.orden_col || null;
                ordenDirActual = data.orden_dir || 'asc';
                actualizarFlechasOrden();
                render(data);
            });
    }

    soloSinRevisarInput.addEventListener('change', function () { render(ultimoData); });

    document.getElementById('cr-revision-limpiar').addEventListener('click', function () {
        if (!confirm('¿Limpiar la revisión de esta selección para TODOS los que usan esta pantalla? No borra nada de clientes, ventas ni pagos — solo el checklist.')) return;
        var url = baseUrl + '/limpiar-revision?ruta_cobro_id=' + encodeURIComponent(rutaSelect.value);
        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        })
            .then(function (r) { return r.json(); })
            .then(function () {
                soloSinRevisarInput.checked = false;
                paginaActual = 1;
                cargar();
                showToast('Revisión reiniciada.');
            })
            .catch(function () { showToast('No se pudo reiniciar la revisión.'); });
    });

    rutaSelect.addEventListener('change', function () {
        var url = new URL(window.location);
        url.searchParams.set('ruta_cobro_id', rutaSelect.value);
        window.history.replaceState({}, '', url);
        paginaActual = 1;
        cargar();
    });

    // ── Filtro por cobrador y/o semana: reduce las opciones de "Ruta de cobro" ──
    function aplicarFiltrosRutaOpciones() {
        var cobradorId = cobradorSelect.value;
        var semana = semanaSelect.value;
        var opciones = Array.prototype.slice.call(rutaSelect.options);
        var huboCambioDeSeleccion = false;

        opciones.forEach(function (opt) {
            if (!opt.dataset.cobradorId) return; // "sin_ruta" / "todos": siempre visibles
            var matchCobrador = !cobradorId || opt.dataset.cobradorId === cobradorId;
            // Una ruta sin semana clasificada (data-semana-ciclo="") se muestra
            // siempre, igual que en el POS — no se filtra hasta que se clasifique.
            var matchSemana = !semana || opt.dataset.semanaCiclo === semana || opt.dataset.semanaCiclo === '';
            var visible = matchCobrador && matchSemana;
            opt.style.display = visible ? '' : 'none';
            if (!visible && opt.selected) {
                opt.selected = false;
                huboCambioDeSeleccion = true;
            }
        });

        if (huboCambioDeSeleccion) {
            var primeraVisible = opciones.find(function (opt) {
                return opt.style.display !== 'none';
            });
            if (primeraVisible) primeraVisible.selected = true;
            rutaSelect.dispatchEvent(new Event('change'));
        }
    }

    cobradorSelect.addEventListener('change', aplicarFiltrosRutaOpciones);
    semanaSelect.addEventListener('change', aplicarFiltrosRutaOpciones);

    buscarInput.addEventListener('input', function () {
        clearTimeout(buscarTimeout);
        paginaActual = 1;
        buscarTimeout = setTimeout(cargar, 300);
    });

    document.getElementById('cr-refresh-link').addEventListener('click', cargar);

    porPaginaSelect.addEventListener('change', function () {
        paginaActual = 1;
        cargar();
    });

    // ── Orden por columna (clic en el encabezado) ────────────────────────
    document.querySelectorAll('.cr-th-sort').forEach(function (th) {
        th.addEventListener('click', function () {
            var col = th.dataset.sort;
            if (ordenColActual === col) {
                ordenDirActual = ordenDirActual === 'asc' ? 'desc' : 'asc';
            } else {
                ordenColActual = col;
                ordenDirActual = 'asc';
            }
            paginaActual = 1;
            cargar();
        });
    });

    function actualizarFlechasOrden() {
        document.querySelectorAll('.cr-th-sort').forEach(function (th) {
            var activo = th.dataset.sort === ordenColActual;
            th.classList.toggle('cr-sort-active', activo);
            th.querySelector('.cr-sort-arrow').textContent = activo ? (ordenDirActual === 'asc' ? '▲' : '▼') : '';
        });
        document.getElementById('cr-quitar-orden').style.display = ordenColActual ? '' : 'none';
    }

    document.getElementById('cr-quitar-orden').addEventListener('click', function () {
        ordenColActual = null;
        ordenDirActual = 'asc';
        paginaActual = 1;
        cargar();
    });

    document.getElementById('cr-sugerir-orden').addEventListener('click', function () {
        var rutaId = rutaSelect.value;
        if (!rutaId || rutaId === 'todos' || rutaId === 'sin_ruta') return;

        var btn = this;
        var textoOriginal = btn.textContent;
        btn.textContent = 'Calculando...';

        fetch(baseUrl + '/rutas/' + rutaId + '/sugerir-orden')
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
            .then(function (res) {
                btn.textContent = textoOriginal;
                if (!res.ok) {
                    showToast(res.body.mensaje || 'No se pudo calcular el orden sugerido.');
                    return;
                }

                var aviso = 'Se calculó un orden para ' + res.body.con_gps + ' cliente(s) con GPS' +
                    (res.body.sin_gps > 0 ? ', dejando ' + res.body.sin_gps + ' sin GPS al final' : '') +
                    '.\n\nEs una aproximación en línea recta (no la ruta real de calles). ¿Aplicar este orden?';
                if (!window.confirm(aviso)) return;

                fetch(baseUrl + '/reordenar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ orden: res.body.orden, offset: 0 }),
                }).then(function (r) { return r.json(); }).then(function () {
                    showToast('Orden sugerido aplicado.');
                    cargar();
                });
            })
            .catch(function () {
                btn.textContent = textoOriginal;
                showToast('Error de conexión al calcular el orden.');
            });
    });

    btnPaginaAnterior.addEventListener('click', function () {
        if (paginaActual <= 1) return;
        paginaActual--;
        cargar();
    });
    btnPaginaSiguiente.addEventListener('click', function () {
        if (!ultimoData || paginaActual >= ultimoData.total_paginas) return;
        paginaActual++;
        cargar();
    });

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

    // ── Nuevo Cliente ─────────────────────────────────────────────────────
    var nuevoOverlay = document.getElementById('cr-nuevo-overlay');
    var nuevoTieneVenta = document.getElementById('cr-nuevo-tiene-venta');
    var nuevoVentaForm = document.getElementById('cr-nuevo-venta-form');

    function abrirNuevoModal() {
        ['nombre', 'codigo', 'telefono', 'direccion', 'producto', 'valor', 'cobrado', 'fecha'].forEach(function (campo) {
            document.getElementById('cr-nuevo-' + campo).value = '';
        });
        document.getElementById('cr-nuevo-error').textContent = '';
        nuevoTieneVenta.checked = true;
        nuevoVentaForm.style.display = '';

        var rutaSel = document.getElementById('cr-nuevo-ruta');
        var actual = rutaSelect.value;
        rutaSel.value = (actual !== 'todos' && actual !== 'sin_ruta') ? actual : '';

        nuevoOverlay.classList.add('show');
    }
    function cerrarNuevoModal() { nuevoOverlay.classList.remove('show'); }

    document.getElementById('cr-abrir-nuevo-cliente').addEventListener('click', abrirNuevoModal);
    document.getElementById('cr-nuevo-close').addEventListener('click', cerrarNuevoModal);
    document.getElementById('cr-nuevo-cancelar').addEventListener('click', cerrarNuevoModal);
    nuevoOverlay.addEventListener('click', function (e) { if (e.target === nuevoOverlay) cerrarNuevoModal(); });

    nuevoTieneVenta.addEventListener('change', function () {
        nuevoVentaForm.style.display = this.checked ? '' : 'none';
    });

    document.getElementById('cr-nuevo-guardar').addEventListener('click', function () {
        var errorEl = document.getElementById('cr-nuevo-error');
        errorEl.textContent = '';

        var nombre = document.getElementById('cr-nuevo-nombre').value.trim();
        if (!nombre) {
            errorEl.textContent = 'El nombre es obligatorio.';
            return;
        }

        var tieneVenta = nuevoTieneVenta.checked;
        var valor = document.getElementById('cr-nuevo-valor').value;
        if (tieneVenta && (!valor || Number(valor) <= 0)) {
            errorEl.textContent = 'Escribe el valor total de la venta.';
            return;
        }

        var payload = {
            nombre: nombre,
            codigo_anterior: document.getElementById('cr-nuevo-codigo').value.trim() || null,
            telefono: document.getElementById('cr-nuevo-telefono').value.trim() || null,
            direccion: document.getElementById('cr-nuevo-direccion').value.trim() || null,
            ruta_cobro_id: document.getElementById('cr-nuevo-ruta').value || null,
            tiene_venta: tieneVenta,
            producto: document.getElementById('cr-nuevo-producto').value.trim() || null,
            valor_total: tieneVenta ? Number(valor) : null,
            monto_cobrado: tieneVenta ? Number(document.getElementById('cr-nuevo-cobrado').value || 0) : null,
            fecha_venta: document.getElementById('cr-nuevo-fecha').value || null,
        };

        var btn = this;
        btn.disabled = true;
        btn.textContent = 'Guardando...';

        fetch(baseUrl + '/clientes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(payload),
        }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
          .then(function (res) {
            btn.disabled = false;
            btn.textContent = 'Crear cliente';
            if (!res.ok) {
                errorEl.textContent = res.body.mensaje || 'Error al crear el cliente.';
                return;
            }
            cerrarNuevoModal();
            showToast(res.body.mensaje || 'Cliente creado.');
            cargar();
        }).catch(function () {
            btn.disabled = false;
            btn.textContent = 'Crear cliente';
            errorEl.textContent = 'Error de conexión al guardar.';
        });
    });

    // ── Cambiar de cobrador (acción aparte, deliberada) ──────────────────
    var cambiarCobradorOverlay = document.getElementById('cr-cambiar-cobrador-overlay');
    var cambiarCobradorCobradorSel = document.getElementById('cr-cambiar-cobrador-cobrador');
    var cambiarCobradorRutaSel = document.getElementById('cr-cambiar-cobrador-ruta');
    var cambiarCobradorClienteId = null;

    function abrirCambiarCobradorModal(clienteId, nombreCliente) {
        cambiarCobradorClienteId = clienteId;
        document.getElementById('cr-cambiar-cobrador-cliente-nombre').textContent = 'Cliente: ' + nombreCliente;
        document.getElementById('cr-cambiar-cobrador-error').textContent = '';
        cambiarCobradorCobradorSel.value = '';
        cambiarCobradorRutaSel.innerHTML = '<option value="">— Selecciona un cobrador primero —</option>';
        cambiarCobradorOverlay.classList.add('show');
    }
    function cerrarCambiarCobradorModal() { cambiarCobradorOverlay.classList.remove('show'); }

    cambiarCobradorCobradorSel.addEventListener('change', function () {
        var cobradorId = this.value;
        if (!cobradorId) {
            cambiarCobradorRutaSel.innerHTML = '<option value="">— Selecciona un cobrador primero —</option>';
            return;
        }
        var rutasDelCobrador = rutasDisponibles.filter(function (r) { return String(r.cobrador_id) === String(cobradorId); });
        if (rutasDelCobrador.length === 0) {
            cambiarCobradorRutaSel.innerHTML = '<option value="">Este cobrador no tiene rutas</option>';
            return;
        }
        cambiarCobradorRutaSel.innerHTML = rutasDelCobrador.map(function (r) {
            return '<option value="' + r.id + '">' + r.nombre + (r.dia_semana ? ' — ' + r.dia_semana : '') + '</option>';
        }).join('');
    });

    document.getElementById('cr-cambiar-cobrador-close').addEventListener('click', cerrarCambiarCobradorModal);
    document.getElementById('cr-cambiar-cobrador-cancelar').addEventListener('click', cerrarCambiarCobradorModal);
    cambiarCobradorOverlay.addEventListener('click', function (e) { if (e.target === cambiarCobradorOverlay) cerrarCambiarCobradorModal(); });

    document.getElementById('cr-cambiar-cobrador-confirmar').addEventListener('click', function () {
        var errorEl = document.getElementById('cr-cambiar-cobrador-error');
        var rutaId = cambiarCobradorRutaSel.value;
        if (!cambiarCobradorCobradorSel.value || !rutaId) {
            errorEl.textContent = 'Selecciona el cobrador y la ruta de destino.';
            return;
        }

        var btn = this;
        btn.disabled = true;
        btn.textContent = 'Moviendo...';

        fetch(baseUrl + '/clientes/' + cambiarCobradorClienteId + '/ruta', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ ruta_cobro_id: rutaId }),
        }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
          .then(function (res) {
            btn.disabled = false;
            btn.textContent = 'Mover cliente';
            if (!res.ok) {
                errorEl.textContent = res.body.mensaje || 'No se pudo mover al cliente.';
                return;
            }
            cerrarCambiarCobradorModal();
            showToast('Cliente movido a otro cobrador.');
            cargar();
        }).catch(function () {
            btn.disabled = false;
            btn.textContent = 'Mover cliente';
            errorEl.textContent = 'Error de conexión.';
        });
    });

    // Ver detalle: ahora es una página completa (perfil del cliente), enlazada
    // directamente desde el ícono del ojo en cada fila — ver rutaOptionsHtml/
    // el <a> generado en render() más abajo. Ya no hay modal ni JS aquí.

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
