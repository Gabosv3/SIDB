<x-filament-panels::page>
<style>
    .rc-input {
        border: 1px solid #d1d5db; border-radius: 0.5rem;
        padding: 0.5rem 0.75rem; font-size: 0.875rem;
        color: #111827; background: #fff;
        outline: none; transition: border-color .15s;
    }
    .rc-input:focus { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,.2); }

    .rc-card {
        background: #fff; border: 1px solid #e5e7eb;
        border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }
    .rc-stat-label {
        font-size: 0.7rem; font-weight: 600; text-transform: uppercase;
        letter-spacing: .05em; color: #6b7280;
    }
    .rc-thead { background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .rc-thead th {
        padding: 0.6rem 0.75rem; text-align: left;
        font-size: 0.7rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: .04em; color: #6b7280;
    }
    .rc-thead th:first-child { padding-left: 1.25rem; }
    .rc-thead th:last-child  { padding-right: 1.25rem; text-align: right; }
    .rc-tr { border-bottom: 1px solid #f3f4f6; transition: background .1s; }
    .rc-tr:hover { background: #f9fafb; }
    .rc-td { padding: 0.6rem 0.75rem; font-size: 0.825rem; color: #374151; }
    .rc-td:first-child { padding-left: 1.25rem; color: #9ca3af; }
    .rc-td:last-child  { padding-right: 1.25rem; text-align: right; font-weight: 700; color: #111827; }
    .rc-cobrador-header {
        display: flex; flex-wrap: wrap; align-items: center;
        justify-content: space-between; gap: 0.75rem;
        padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
    }
    .rc-badge {
        display: inline-flex; align-items: center; gap: 0.4rem;
        border: 1px solid #e5e7eb; border-radius: 0.5rem;
        padding: 0.35rem 0.75rem; background: #f9fafb; font-size: 0.8rem;
    }
    .rc-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .rc-empty {
        text-align: center; padding: 3rem;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem;
    }
    .rc-visita-header {
        display: flex; align-items: center; gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        background: #f8fafc;
        border-top: 2px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.78rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .05em;
        color: #64748b;
    }
    .rc-visita-row {
        display: flex; flex-wrap: wrap; align-items: flex-start;
        gap: 1rem; padding: 0.875rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        transition: background .1s;
    }
    .rc-visita-row:hover { background: #f8fafc; }
    .rc-badge-resultado {
        display: inline-flex; align-items: center;
        padding: 0.18rem 0.55rem; border-radius: 9999px;
        font-size: 0.68rem; font-weight: 700; letter-spacing: .03em;
    }

    /* ── Dark mode ── */
    .dark .rc-input         { background: #2a2a35; border-color: #3f3f50; color: #f3f4f6; }
    .dark .rc-input:focus   { border-color: #818cf8; box-shadow: 0 0 0 2px rgba(129,140,248,.25); }
    .dark .rc-card          { background: #1e1e24; border-color: #2e2e3a; box-shadow: none; }
    .dark .rc-stat-label    { color: #64748b; }
    .dark .rc-thead         { background: #252530; border-bottom-color: #2e2e3a; }
    .dark .rc-thead th      { color: #9ca3af; }
    .dark .rc-tr            { border-bottom-color: #2a2a35; }
    .dark .rc-tr:hover      { background: #252530; }
    .dark .rc-td            { color: #d1d5db; }
    .dark .rc-td:first-child{ color: #6b7280; }
    .dark .rc-td:last-child { color: #f3f4f6; }
    .dark .rc-cobrador-header { background: #252530; border-bottom-color: #2e2e3a; }
    .dark .rc-badge         { background: rgba(55,65,81,.5); border-color: #4b5563; }
    .dark .rc-empty         { background: #1f2937; border-color: #374151; color: #9ca3af; }
    .dark .rc-visita-header  { background: #252530; border-top-color: #2e2e3a; border-bottom-color: #2e2e3a; color: #94a3b8; }
    .dark .rc-visita-row     { border-bottom-color: #2a2a35; }
    .dark .rc-visita-row:hover { background: #252530; }

    /* Texto con contraste en dark */
    .rc-text-primary   { color: #0f172a; }
    .rc-text-secondary { color: #475569; }
    .rc-text-muted     { color: #64748b; }
    .rc-text-time      { color: #94a3b8; font-variant-numeric: tabular-nums; font-size: 0.72rem; }
    .dark .rc-text-primary   { color: #f1f5f9; }
    .dark .rc-text-secondary { color: #cbd5e1; }
    .dark .rc-text-muted     { color: #94a3b8; }
    .dark .rc-text-time      { color: #64748b; }

    /* Badges resultado con dark mode */
    .rc-badge-sin-pago      { background:#fef9c3; color:#854d0e; }
    .rc-badge-promesa       { background:#e0f2fe; color:#0369a1; }
    .rc-badge-no-encontrado { background:#f1f5f9; color:#475569; }
    .rc-badge-rechazo       { background:#ffe4e6; color:#9f1239; }
    .rc-badge-abono-previo  { background:#dcfce7; color:#166534; }
    .dark .rc-badge-sin-pago      { background:rgba(202,138,4,.18);  color:#fde047; }
    .dark .rc-badge-promesa       { background:rgba(2,132,199,.18);  color:#38bdf8; }
    .dark .rc-badge-no-encontrado { background:rgba(100,116,139,.18);color:#94a3b8; }
    .dark .rc-badge-rechazo       { background:rgba(225,29,72,.18);  color:#fb7185; }
    .dark .rc-badge-abono-previo  { background:rgba(34,197,94,.18);  color:#86efac; }

    /* Promesa y observaciones */
    .rc-promesa { color:#0369a1; font-size:0.75rem; font-weight:500; display:inline-flex; align-items:center; gap:0.3rem; }
    .rc-obs     { color:#64748b; font-size:0.78rem; line-height:1.4; margin:0; }
    .dark .rc-promesa { color:#38bdf8; }
    .dark .rc-obs     { color:#94a3b8; }

    /* Botón mapa */
    .rc-mapa-btn {
        display:inline-flex; align-items:center; gap:0.4rem;
        font-size:0.75rem; font-weight:600; text-decoration:none;
        border-radius:0.5rem; padding:0.4rem 0.75rem; white-space:nowrap;
        color:#0369a1; background:#f0f9ff; border:1px solid #bae6fd;
        transition: all .15s;
    }
    .rc-mapa-btn:hover { background:#e0f2fe; border-color:#7dd3fc; }
    .dark .rc-mapa-btn { color:#38bdf8; background:rgba(2,132,199,.12); border-color:rgba(56,189,248,.25); }
    .dark .rc-mapa-btn:hover { background:rgba(2,132,199,.22); border-color:rgba(56,189,248,.4); }

    /* Foto sin imagen */
    .rc-foto-empty { background:#f1f5f9; border:2px dashed #cbd5e1; }
    .dark .rc-foto-empty { background:rgba(30,41,59,.5); border-color:#334155; }

    /* Cobrador header textos */
    .rc-cobrador-name { font-size:1rem; font-weight:700; color:#111827; }
    .rc-cobrador-sub  { font-size:0.8rem; color:#6b7280; margin-top:0.1rem; }
    .dark .rc-cobrador-name { color:#f1f5f9; }
    .dark .rc-cobrador-sub  { color:#94a3b8; }

    /* No visitados */
    .rc-novisit-header {
        display: flex; align-items: center; gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        background: #fafafa;
        border-top: 2px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.78rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .05em;
        color: #64748b;
    }
    .rc-novisit-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 0.6rem 1.25rem; border-bottom: 1px solid #f1f5f9;
        gap: 1rem;
    }
    .rc-novisit-row:hover { background: #f8fafc; }
    .rc-novisit-name { font-size: 0.85rem; font-weight: 500; color: #334155; }
    .rc-novisit-tel  { font-size: 0.75rem; color: #94a3b8; }
    .dark .rc-novisit-header { background: rgba(15,23,42,.5); border-top-color: #1e293b; border-bottom-color: #1e293b; color: #64748b; }
    .dark .rc-novisit-row    { border-bottom-color: #1e293b; }
    .dark .rc-novisit-row:hover { background: rgba(30,41,59,.3); }
    .dark .rc-novisit-name   { color: #cbd5e1; }
    .dark .rc-novisit-tel    { color: #475569; }

    /* Monto cobrado */
    .rc-monto-cobrado       { font-size:1.5rem; font-weight:700; color:#16a34a; margin-top:0.1rem; }
    .rc-monto-total         { font-size:1.875rem; font-weight:700; color:#16a34a; margin-top:0.25rem; }
    .dark .rc-monto-cobrado { color:#ffffff; }
    .dark .rc-monto-total   { color:#ffffff; }
</style>

@php
    $resumen          = $this->getResumen();
    $totales          = $this->getTotalesGenerales($resumen);
    $cobradores       = $this->getCobradores();
    $rutasDisponibles = $this->getRutasDisponibles();

    $resultadoLabels = [
        'sin_pago'      => 'Sin pago',
        'promesa_pago'  => 'Promesa de pago',
        'no_encontrado' => 'No encontrado',
        'rechazo'       => 'Rechazo',
        'abono_previo'  => 'Abono previo',
    ];
    $resultadoBg = [
        'sin_pago'      => ['bg' => '#fef9c3', 'color' => '#854d0e', 'dot' => '#ca8a04'],
        'promesa_pago'  => ['bg' => '#e0f2fe', 'color' => '#0369a1', 'dot' => '#0284c7'],
        'no_encontrado' => ['bg' => '#f1f5f9', 'color' => '#475569', 'dot' => '#94a3b8'],
        'rechazo'       => ['bg' => '#ffe4e6', 'color' => '#9f1239', 'dot' => '#e11d48'],
        'abono_previo'  => ['bg' => '#dcfce7', 'color' => '#166534', 'dot' => '#16a34a'],
    ];
    $metodoLabels = [
        'efectivo'      => 'Efectivo',
        'transferencia' => 'Transferencia',
        'cheque'        => 'Cheque',
        'deposito'      => 'Depósito',
    ];
    $metodoDot = [
        'efectivo'      => '#16a34a',
        'transferencia' => '#2563eb',
        'cheque'        => '#9333ea',
        'deposito'      => '#d97706',
    ];
    $metodoColor = [
        'efectivo'      => '#16a34a',
        'transferencia' => '#2563eb',
        'cheque'        => '#9333ea',
        'deposito'      => '#d97706',
    ];
@endphp

{{-- Filtros --}}
<div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:1rem;margin-bottom:1.5rem">
    <div>
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Fecha</label>
        <input type="date" wire:model.live="fecha" class="rc-input" />
    </div>
    <div>
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Cobrador</label>
        <select wire:model.live="cobrador_id" class="rc-input" style="min-width:200px">
            <option value="">Todos los cobradores</option>
            @foreach($cobradores as $c)
                <option value="{{ $c->id }}">{{ $c->nombre }} {{ $c->apellido }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Buscar cliente</label>
        <input
            type="text"
            wire:model.live.debounce.400ms="buscarCliente"
            placeholder="Nombre o código del cliente..."
            class="rc-input"
            style="min-width:220px"
        />
    </div>
    @if($rutasDisponibles->count() > 1)
        <div x-data="{ open: false }" style="position:relative">
            <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Rutas</label>
            <button
                type="button"
                @click="open = !open"
                class="rc-input"
                style="min-width:200px;text-align:left;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:.5rem"
            >
                <span>
                    @if(empty($rutasSeleccionadas))
                        Todas las rutas
                    @elseif(count($rutasSeleccionadas) === 1)
                        {{ $rutasDisponibles->firstWhere('id', $rutasSeleccionadas[0])?->nombre }}
                    @else
                        {{ count($rutasSeleccionadas) }} rutas seleccionadas
                    @endif
                </span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div
                x-show="open"
                @click.outside="open = false"
                x-cloak
                style="position:absolute;top:calc(100% + 4px);left:0;z-index:50;min-width:220px;max-height:280px;overflow-y:auto;background:#fff;border:1px solid #e5e7eb;border-radius:.5rem;box-shadow:0 6px 18px rgba(0,0,0,.1);padding:.4rem"
            >
                <label style="display:flex;align-items:center;gap:.5rem;padding:.4rem .5rem;font-size:.8rem;cursor:pointer;border-bottom:1px solid #f3f4f6;font-weight:600;color:#111827">
                    <input type="checkbox" {{ empty($rutasSeleccionadas) ? 'checked' : '' }} wire:click="$set('rutasSeleccionadas', [])">
                    Todas las rutas
                </label>
                @foreach($rutasDisponibles as $rutaOpt)
                    <label style="display:flex;align-items:center;gap:.5rem;padding:.4rem .5rem;font-size:.8rem;cursor:pointer;color:#111827">
                        <input type="checkbox" value="{{ $rutaOpt->id }}" wire:model.live="rutasSeleccionadas">
                        {{ $rutaOpt->nombre }}
                    </label>
                @endforeach
            </div>
        </div>
    @endif
</div>

{{-- Tarjetas totales --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.75rem">
    <div class="rc-card" style="padding:1.25rem">
        <p class="rc-stat-label">Total cobrado</p>
        <p class="rc-monto-total">${{ number_format($totales['total_cobrado'], 2) }}</p>
    </div>
    <div class="rc-card" style="padding:1.25rem">
        <p class="rc-stat-label">Pagos registrados</p>
        <p class="rc-monto-total">{{ $totales['total_pagos'] }}</p>
    </div>
    <div class="rc-card" style="padding:1.25rem">
        <p class="rc-stat-label">Clientes visitados</p>
        <p class="rc-monto-total">{{ $totales['clientes_visitados'] }}</p>
    </div>
    <div class="rc-card" style="padding:1.25rem">
        <p class="rc-stat-label">Visitas sin cobro</p>
        <p class="rc-monto-total">{{ $totales['total_sin_cobro'] }}</p>
    </div>
    <div class="rc-card" style="padding:1.25rem">
        <p class="rc-stat-label">Sin visitar</p>
        <p class="rc-monto-total">{{ $totales['total_no_visitados'] }}</p>
    </div>
</div>

@php $valesDia = $this->getValesDia(); @endphp
@if($valesDia->isNotEmpty())
    @php
        $cobradoPorCobrador = collect($resumen)
            ->groupBy(fn ($r) => $r['cobrador']->id)
            ->map(fn ($grupo) => (float) $grupo->sum('total_cobrado'));
    @endphp
    {{-- Gastos del día (vales) --}}
    <div class="rc-card" style="margin-bottom:1.75rem;overflow:hidden">
        <div class="rc-cobrador-header">
            <p class="rc-cobrador-name">Gastos del día (vales) — para cuadrar lo que cada cobrador debe entregar</p>
        </div>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead class="rc-thead">
                    <tr>
                        <th>Cobrador</th>
                        <th>Cobrado</th>
                        <th>Vale consumo</th>
                        <th>Vale vehículo</th>
                        <th>Total gastado</th>
                        <th>Neto a entregar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($valesDia as $v)
                        @php $cobradoCobrador = $cobradoPorCobrador->get($v['cobrador']->id, 0.0); @endphp
                        <tr class="rc-tr">
                            <td class="rc-td" style="color:inherit">{{ $v['cobrador']->nombre }} {{ $v['cobrador']->apellido }}</td>
                            <td class="rc-td">${{ number_format($cobradoCobrador, 2) }}</td>
                            <td class="rc-td">${{ number_format($v['total_consumo'], 2) }}</td>
                            <td class="rc-td">${{ number_format($v['total_vehiculo'], 2) }}</td>
                            <td class="rc-td">${{ number_format($v['total_gastado'], 2) }}</td>
                            <td class="rc-td">${{ number_format($cobradoCobrador - $v['total_gastado'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p style="font-size:0.7rem;color:#9ca3af;padding:0.6rem 1.25rem 0.9rem">Incluye vales en cualquier estado (pendiente, aprobado o rechazado) — refleja el efectivo que salió de su mano ese día. La deducción de la comisión semanal solo cuenta los vales de consumo ya aprobados.</p>
    </div>
@endif

{{-- Cards por cobrador --}}
@forelse($resumen as $r)
    @php $c = $r['cobrador']; $ruta = $r['ruta'] ?? null; @endphp
    <div class="rc-card" style="margin-bottom:1.25rem;overflow:hidden">

        {{-- Header --}}
        <div class="rc-cobrador-header">
            <div>
                <p class="rc-cobrador-name">{{ $c->nombre }} {{ $c->apellido }}{{ $ruta ? ' — '.$ruta->nombre : '' }}</p>
                <p class="rc-cobrador-sub">
                    {{ $r['total_pagos'] }} pago(s) &middot; {{ $r['clientes_visitados'] }} cliente(s) visitado(s)
                </p>
            </div>
            <div style="text-align:right">
                <p class="rc-stat-label">Total cobrado</p>
                <p class="rc-monto-cobrado">${{ number_format($r['total_cobrado'], 2) }}</p>
            </div>
        </div>

        {{-- Métodos de pago --}}
        @if($r['por_metodo']->isNotEmpty())
            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;padding:0.875rem 1.25rem;border-bottom:1px solid #f3f4f6">
                @foreach($r['por_metodo'] as $metodo => $datos)
                    <div class="rc-badge">
                        <span class="rc-dot" style="background:{{ $metodoDot[$metodo] ?? '#6b7280' }}"></span>
                        <span style="font-weight:500;color:#374151" class="dark-text">{{ $metodoLabels[$metodo] ?? $metodo }}</span>
                        <span style="color:#9ca3af">({{ $datos->cantidad }})</span>
                        <span style="font-weight:700;color:{{ $metodoColor[$metodo] ?? '#6b7280' }}">${{ number_format($datos->monto, 2) }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Tabla de pagos --}}
        @if($r['detalle']->isNotEmpty())
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse">
                    <thead class="rc-thead">
                        <tr>
                            <th>Hora</th>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Venta</th>
                            <th>N° Recibo</th>
                            <th>Método</th>
                            <th>Referencia</th>
                            <th style="text-align:right">Monto</th>
                            <th style="text-align:right">Saldo restante</th>
                            <th style="padding-right:1.25rem"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // Agrupar por cliente+venta → una sola fila con el total
                            $filas = $r['detalle']
                                ->groupBy(fn($p) => $p->cliente_id . '_' . $p->venta_id)
                                ->map(fn($grupo) => [
                                    'ids'           => $grupo->pluck('id')->all(),
                                    'codigo'        => $grupo->first()->cliente?->codigo_anterior ?? '—',
                                    'cliente'       => $grupo->first()->cliente?->nombre_completo ?? '—',
                                    'venta'         => $grupo->first()->venta?->numero_venta ?? '—',
                                    'numero_recibo' => $grupo->pluck('numero_recibo')->filter()->unique()->implode(', ') ?: '—',
                                    'metodo'     => $grupo->pluck('metodo_pago')->unique()->count() === 1
                                                        ? $grupo->first()->metodo_pago
                                                        : 'varios',
                                    'referencia' => $grupo->first()->referencia,
                                    'hora'       => $grupo->first()->fecha_pago->isSameDay($grupo->first()->created_at)
                                                        ? $grupo->first()->created_at->format('H:i')
                                                        : 'Registrado después',
                                    'total'      => $grupo->sum('monto'),
                                    'count'      => $grupo->count(),
                                    'saldo'      => $grupo->first()->venta?->saldo_pendiente,
                                ]);
                        @endphp
                        @foreach($filas as $fila)
                            <tr class="rc-tr">
                                <td class="rc-td" style="white-space:nowrap;font-variant-numeric:tabular-nums">
                                    {{ $fila['hora'] }}
                                </td>
                                <td class="rc-td" style="color:#6b7280;font-variant-numeric:tabular-nums">
                                    {{ $fila['codigo'] }}
                                </td>
                                <td class="rc-td rc-text-primary" style="font-weight:500">
                                    {{ $fila['cliente'] }}
                                </td>
                                <td class="rc-td" style="color:#6b7280">
                                    {{ $fila['venta'] }}
                                </td>
                                <td class="rc-td" style="color:#6b7280;font-size:0.75rem;font-variant-numeric:tabular-nums">
                                    {{ $fila['numero_recibo'] }}
                                </td>
                                <td class="rc-td">
                                    <span style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.75rem;font-weight:500;color:{{ $metodoColor[$fila['metodo']] ?? '#6b7280' }}">
                                        <span class="rc-dot" style="width:6px;height:6px;background:{{ $metodoDot[$fila['metodo']] ?? '#9ca3af' }}"></span>
                                        {{ $metodoLabels[$fila['metodo']] ?? ucfirst($fila['metodo']) }}
                                    </span>
                                </td>
                                <td class="rc-td" style="color:#9ca3af;font-size:0.75rem">
                                    {{ $fila['referencia'] ?? '—' }}
                                </td>
                                <td class="rc-td rc-text-primary" style="text-align:right;font-weight:700">${{ number_format($fila['total'], 2) }}</td>
                                <td class="rc-td" style="text-align:right;color:{{ (float) $fila['saldo'] > 0 ? '#dc2626' : '#16a34a' }};font-weight:600">
                                    {{ $fila['saldo'] === null ? '—' : '$'.number_format((float) $fila['saldo'], 2) }}
                                </td>
                                <td class="rc-td" style="text-align:right;padding-right:1.25rem">
                                    <button
                                        type="button"
                                        title="Eliminar pago"
                                        wire:click="mountAction('eliminarPago', {{ Illuminate\Support\Js::from(['ids' => $fila['ids'], 'cliente' => $fila['cliente'], 'monto' => $fila['total']]) }})"
                                        style="display:inline-flex;align-items:center;justify-content:center;width:1.75rem;height:1.75rem;border-radius:0.375rem;border:1px solid #fecaca;background:#fef2f2;color:#dc2626;cursor:pointer"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6h16Z"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Visitas sin cobro --}}
        @if($r['visitas_sin_cobro']->isNotEmpty())
            <div class="rc-visita-header">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16zm-1-5h2v2h-2zm0-8h2v6h-2z"/></svg>
                Clientes visitados sin cobro ({{ $r['visitas_sin_cobro']->count() }})
            </div>

            {{-- Filas por visita --}}
            @foreach($r['visitas_sin_cobro'] as $v)
                @php
                    $badgeClass = [
                        'sin_pago'      => 'rc-badge-sin-pago',
                        'promesa_pago'  => 'rc-badge-promesa',
                        'no_encontrado' => 'rc-badge-no-encontrado',
                        'rechazo'       => 'rc-badge-rechazo',
                        'abono_previo'  => 'rc-badge-abono-previo',
                    ][$v->resultado] ?? 'rc-badge-no-encontrado';
                    $rb      = $resultadoBg[$v->resultado] ?? ['dot'=>'#94a3b8'];
                    $fotoUrl = $v->foto_hogar ? \Illuminate\Support\Facades\Storage::url($v->foto_hogar) : null;
                    $mapsUrl = ($v->latitud && $v->longitud)
                        ? 'https://www.google.com/maps?q=' . $v->latitud . ',' . $v->longitud
                        : null;
                @endphp
                <div class="rc-visita-row">

                    {{-- Foto --}}
                    @if($fotoUrl)
                        <a href="{{ $fotoUrl }}" target="_blank" title="Ver foto completa"
                           style="flex-shrink:0;width:68px;height:68px;border-radius:0.5rem;overflow:hidden;display:block;border:2px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.08)">
                            <img src="{{ $fotoUrl }}" alt="Foto hogar"
                                 style="width:68px;height:68px;object-fit:cover;display:block;transition:transform .2s"
                                 onmouseover="this.style.transform='scale(1.07)'" onmouseout="this.style.transform='scale(1)'">
                        </a>
                    @else
                        <div class="rc-foto-empty" style="flex-shrink:0;width:68px;height:68px;border-radius:0.5rem;display:flex;align-items:center;justify-content:center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#94a3b8" stroke-width="1.5" viewBox="0 0 24 24">
                                <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
                                <path d="m21 15-5-5L5 21"/>
                            </svg>
                        </div>
                    @endif

                    {{-- Info principal --}}
                    <div style="flex:1;min-width:160px;display:flex;flex-direction:column;gap:0.3rem">
                        <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap">
                            <span class="rc-text-primary" style="font-weight:600;font-size:0.875rem">
                                {{ $v->cliente?->nombre_completo ?? '—' }}
                            </span>
                            <span class="rc-badge-resultado {{ $badgeClass }}">
                                <span style="width:5px;height:5px;border-radius:50%;background:{{ $rb['dot'] ?? '#94a3b8' }};display:inline-block;margin-right:4px"></span>
                                {{ $resultadoLabels[$v->resultado] ?? $v->resultado }}
                            </span>
                            <span class="rc-text-time">{{ $v->created_at->format('H:i') }}</span>
                        </div>

                        @if($v->promesa_fecha)
                            <div class="rc-promesa">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
                                Promete pagar el {{ $v->promesa_fecha->format('d/m/Y') }}
                            </div>
                        @endif

                        @if($v->observaciones)
                            <p class="rc-obs">"{{ $v->observaciones }}"</p>
                        @endif
                    </div>

                    {{-- Ubicación --}}
                    <div style="flex-shrink:0;align-self:center">
                        @if($mapsUrl)
                            <a href="{{ $mapsUrl }}" target="_blank" class="rc-mapa-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/>
                                </svg>
                                Ver en mapa
                            </a>
                        @else
                            <span class="rc-text-time" style="font-style:italic">Sin ubicación</span>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif

        {{-- Clientes no visitados --}}
        @if($r['no_visitados']->isNotEmpty())
            <div class="rc-novisit-header">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                Pendientes de visitar ({{ $r['no_visitados']->count() }})
            </div>
            @foreach($r['no_visitados'] as $nv)
                <div class="rc-novisit-row">
                    <div>
                        <span class="rc-novisit-name">{{ $nv->nombre_completo }}</span>
                    </div>
                    <span class="rc-novisit-tel">{{ $nv->telefono_normal ?? 'Sin teléfono' }}</span>
                </div>
            @endforeach
        @endif
    </div>
@empty
    <div class="rc-empty">
        @if(trim($this->buscarCliente) !== '')
            <p style="font-size:1rem;font-weight:500;color:#6b7280">Ningún cliente coincide con "{{ $this->buscarCliente }}"</p>
            <p style="font-size:0.875rem;color:#9ca3af;margin-top:0.25rem">Prueba con otro nombre o borra la búsqueda</p>
        @else
            <p style="font-size:1rem;font-weight:500;color:#6b7280">Sin cobros registrados para esta fecha</p>
            <p style="font-size:0.875rem;color:#9ca3af;margin-top:0.25rem">Selecciona otra fecha o verifica que los cobradores hayan registrado pagos</p>
        @endif
    </div>
@endforelse

</x-filament-panels::page>
