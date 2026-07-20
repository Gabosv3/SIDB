@extends('pos._layout')

@section('page-title', 'Resumen del día')

@php
    $metodoLabels = ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'cheque' => 'Cheque', 'deposito' => 'Depósito'];
    $metodoColor  = ['efectivo' => '#16a34a', 'transferencia' => '#2563eb', 'cheque' => '#9333ea', 'deposito' => '#d97706'];
    $resultadoLabels = ['sin_pago' => 'Sin pago', 'promesa_pago' => 'Promesa de pago', 'no_encontrado' => 'No encontrado', 'rechazo' => 'Rechazo', 'abono_previo' => 'Abono previo'];
    $resultadoBg = [
        'sin_pago'      => ['bg' => '#fef9c3', 'color' => '#854d0e', 'dot' => '#ca8a04'],
        'promesa_pago'  => ['bg' => '#e0f2fe', 'color' => '#0369a1', 'dot' => '#0284c7'],
        'no_encontrado' => ['bg' => '#f1f5f9', 'color' => '#475569', 'dot' => '#94a3b8'],
        'rechazo'       => ['bg' => '#ffe4e6', 'color' => '#9f1239', 'dot' => '#e11d48'],
        'abono_previo'  => ['bg' => '#dcfce7', 'color' => '#166534', 'dot' => '#16a34a'],
    ];
    $avatarColors = ['#dbeafe:#1d4ed8', '#fce7f3:#be185d', '#dcfce7:#15803d', '#fef3c7:#a16207', '#ede9fe:#6d28d9'];

    // Rutas presentes en el resumen de hoy, para el filtro "todos / algunos / varios"
    $rutasDisponibles = collect($resumen)
        ->pluck('ruta')
        ->filter()
        ->unique('id')
        ->sortBy('nombre')
        ->values();

    $pmDelta = fn ($hoy, $ayer) => $ayer > 0 ? round((($hoy - $ayer) / $ayer) * 100, 1) : ($hoy > 0 ? 100.0 : 0.0);

    $kpis = [
        ['label' => 'Hoy',                'sub' => 'Total cobrado',     'num' => '$'.number_format($hoySimple['total_cobrado'], 2), 'delta' => $pmDelta($hoySimple['total_cobrado'], $ayerSimple['total_cobrado'])],
        ['label' => 'Pagos realizados',    'sub' => null,                'num' => $hoySimple['total_pagos'],                          'delta' => $pmDelta($hoySimple['total_pagos'], $ayerSimple['total_pagos'])],
        ['label' => 'Clientes atendidos',  'sub' => null,                'num' => $hoySimple['clientes_visitados'],                   'delta' => $pmDelta($hoySimple['clientes_visitados'], $ayerSimple['clientes_visitados'])],
        ['label' => 'Sin cobro',           'sub' => null,                'num' => $hoySimple['total_sin_cobro'],                      'delta' => $pmDelta($hoySimple['total_sin_cobro'], $ayerSimple['total_sin_cobro'])],
        ['label' => 'Pendientes',          'sub' => null,                'num' => $hoySimple['total_pendientes'],                     'delta' => $pmDelta($hoySimple['total_pendientes'], $ayerSimple['total_pendientes'])],
    ];
@endphp

