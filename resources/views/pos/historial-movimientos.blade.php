@extends('pos._layout')

@section('page-title', 'Historial de movimientos')

@section('styles')
<style>
    .hm-back { display:inline-flex; align-items:center; gap:.35rem; font-size:.78rem; color:var(--muted); text-decoration:none; margin-bottom:.75rem; }
    .hm-back:hover { color:var(--text); }

    .hm-filter-bar { display:flex; flex-wrap:wrap; align-items:flex-end; gap:.75rem; margin-bottom:1.25rem; }
    .hm-filter-group { display:flex; flex-direction:column; gap:.35rem; }
    .hm-filter-label { font-size:.7rem; font-weight:600; color:var(--muted); }
    .hm-filter-input { border:1px solid var(--border); border-radius:.625rem; padding:.55rem .8rem; font-size:.84rem; color:var(--text-2); background:var(--card); outline:none; }
    .hm-filter-input:focus { border-color:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,.12); }
    .hm-filter-input.hm-buscar { min-width:220px; }
    .hm-filter-input.hm-fecha { min-width:150px; }
    .hm-filter-input.hm-ruta { min-width:190px; }
    .hm-limpiar-filtros { font-size:.75rem; color:var(--muted); text-decoration:underline; cursor:pointer; background:none; border:none; padding:.55rem 0; }
    .hm-limpiar-filtros:hover { color:#dc2626; }

    .hm-cliente-link { color:#10b981; text-decoration:none; font-weight:600; }
    .hm-cliente-link:hover { text-decoration:underline; }

    .hm-th-sort { cursor:pointer; user-select:none; white-space:nowrap; }
    .hm-th-sort:hover { color:var(--text); }
    .hm-sort-arrow { display:inline-block; margin-left:.25rem; font-size:.7rem; color:var(--muted-2); }
    .hm-th-sort.hm-sort-active .hm-sort-arrow { color:#10b981; }
</style>
@endsection

@section('content')
<a href="{{ route('clientes-ruta.index', $tenant) }}" class="hm-back">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Volver a Clientes por Ruta
</a>

<div class="pm-page-header">
    <h1>Historial de movimientos</h1>
    <p>Todos los cambios de ruta/cobrador de todos los clientes, con quién los hizo y cuándo.</p>
</div>

<div class="hm-filter-bar">
    <div class="hm-filter-group">
        <label class="hm-filter-label">Buscar cliente (nombre o código)</label>
        <input type="text" id="hm-buscar" class="hm-filter-input hm-buscar" placeholder="Ej. 7304 o nombre del cliente...">
    </div>
    <div class="hm-filter-group">
        <label class="hm-filter-label">Desde</label>
        <input type="date" id="hm-fecha-desde" class="hm-filter-input hm-fecha">
    </div>
    <div class="hm-filter-group">
        <label class="hm-filter-label">Hasta</label>
        <input type="date" id="hm-fecha-hasta" class="hm-filter-input hm-fecha">
    </div>
    <div class="hm-filter-group">
        <label class="hm-filter-label">Ruta anterior</label>
        <select id="hm-ruta-anterior" class="hm-filter-input hm-ruta">
            <option value="">Todas</option>
            @foreach($rutas as $r)
                <option value="{{ $r->id }}">{{ $r->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div class="hm-filter-group">
        <label class="hm-filter-label">Ruta nueva</label>
        <select id="hm-ruta-nueva" class="hm-filter-input hm-ruta">
            <option value="">Todas</option>
            @foreach($rutas as $r)
                <option value="{{ $r->id }}">{{ $r->nombre }}</option>
            @endforeach
        </select>
    </div>
    <button type="button" class="hm-limpiar-filtros" id="hm-limpiar-filtros">✕ Limpiar filtros</button>
</div>

<div class="pm-card">
    <div class="pm-card-header" style="flex-wrap:wrap; gap:.6rem;">
        <span class="pm-card-title">Movimientos</span>
        <div style="display:flex; align-items:center; gap:.9rem;">
            <label style="display:flex; align-items:center; gap:.4rem; font-size:.74rem; color:var(--muted);">
                Mostrar
                <select id="hm-por-pagina" class="hm-filter-input" style="min-width:0; padding:.3rem .5rem; font-size:.74rem;">
                    <option value="25">25</option>
                    <option value="50" selected>50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                </select>
                por página
            </label>
            <span class="pm-card-link" id="hm-refresh-link" style="cursor:pointer;">↻ Actualizar</span>
        </div>
    </div>
    <div class="pm-table-wrap">
        <table class="pm-table">
            <thead class="pm-thead">
                <tr>
                    <th class="hm-th-sort" data-sort="fecha">Fecha<span class="hm-sort-arrow"></span></th>
                    <th class="hm-th-sort" data-sort="cliente">Cliente<span class="hm-sort-arrow"></span></th>
                    <th class="hm-th-sort" data-sort="ruta_anterior">Ruta anterior<span class="hm-sort-arrow"></span></th>
                    <th class="hm-th-sort" data-sort="ruta_nueva">Ruta nueva<span class="hm-sort-arrow"></span></th>
                    <th class="hm-th-sort" data-sort="usuario">Usuario<span class="hm-sort-arrow"></span></th>
                </tr>
            </thead>
            <tbody id="hm-tbody">
                <tr><td class="pm-td" colspan="5">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
    <div id="hm-paginacion" style="display:none; align-items:center; justify-content:space-between; gap:.75rem; padding:.75rem 1rem; border-top:1px solid var(--border-2);">
        <span id="hm-pagina-info" style="font-size:.75rem; color:var(--muted);"></span>
        <div style="display:flex; gap:.5rem;">
            <button type="button" id="hm-pagina-anterior" style="padding:.35rem .8rem; font-size:.75rem; border:1px solid var(--border); border-radius:.55rem; background:var(--subtle); cursor:pointer; color:var(--text-2);">← Anterior</button>
            <button type="button" id="hm-pagina-siguiente" style="padding:.35rem .8rem; font-size:.75rem; border:1px solid var(--border); border-radius:.55rem; background:var(--subtle); cursor:pointer; color:var(--text-2);">Siguiente →</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    var tenant = {{ (int) $tenant }};
    var baseUrl = '/clientes-ruta/' + tenant;
    var tbody = document.getElementById('hm-tbody');
    var buscarInput = document.getElementById('hm-buscar');
    var fechaDesdeInput = document.getElementById('hm-fecha-desde');
    var fechaHastaInput = document.getElementById('hm-fecha-hasta');
    var rutaAnteriorSelect = document.getElementById('hm-ruta-anterior');
    var rutaNuevaSelect = document.getElementById('hm-ruta-nueva');
    var porPaginaSelect = document.getElementById('hm-por-pagina');
    var buscarTimeout = null;
    var paginaActual = 1;
    var paginacionDiv = document.getElementById('hm-paginacion');
    var paginaInfo = document.getElementById('hm-pagina-info');
    var btnPaginaAnterior = document.getElementById('hm-pagina-anterior');
    var btnPaginaSiguiente = document.getElementById('hm-pagina-siguiente');
    var ultimoData = null;
    var ordenColActual = null;
    var ordenDirActual = 'asc';

    function cargar() {
        var buscar = buscarInput.value.trim();
        var url = baseUrl + '/historial/data?page=' + paginaActual + '&por_pagina=' + porPaginaSelect.value;
        if (buscar !== '') url += '&buscar=' + encodeURIComponent(buscar);
        if (fechaDesdeInput.value) url += '&fecha_desde=' + encodeURIComponent(fechaDesdeInput.value);
        if (fechaHastaInput.value) url += '&fecha_hasta=' + encodeURIComponent(fechaHastaInput.value);
        if (rutaAnteriorSelect.value) url += '&ruta_anterior_id=' + encodeURIComponent(rutaAnteriorSelect.value);
        if (rutaNuevaSelect.value) url += '&ruta_nueva_id=' + encodeURIComponent(rutaNuevaSelect.value);
        if (ordenColActual) url += '&orden_col=' + encodeURIComponent(ordenColActual) + '&orden_dir=' + encodeURIComponent(ordenDirActual);

        tbody.innerHTML = '<tr><td class="pm-td" colspan="5">Cargando...</td></tr>';

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

    function render(data) {
        if (data.items.length === 0) {
            tbody.innerHTML = '<tr><td class="pm-td" colspan="5">No hay movimientos que calcen con los filtros.</td></tr>';
            paginacionDiv.style.display = 'none';
            return;
        }

        tbody.innerHTML = data.items.map(function (h) {
            var clienteCelda = h.cliente_id
                ? '<a class="hm-cliente-link" href="' + baseUrl + '/clientes/' + h.cliente_id + '/perfil">' + h.cliente_nombre + '</a>'
                : h.cliente_nombre;

            return '<tr class="pm-tr">' +
                '<td class="pm-td">' + h.fecha + '</td>' +
                '<td class="pm-td">' + clienteCelda + '</td>' +
                '<td class="pm-td">' + (h.ruta_anterior || 'Sin ruta') + (h.cobrador_anterior ? ' <span style="color:var(--muted-2);">(' + h.cobrador_anterior + ')</span>' : '') + '</td>' +
                '<td class="pm-td">' + (h.ruta_nueva || 'Sin ruta') + (h.cobrador_nuevo ? ' <span style="color:var(--muted-2);">(' + h.cobrador_nuevo + ')</span>' : '') + '</td>' +
                '<td class="pm-td">' + h.usuario + '</td>' +
            '</tr>';
        }).join('');

        if (data.total_paginas > 1) {
            paginacionDiv.style.display = 'flex';
            paginaInfo.textContent = 'Página ' + data.pagina_actual + ' de ' + data.total_paginas + ' (' + data.total + ' movimientos en total)';
            btnPaginaAnterior.disabled = data.pagina_actual <= 1;
            btnPaginaSiguiente.disabled = data.pagina_actual >= data.total_paginas;
        } else {
            paginacionDiv.style.display = 'none';
        }
    }

    function actualizarFlechasOrden() {
        document.querySelectorAll('.hm-th-sort').forEach(function (th) {
            var activo = th.dataset.sort === ordenColActual;
            th.classList.toggle('hm-sort-active', activo);
            th.querySelector('.hm-sort-arrow').textContent = activo ? (ordenDirActual === 'asc' ? '▲' : '▼') : '';
        });
    }

    document.querySelectorAll('.hm-th-sort').forEach(function (th) {
        th.addEventListener('click', function () {
            var col = th.dataset.sort;
            if (ordenColActual === col) {
                ordenDirActual = ordenDirActual === 'asc' ? 'desc' : 'asc';
            } else {
                ordenColActual = col;
                ordenDirActual = col === 'fecha' ? 'desc' : 'asc';
            }
            paginaActual = 1;
            cargar();
        });
    });

    [buscarInput].forEach(function (el) {
        el.addEventListener('input', function () {
            clearTimeout(buscarTimeout);
            paginaActual = 1;
            buscarTimeout = setTimeout(cargar, 300);
        });
    });

    [fechaDesdeInput, fechaHastaInput, rutaAnteriorSelect, rutaNuevaSelect, porPaginaSelect].forEach(function (el) {
        el.addEventListener('change', function () {
            paginaActual = 1;
            cargar();
        });
    });

    document.getElementById('hm-limpiar-filtros').addEventListener('click', function () {
        buscarInput.value = '';
        fechaDesdeInput.value = '';
        fechaHastaInput.value = '';
        rutaAnteriorSelect.value = '';
        rutaNuevaSelect.value = '';
        paginaActual = 1;
        cargar();
    });

    document.getElementById('hm-refresh-link').addEventListener('click', cargar);

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

    cargar();
})();
</script>
@endsection
