<x-filament-panels::page>
<style>
    .cq-input { border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .7rem; font-size:.85rem; background:#fff; color:#111827; }
    .dark .cq-input { background:#2a2a35; border-color:#3f3f50; color:#f3f4f6; }
    .cq-table { width:100%; border-collapse:collapse; font-size:.85rem; background:#fff; border:1px solid #e5e7eb; border-radius:.5rem; }
    .dark .cq-table { background:#1e1e24; border-color:#2e2e3a; }
    .cq-table th { text-align:left; padding:.55rem .7rem; font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; background:#f9fafb; border-bottom:1px solid #e5e7eb; }
    .dark .cq-table th { background:#252530; border-color:#2e2e3a; color:#9ca3af; }
    .cq-table td { padding:.55rem .7rem; border-bottom:1px solid #f3f4f6; vertical-align:middle; color:#374151; }
    .dark .cq-table td { border-color:#2a2a35; color:#d1d5db; }
    .cq-table .n { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .cq-table tr.total td { font-weight:700; background:#f9fafb; }
    .dark .cq-table tr.total td { background:#252530; }
    .cq-badge { display:inline-block; padding:.12rem .55rem; border-radius:999px; font-size:.72rem; font-weight:700; }
    .cq-ok { background:#dcfce7; color:#166534; } .cq-falta { background:#ffe4e6; color:#9f1239; } .cq-sobra { background:#fef9c3; color:#854d0e; } .cq-pend { background:#f1f5f9; color:#475569; } .cq-cambio { background:#ede9fe; color:#5b21b6; }
    .cq-btn { padding:.4rem .8rem; border-radius:.5rem; background:#c0394b; color:#fff; font-size:.8rem; font-weight:600; border:none; cursor:pointer; }
    .cq-link { background:none; border:none; color:#6b7280; font-size:.72rem; cursor:pointer; text-decoration:underline; }
    .cq-pos { color:#854d0e; } .cq-neg { color:#dc2626; } .cq-cero { color:#16a34a; }
    .cq-nota { font-size:.75rem; color:#6b7280; margin-top:.6rem; }
</style>

@php
    $filas = $this->getFilas();
    $m = fn ($n) => '$' . number_format((float) $n, 2);
    $ms = fn ($n) => ((float) $n > 0 ? '+' : ((float) $n < 0 ? '−' : '')) . '$' . number_format(abs((float) $n), 2);
    $totEsp = $filas->sum('esperado');
    $guardadas = $filas->filter(fn ($f) => $f['cuadre']);
    $totRec = $guardadas->sum(fn ($f) => (float) $f['cuadre']->recibido);
    $totDif = $guardadas->sum(fn ($f) => (float) $f['cuadre']->diferencia);
@endphp

<div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:1rem;margin-bottom:1rem">
    <div>
        <label style="display:block;font-size:.75rem;font-weight:500;color:#6b7280;margin-bottom:.25rem">Fecha</label>
        <input type="date" wire:model.live="fecha" class="cq-input">
    </div>
    <span style="font-size:.85rem;color:#6b7280">{{ $guardadas->count() }} de {{ $filas->count() }} personas cuadradas</span>
</div>

@if($filas->isEmpty())
    <div class="cq-table" style="padding:2.5rem;text-align:center;color:#9ca3af">Sin cobros en efectivo, ventas al contado ni gastos en esta fecha.</div>
@else
    <div style="overflow-x:auto">
    <table class="cq-table">
        <thead>
            <tr>
                <th>Persona</th>
                <th class="n">Cobros (efectivo)</th>
                <th class="n">Ventas al contado</th>
                <th class="n">Gastos (−)</th>
                <th class="n">Debe entregar</th>
                <th>Efectivo recibido</th>
                <th class="n">Diferencia</th>
                <th>Estado</th>
                <th>Nota</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($filas as $f)
                @php
                    $c = $f['cuadre'];
                    $dif = $c ? (float) $c->diferencia : null;
                    $cambio = $c && abs((float) $c->esperado - $f['esperado']) > 0.004;
                @endphp
                <tr wire:key="cq-{{ $f['user_id'] }}">
                    <td><b>{{ $f['persona'] }}</b></td>
                    <td class="n">{{ $m($f['cobros']) }}</td>
                    <td class="n">{{ $m($f['ventas_contado']) }}</td>
                    <td class="n">{{ $f['gastos'] > 0 ? '− ' . $m($f['gastos']) : '—' }}</td>
                    <td class="n"><b>{{ $m($f['esperado']) }}</b></td>
                    <td><input type="number" step="0.01" min="0" wire:model="recibido.{{ $f['user_id'] }}" class="cq-input" style="width:95px" placeholder="0.00"></td>
                    <td class="n">
                        @if($c)
                            <b class="{{ $dif > 0.004 ? 'cq-pos' : ($dif < -0.004 ? 'cq-neg' : 'cq-cero') }}">{{ $ms($dif) }}</b>
                        @else — @endif
                    </td>
                    <td>
                        @if(! $c)<span class="cq-badge cq-pend">Sin cuadrar</span>
                        @elseif($dif < -0.004)<span class="cq-badge cq-falta">Faltante</span>
                        @elseif($dif > 0.004)<span class="cq-badge cq-sobra">Sobrante</span>
                        @else<span class="cq-badge cq-ok">Cuadrado</span>@endif
                        @if($cambio)<br><span class="cq-badge cq-cambio" title="Los movimientos del día cambiaron después de cuadrar: antes se esperaba {{ $m($c->esperado) }}">Cambió después</span>@endif
                        @if($c)<div style="font-size:.68rem;color:#9ca3af;margin-top:.15rem">{{ $c->cuadradoPor?->name ?? '—' }} · {{ $c->updated_at->format('d/m H:i') }}</div>@endif
                    </td>
                    <td><input type="text" wire:model="nota.{{ $f['user_id'] }}" class="cq-input" style="width:140px" placeholder="Nota (opcional)"></td>
                    <td style="white-space:nowrap">
                        <button type="button" class="cq-btn" wire:click="guardar({{ $f['user_id'] }})">{{ $c ? 'Actualizar' : 'Cuadrar' }}</button>
                        @if($c)<button type="button" class="cq-link" wire:click="borrar({{ $f['user_id'] }})" wire:confirm="¿Quitar este cuadre?">quitar</button>@endif
                    </td>
                </tr>
            @endforeach
            <tr class="total">
                <td>Total</td>
                <td class="n">{{ $m($filas->sum('cobros')) }}</td>
                <td class="n">{{ $m($filas->sum('ventas_contado')) }}</td>
                <td class="n">{{ $filas->sum('gastos') > 0 ? '− ' . $m($filas->sum('gastos')) : '—' }}</td>
                <td class="n">{{ $m($totEsp) }}</td>
                <td class="n">{{ $guardadas->isNotEmpty() ? $m($totRec) : '—' }}</td>
                <td class="n">{{ $guardadas->isNotEmpty() ? $ms($totDif) : '—' }}</td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>
    </div>
    <p class="cq-nota">Debe entregar = cobros en efectivo + lo que corresponde entregar por ventas al contado − gastos del día que descuentan del efectivo (cualquier estado). La diferencia es recibido − debe entregar: positiva = sobrante, negativa = faltante. Solo se registra; no descuenta nada a nadie.</p>
@endif
</x-filament-panels::page>