@section('styles')
<style>
    .rd-kpi-grid   { display:grid; grid-template-columns:repeat(5,1fr); gap:1rem; margin-bottom:1.5rem; }
    .rd-kpi-card   { padding:1rem 1.1rem; }
    .rd-kpi-label  { font-size:.7rem; font-weight:600; color:#6b7280; }
    .rd-kpi-num    { font-size:1.3rem; font-weight:800; color:#111827; margin-top:.3rem; }
    .rd-kpi-delta  { font-size:.72rem; font-weight:700; margin-top:.4rem; display:inline-flex; align-items:center; gap:.25rem; }
    .rd-delta-up   { color:#16a34a; } .rd-delta-down { color:#dc2626; } .rd-delta-flat { color:#9ca3af; }
    @media (max-width:1100px) { .rd-kpi-grid { grid-template-columns:repeat(2,1fr); } }

    .rd-filter-bar   { display:flex; flex-wrap:wrap; align-items:flex-end; gap:.75rem; margin-bottom:1.5rem; }
    .rd-filter-group { display:flex; flex-direction:column; gap:.3rem; }
    .rd-filter-label { font-size:.7rem; font-weight:600; color:#6b7280; }
    .rd-filter-input { border:1px solid #e5e7eb; border-radius:.5rem; padding:.45rem .7rem; font-size:.82rem; color:#374151; background:#fff; outline:none; min-width:170px; }
    .rd-filter-input:focus { border-color:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,.12); }
    .rd-filter-btn   { background:#10b981; color:#fff; border:none; border-radius:.5rem; padding:.5rem 1.1rem; font-size:.8rem; font-weight:600; cursor:pointer; }
    .rd-filter-btn:hover { background:#059669; }
    .rd-filter-reset { font-size:.78rem; color:#6b7280; text-decoration:none; padding:.5rem .25rem; }
    .rd-filter-reset:hover { color:#374151; text-decoration:underline; }

    .rd-rutas-panel { position:absolute; top:calc(100% + 4px); left:0; z-index:50; min-width:240px; max-height:280px; overflow-y:auto; background:var(--card,#fff); border:1px solid var(--border,#e5e7eb); border-radius:.6rem; box-shadow:0 6px 18px rgba(0,0,0,.08); padding:.4rem; }
    .rd-rutas-item { display:flex; align-items:center; gap:.5rem; padding:.45rem .5rem; font-size:.82rem; color:var(--text-2,#374151); cursor:pointer; border-radius:.4rem; }
    .rd-rutas-item:hover { background:var(--subtle,#f9fafb); }
    .rd-rutas-item input { accent-color:#10b981; cursor:pointer; }
    .rd-cob-card.rd-oculta-por-filtro { display:none; }

    .rd-cob-card    { margin-bottom:1.1rem; overflow:hidden; }
    .rd-cob-header  { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.1rem; cursor:pointer; user-select:none; }
    .rd-cob-avatar  { width:42px; height:42px; border-radius:50%; background:#f1f5f9; display:flex; align-items:center; justify-content:center; flex-shrink:0; color:#64748b; }
    .rd-cob-name    { font-size:.92rem; font-weight:700; color:#111827; }
    .rd-cob-sub     { font-size:.76rem; color:#6b7280; margin-top:.1rem; }
    .rd-cob-total-wrap   { display:flex; align-items:center; gap:.85rem; }
    .rd-cob-total-label  { font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#9ca3af; text-align:right; }
    .rd-cob-total   { font-size:1.2rem; font-weight:800; color:#16a34a; text-align:right; }
    .rd-chevron     { transition:transform .2s; color:#9ca3af; flex-shrink:0; }
    .rd-cob-card.collapsed .rd-chevron { transform:rotate(180deg); }
    .rd-cob-card.collapsed .rd-cob-header { border-bottom:none; }
    .rd-cob-header  { border-bottom:1px solid #f3f4f6; }
    .rd-cob-body    { display:block; }
    .rd-cob-card.collapsed .rd-cob-body { display:none; }

    .rd-metodo-row   { display:flex; flex-wrap:wrap; gap:.5rem; padding:.75rem 1.1rem; border-bottom:1px solid #f3f4f6; }
    .rd-metodo-badge { display:inline-flex; align-items:center; gap:.4rem; border:1px solid #e5e7eb; border-radius:.5rem; padding:.3rem .65rem; font-size:.76rem; background:#fff; }
    .rd-metodo-dot   { width:7px; height:7px; border-radius:50%; flex-shrink:0; }

    .rd-subhead { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#9ca3af; padding:.85rem 1.1rem .35rem; }

    .rd-pago-table { width:100%; border-collapse:collapse; }
    .rd-pago-thead th { padding:.45rem .9rem; font-size:.63rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#9ca3af; background:#f9fafb; border-bottom:1px solid #f3f4f6; white-space:nowrap; text-align:left; }
    .rd-pago-thead th:last-child { text-align:right; }
    .rd-pago-tr td { padding:.45rem .9rem; font-size:.79rem; color:#374151; border-bottom:1px solid #f9fafb; white-space:nowrap; }
    .rd-pago-tr td:last-child { text-align:right; font-weight:700; color:#111827; }

    .rd-split { display:grid; grid-template-columns:1fr 1fr; border-top:1px solid #f3f4f6; }
    .rd-split > div + div { border-left:1px solid #f3f4f6; }
    @media (max-width:850px) { .rd-split { grid-template-columns:1fr; } .rd-split > div + div { border-left:none; border-top:1px solid #f3f4f6; } }

    .rd-visita-row  { display:flex; align-items:flex-start; gap:.6rem; padding:.7rem 1.1rem; border-bottom:1px solid #f9fafb; }
    .rd-avatar-init { width:34px; height:34px; border-radius:50%; font-size:.66rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .rd-visita-name { font-size:.81rem; font-weight:600; color:#111827; }
    .rd-resultado-badge { display:inline-flex; align-items:center; gap:.3rem; padding:.14rem .5rem; border-radius:9999px; font-size:.64rem; font-weight:700; margin-left:.4rem; }
    .rd-visita-time { font-size:.68rem; color:#9ca3af; margin-left:.4rem; }
    .rd-mapa-link   { font-size:.7rem; font-weight:600; color:#0369a1; text-decoration:none; display:inline-flex; align-items:center; gap:.3rem; margin-top:.35rem; }

    .rd-pendiente-row { display:flex; align-items:center; justify-content:space-between; padding:.5rem 1.1rem; border-bottom:1px solid #f9fafb; font-size:.79rem; }
    .rd-pendiente-name { color:#374151; }
    .rd-pendiente-tel  { color:#9ca3af; font-size:.72rem; }
    .rd-ver-todos { display:block; width:100%; text-align:center; padding:.6rem; font-size:.76rem; font-weight:600; color:#10b981; background:none; border:none; cursor:pointer; }
    .rd-ver-todos:hover { text-decoration:underline; }

    .rd-footer-grid  { display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-top:.5rem; }
    @media (max-width:1000px) { .rd-footer-grid { grid-template-columns:1fr; } }
    .rd-general-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:1rem; padding:1.1rem; }
    .rd-general-item { display:flex; align-items:center; gap:.7rem; }
    .rd-general-icon { width:38px; height:38px; border-radius:.6rem; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .rd-general-num  { font-size:1.1rem; font-weight:800; color:#111827; }
    .rd-general-label{ font-size:.72rem; color:#6b7280; }
    .rd-general-sub  { font-size:.65rem; color:#9ca3af; }

    .rd-activity-item  { display:flex; align-items:flex-start; gap:.65rem; padding:.6rem 1.1rem; border-bottom:1px solid #f9fafb; }
    .rd-activity-dot   { width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-top:.3rem; }
    .rd-activity-title { font-size:.79rem; font-weight:600; color:#111827; }
    .rd-activity-detalle { font-size:.73rem; color:#6b7280; }
    .rd-activity-time  { font-size:.67rem; color:#9ca3af; margin-left:auto; white-space:nowrap; padding-left:.5rem; }

    /* ── Modo oscuro ── */
    html.dark .rd-kpi-label, html.dark .rd-filter-label, html.dark .rd-cob-sub,
    html.dark .rd-general-label, html.dark .rd-activity-detalle { color:var(--muted); }
    html.dark .rd-kpi-num, html.dark .rd-cob-name, html.dark .rd-pago-tr td:last-child,
    html.dark .rd-visita-name, html.dark .rd-general-num, html.dark .rd-activity-title { color:var(--text); }
    html.dark .rd-delta-flat, html.dark .rd-cob-total-label, html.dark .rd-chevron,
    html.dark .rd-subhead, html.dark .rd-pago-thead th, html.dark .rd-visita-time,
    html.dark .rd-pendiente-tel, html.dark .rd-general-sub, html.dark .rd-activity-time { color:var(--muted-2); }
    html.dark .rd-filter-input { background:var(--card); border-color:var(--border); color:var(--text-2); }
    html.dark .rd-filter-reset { color:var(--muted); }
    html.dark .rd-filter-reset:hover { color:var(--text-2); }
    html.dark .rd-cob-avatar { background:var(--subtle); }
    html.dark .rd-cob-header { border-bottom-color:var(--border-2); }
    html.dark .rd-metodo-row { border-bottom-color:var(--border-2); }
    html.dark .rd-metodo-badge { background:var(--card); border-color:var(--border); }
    html.dark .rd-pago-thead th { background:var(--subtle); border-bottom-color:var(--border-2); }
    html.dark .rd-pago-tr td { color:var(--text-2); border-bottom-color:var(--border-2); }
    html.dark .rd-split { border-top-color:var(--border-2); }
    html.dark .rd-split > div + div { border-left-color:var(--border-2); }
    html.dark .rd-visita-row, html.dark .rd-pendiente-row, html.dark .rd-activity-item { border-bottom-color:var(--border-2); }
    html.dark .rd-pendiente-name { color:var(--text-2); }
</style>
@endsection

@section('content')
<div class="pm-page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
    <div>
        <h1>Resumen del día</h1>
        <p>Cobros, visitas y pendientes de cada cobrador</p>
    </div>
    <a href="{{ route('pos.monitor', $tenant) }}" style="display:inline-flex;align-items:center;gap:.4rem;background:var(--card);border:1px solid var(--border);border-radius:.625rem;padding:.55rem 1rem;font-size:.8rem;font-weight:600;color:var(--text-2);text-decoration:none;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Volver al monitor
    </a>
</div>

<form method="GET" action="{{ route('pos.resumen', $tenant) }}" class="rd-filter-bar">
    <div class="rd-filter-group">
        <label class="rd-filter-label">Fecha</label>
        <input type="date" name="fecha" value="{{ $fecha }}" class="rd-filter-input">
    </div>
    <div class="rd-filter-group">
        <label class="rd-filter-label">Cobrador</label>
        <select name="cobrador_id" class="rd-filter-input">
            <option value="">Todos los cobradores</option>
            @foreach($cobradores as $c)
                <option value="{{ $c->id }}" {{ (string) $cobradorId === (string) $c->id ? 'selected' : '' }}>{{ $c->nombre }} {{ $c->apellido }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="rd-filter-btn">Filtrar</button>
    @if($fecha !== today()->toDateString() || $cobradorId)
        <a href="{{ route('pos.resumen', $tenant) }}" class="rd-filter-reset">Limpiar filtros</a>
    @endif
</form>

@if($rutasDisponibles->count() > 1)
<div class="rd-filter-group" style="margin-bottom:1.5rem;position:relative;">
    <label class="rd-filter-label">Rutas</label>
    <button type="button" class="rd-filter-input" style="text-align:left;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:.5rem;" id="rd-rutas-btn">
        <span id="rd-rutas-label">Todas las rutas</span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="rd-rutas-panel" id="rd-rutas-panel" style="display:none;">
        <label class="rd-rutas-item" style="font-weight:700;border-bottom:1px solid var(--border-2);">
            <input type="checkbox" id="rd-ruta-todas" checked> Todas las rutas
        </label>
        @foreach($rutasDisponibles as $ruta)
            <label class="rd-rutas-item">
                <input type="checkbox" class="rd-ruta-check" value="{{ $ruta->id }}" checked> {{ $ruta->nombre }}
            </label>
        @endforeach
    </div>
</div>
@endif

{{-- ── KPIs con comparativo vs ayer ── --}}
<div class="rd-kpi-grid">
    @foreach($kpis as $k)
        <div class="pm-card rd-kpi-card">
            <div class="rd-kpi-label">{{ $k['label'] }}</div>
            <div class="rd-kpi-num">{{ $k['num'] }}</div>
            @if($k['delta'] == 0)
                <div class="rd-kpi-delta rd-delta-flat">Sin cambio vs ayer</div>
            @else
                <div class="rd-kpi-delta {{ $k['delta'] > 0 ? 'rd-delta-up' : 'rd-delta-down' }}">
                    {{ $k['delta'] > 0 ? '▲' : '▼' }} {{ abs($k['delta']) }}% vs ayer
                </div>
            @endif
        </div>
    @endforeach
</div>

{{-- ── Tarjetas por cobrador ── --}}
@forelse($resumen as $r)
    @php
        $c = $r['cobrador'];
        $ruta = $r['ruta'] ?? null;
        $rowKey = $c->id . '-' . ($ruta->id ?? 0);
        $tieneActividad = $r['total_pagos'] > 0 || $r['visitas_sin_cobro']->isNotEmpty() || $r['no_visitados']->isNotEmpty();
        $pendientesVisibles = $r['no_visitados']->take(5);
        $pendientesResto    = $r['no_visitados']->slice(5);
    @endphp
    <div class="pm-card rd-cob-card {{ $tieneActividad ? '' : 'collapsed' }}" id="cob-{{ $rowKey }}" data-ruta-id="{{ $ruta->id ?? '' }}">
        <div class="rd-cob-header" onclick="toggleCob('{{ $rowKey }}')">
            <div style="display:flex;align-items:center;gap:.75rem;min-width:0;">
                <div class="rd-cob-avatar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
                </div>
                <div style="min-width:0;">
                    <div class="rd-cob-name">{{ $c->nombre }} {{ $c->apellido }}{{ $ruta ? ' — '.$ruta->nombre : '' }}</div>
                    <div class="rd-cob-sub">{{ $r['total_pagos'] }} pago(s) &middot; {{ $r['clientes_visitados'] }} cliente(s) visitado(s)</div>
                </div>
            </div>
            <div class="rd-cob-total-wrap">
                <div>
                    <div class="rd-cob-total-label">Total cobrado</div>
                    <div class="rd-cob-total">${{ number_format($r['total_cobrado'], 2) }}</div>
                </div>
                <svg class="rd-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="18 15 12 9 6 15"/></svg>
            </div>
        </div>

        <div class="rd-cob-body">
            @if($r['por_metodo']->isNotEmpty())
            <div class="rd-metodo-row">
                @foreach($r['por_metodo'] as $metodo => $datos)
                    <div class="rd-metodo-badge">
                        <span class="rd-metodo-dot" style="background:{{ $metodoColor[$metodo] ?? '#6b7280' }};"></span>
                        <span style="font-weight:500;">{{ $metodoLabels[$metodo] ?? $metodo }}</span>
                        <span style="color:#9ca3af;">({{ $datos->cantidad }})</span>
                        <span style="font-weight:700;color:{{ $metodoColor[$metodo] ?? '#6b7280' }};">${{ number_format($datos->monto, 2) }}</span>
                    </div>
                @endforeach
            </div>
            @endif

            @if($r['detalle']->isNotEmpty())
                @php
                    $filas = $r['detalle']
                        ->groupBy(fn($p) => $p->cliente_id . '_' . $p->venta_id)
                        ->map(fn($grupo) => [
                            'codigo'        => $grupo->first()->cliente?->codigo_anterior ?? '—',
                            'cliente'       => $grupo->first()->cliente?->nombre_completo ?? '—',
                            'venta'         => $grupo->first()->venta?->numero_venta ?? '—',
                            'numero_recibo' => $grupo->pluck('numero_recibo')->filter()->unique()->implode(', ') ?: '—',
                            'metodo'     => $grupo->pluck('metodo_pago')->unique()->count() === 1 ? $grupo->first()->metodo_pago : 'varios',
                            'referencia' => $grupo->first()->referencia,
                            'hora'       => $grupo->first()->created_at->format('H:i'),
                            'total'      => $grupo->sum('monto'),
                            'saldo'      => $grupo->first()->venta?->saldo_pendiente,
                        ]);
                @endphp
                <div class="rd-subhead">Pagos registrados ({{ $filas->count() }})</div>
                <div style="overflow-x:auto;">
                    <table class="rd-pago-table">
                        <thead class="rd-pago-thead">
                            <tr><th>Hora</th><th>Código</th><th>Cliente</th><th>Venta</th><th>N° Recibo</th><th>Método</th><th>Referencia</th><th>Monto</th><th>Saldo restante</th></tr>
                        </thead>
                        <tbody>
                        @foreach($filas as $fila)
                            <tr class="rd-pago-tr">
                                <td style="font-variant-numeric:tabular-nums;color:#9ca3af;">{{ $fila['hora'] }}</td>
                                <td style="font-variant-numeric:tabular-nums;color:var(--muted);">{{ $fila['codigo'] }}</td>
                                <td style="font-weight:500;">{{ $fila['cliente'] }}</td>
                                <td style="color:var(--muted);">{{ $fila['venta'] }}</td>
                                <td style="color:var(--muted);font-size:.74rem;font-variant-numeric:tabular-nums;">{{ $fila['numero_recibo'] }}</td>
                                <td>
                                    <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:.74rem;font-weight:500;color:{{ $metodoColor[$fila['metodo']] ?? '#6b7280' }};">
                                        <span style="width:6px;height:6px;border-radius:50%;background:{{ $metodoColor[$fila['metodo']] ?? '#9ca3af' }};display:inline-block;"></span>
                                        {{ $metodoLabels[$fila['metodo']] ?? ucfirst($fila['metodo']) }}
                                    </span>
                                </td>
                                <td style="color:#9ca3af;font-size:.74rem;">{{ $fila['referencia'] ?? '—' }}</td>
                                <td>${{ number_format($fila['total'], 2) }}</td>
                                <td style="font-weight:600;color:{{ (float) $fila['saldo'] > 0 ? '#dc2626' : '#16a34a' }};">
                                    {{ $fila['saldo'] === null ? '—' : '$'.number_format((float) $fila['saldo'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($r['visitas_sin_cobro']->isNotEmpty() || $r['no_visitados']->isNotEmpty())
            <div class="rd-split">
                <div>
                    <div class="rd-subhead">Clientes visitados sin cobro ({{ $r['visitas_sin_cobro']->count() }})</div>
                    @forelse($r['visitas_sin_cobro'] as $i => $v)
                        @php
                            $rb = $resultadoBg[$v->resultado] ?? $resultadoBg['no_encontrado'];
                            $iniciales = mb_strtoupper(mb_substr($v->cliente?->nombre ?? '?', 0, 1) . mb_substr($v->cliente?->apellido ?? '', 0, 1));
                            [$avBg, $avColor] = explode(':', $avatarColors[$i % count($avatarColors)]);
                            $mapsUrl = ($v->latitud && $v->longitud) ? 'https://www.google.com/maps?q=' . $v->latitud . ',' . $v->longitud : null;
                        @endphp
                        <div class="rd-visita-row">
                            <div class="rd-avatar-init" style="background:{{ $avBg }};color:{{ $avColor }};">{{ $iniciales }}</div>
                            <div style="flex:1;min-width:0;">
                                <span class="rd-visita-name">{{ $v->cliente?->nombre_completo ?? '—' }}</span>
                                <div style="margin-top:.25rem;">
                                    <span class="rd-resultado-badge" style="background:{{ $rb['bg'] }};color:{{ $rb['color'] }};margin-left:0;">
                                        <span style="width:5px;height:5px;border-radius:50%;background:{{ $rb['dot'] }};display:inline-block;"></span>
                                        {{ $resultadoLabels[$v->resultado] ?? $v->resultado }}
                                    </span>
                                    <span class="rd-visita-time">{{ $v->created_at->format('H:i') }}</span>
                                </div>
                                @if($mapsUrl)
                                    <a href="{{ $mapsUrl }}" target="_blank" class="rd-mapa-link">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        Ver en mapa
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="pm-empty" style="padding:1.5rem;">Sin visitas registradas</div>
                    @endforelse
                </div>
                <div>
                    <div class="rd-subhead">Pendientes de visitar ({{ $r['no_visitados']->count() }})</div>
                    @forelse($pendientesVisibles as $nv)
                        <div class="rd-pendiente-row">
                            <span class="rd-pendiente-name">{{ $nv->nombre_completo }}</span>
                            <span class="rd-pendiente-tel">{{ $nv->telefono_normal ?? 'Sin teléfono' }}</span>
                        </div>
                    @empty
                        <div class="pm-empty" style="padding:1.5rem;">Sin pendientes</div>
                    @endforelse
                    @if($pendientesResto->isNotEmpty())
                        <div id="pend-more-{{ $rowKey }}" style="display:none;">
                            @foreach($pendientesResto as $nv)
                                <div class="rd-pendiente-row">
                                    <span class="rd-pendiente-name">{{ $nv->nombre_completo }}</span>
                                    <span class="rd-pendiente-tel">{{ $nv->telefono_normal ?? 'Sin teléfono' }}</span>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="rd-ver-todos" id="pend-btn-{{ $rowKey }}" onclick="toggleMorePendientes('{{ $rowKey }}')">
                            Ver todos los pendientes ({{ $r['no_visitados']->count() }}) →
                        </button>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
@empty
    <div class="pm-card pm-empty">
        Sin cobradores activos o sin datos para esta fecha.
    </div>
@endforelse

{{-- ── Footer: resumen general + actividad reciente ── --}}
<div class="rd-footer-grid">
    <div class="pm-card">
        <div class="pm-card-header"><span class="pm-card-title">Resumen general del día</span></div>
        <div class="rd-general-grid">
            <div class="rd-general-item">
                <div class="rd-general-icon" style="background:#dbeafe;color:#1d4ed8;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div>
                    <div class="rd-general-num">{{ $general['cobradores_activos'] }} / {{ $general['total_cobradores'] }}</div>
                    <div class="rd-general-label">Total de cobradores</div>
                    <div class="rd-general-sub">Activos hoy</div>
                </div>
            </div>
            <div class="rd-general-item">
                <div class="rd-general-icon" style="background:#dcfce7;color:#16a34a;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div>
                    <div class="rd-general-num">${{ number_format($general['total_esperado'], 2) }}</div>
                    <div class="rd-general-label">Total esperado</div>
                    <div class="rd-general-sub">Meta diaria</div>
                </div>
            </div>
            <div class="rd-general-item">
                <div class="rd-general-icon" style="background:#fef9c3;color:#ca8a04;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </div>
                <div>
                    <div class="rd-general-num">{{ $general['efectividad_cobro'] }}%</div>
                    <div class="rd-general-label">Efectividad de cobro</div>
                    <div class="rd-general-sub">vs visitas realizadas</div>
                </div>
            </div>
            <div class="rd-general-item">
                <div class="rd-general-icon" style="background:#ede9fe;color:#7c3aed;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
                </div>
                <div>
                    <div class="rd-general-num">${{ number_format($general['promedio_pago'], 2) }}</div>
                    <div class="rd-general-label">Promedio por pago</div>
                    <div class="rd-general-sub">Hoy</div>
                </div>
            </div>
        </div>
    </div>

    <div class="pm-card">
        <div class="pm-card-header"><span class="pm-card-title">Actividad reciente</span></div>
        @forelse($actividad as $a)
            <div class="rd-activity-item">
                <span class="rd-activity-dot" style="background:{{ $a['color'] }};"></span>
                <div style="min-width:0;">
                    <div class="rd-activity-title">{{ $a['titulo'] }}</div>
                    <div class="rd-activity-detalle">{{ $a['detalle'] }}</div>
                </div>
                <span class="rd-activity-time">{{ \Illuminate\Support\Carbon::parse($a['hora'])->format('H:i') }}</span>
            </div>
        @empty
            <div class="pm-empty">Sin actividad registrada hoy.</div>
        @endforelse
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleCob(id) {
    document.getElementById('cob-' + id).classList.toggle('collapsed');
}

// ── Filtro de rutas (todas / algunas / varias) ──────────────────────────────
(function () {
    var btn   = document.getElementById('rd-rutas-btn');
    var panel = document.getElementById('rd-rutas-panel');
    var label = document.getElementById('rd-rutas-label');
    var todasCheck = document.getElementById('rd-ruta-todas');
    if (!btn || !panel) return;

    var rutaChecks = Array.prototype.slice.call(document.querySelectorAll('.rd-ruta-check'));

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    });
    document.addEventListener('click', function (e) {
        if (!panel.contains(e.target) && e.target !== btn) panel.style.display = 'none';
    });

    function aplicarFiltro() {
        var seleccionadas = rutaChecks.filter(function (c) { return c.checked; }).map(function (c) { return c.value; });
        var todasMarcadas = seleccionadas.length === rutaChecks.length;

        document.querySelectorAll('.rd-cob-card').forEach(function (card) {
            var rutaId = card.dataset.rutaId || '';
            var visible = todasMarcadas || seleccionadas.indexOf(rutaId) !== -1;
            card.classList.toggle('rd-oculta-por-filtro', !visible);
        });

        if (todasMarcadas) {
            label.textContent = 'Todas las rutas';
        } else if (seleccionadas.length === 0) {
            label.textContent = 'Ninguna ruta';
        } else if (seleccionadas.length === 1) {
            label.textContent = document.querySelector('.rd-ruta-check[value="' + seleccionadas[0] + '"]').parentElement.textContent.trim();
        } else {
            label.textContent = seleccionadas.length + ' rutas seleccionadas';
        }
    }

    todasCheck.addEventListener('change', function () {
        rutaChecks.forEach(function (c) { c.checked = todasCheck.checked; });
        aplicarFiltro();
    });

    rutaChecks.forEach(function (check) {
        check.addEventListener('change', function () {
            todasCheck.checked = rutaChecks.every(function (c) { return c.checked; });
            aplicarFiltro();
        });
    });
})();
function toggleMorePendientes(id) {
    var el  = document.getElementById('pend-more-' + id);
    var btn = document.getElementById('pend-btn-' + id);
    var hidden = el.style.display === 'none';
    el.style.display = hidden ? 'block' : 'none';
    if (hidden) {
        btn.dataset.label = btn.dataset.label || btn.textContent;
        btn.textContent = 'Ver menos ←';
    } else if (btn.dataset.label) {
        btn.textContent = btn.dataset.label;
    }
}
</script>
@endsection
