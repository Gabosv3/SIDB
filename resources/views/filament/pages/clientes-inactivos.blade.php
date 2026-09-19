<x-filament-panels::page>
<style>
    .ci-toolbar { display:flex; align-items:center; gap:0.75rem; margin-bottom:1.25rem; flex-wrap:wrap; }
    .ci-toolbar label { font-size:0.75rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; }
    .ci-select { border:1px solid #d1d5db; border-radius:0.5rem; padding:0.45rem 0.75rem; font-size:0.85rem; color:#111827; background:#fff; }

    .ci-stat-card { background:#fff; border:1px solid #e5e7eb; border-radius:0.75rem; padding:1rem 1.2rem; margin-bottom:1.25rem; display:inline-flex; flex-direction:column; }
    .ci-stat-label { font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; }
    .ci-stat-num { font-size:1.4rem; font-weight:800; color:#dc2626; margin-top:.2rem; }

    .ci-card { background:#fff; border:1px solid #e5e7eb; border-radius:0.75rem; overflow:hidden; }
    .ci-thead th { padding:0.6rem 0.9rem; text-align:left; font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; background:#f9fafb; border-bottom:1px solid #e5e7eb; }
    .ci-tr { border-bottom:1px solid #f3f4f6; }
    .ci-tr:hover { background:#f9fafb; }
    .ci-td { padding:0.65rem 0.9rem; font-size:0.82rem; color:#374151; vertical-align:top; }
    .ci-empty { text-align:center; padding:3rem; color:#6b7280; font-size:0.85rem; }
    .ci-badge { display:inline-flex; align-items:center; padding:.15rem .55rem; border-radius:9999px; font-size:.7rem; font-weight:700; white-space:nowrap; }
    .ci-nunca { color:#dc2626; font-weight:700; }
    .ci-nombre { font-weight:700; color:#0f172a; }
    .ci-sub { font-size:0.75rem; color:#64748b; margin-top:0.1rem; }

    .dark .ci-toolbar label { color:#94a3b8; }
    .dark .ci-select { background:#2a2a35; border-color:#3f3f50; color:#f3f4f6; }
    .dark .ci-stat-card { background:#1e1e24; border-color:#2e2e3a; }
    .dark .ci-stat-label { color:#94a3b8; }
    .dark .ci-stat-num { color:#f87171; }
    .dark .ci-card { background:#1e1e24; border-color:#2e2e3a; }
    .dark .ci-thead th { background:#26262f; border-color:#2e2e3a; color:#94a3b8; }
    .dark .ci-tr { border-color:#2e2e3a; }
    .dark .ci-tr:hover { background:#26262f; }
    .dark .ci-td { color:#cbd5e1; }
    .dark .ci-empty { color:#94a3b8; }
    .dark .ci-nombre { color:#f1f5f9; }
    .dark .ci-sub { color:#94a3b8; }
</style>

@php
    $filas = $this->getClientes();
    $rutas = $this->getRutas();
    $cobradores = $this->getCobradores();
@endphp

<div class="ci-toolbar">
    <label>Sin visita ni abono desde hace</label>
    <select wire:model.live="dias" class="ci-select">
        <option value="15">15 días</option>
        <option value="30">1 mes</option>
        <option value="60">2 meses</option>
        <option value="90">3 meses</option>
    </select>

    <label>Ruta</label>
    <select wire:model.live="ruta_id" class="ci-select">
        <option value="">— Todas —</option>
        @foreach($rutas as $ruta)
            <option value="{{ $ruta->id }}">{{ $ruta->nombre }}</option>
        @endforeach
    </select>

    <label>Cobrador</label>
    <select wire:model.live="cobrador_id" class="ci-select">
        <option value="">— Todos —</option>
        @foreach($cobradores as $cobrador)
            <option value="{{ $cobrador->id }}">{{ $cobrador->nombre }} {{ $cobrador->apellido }}</option>
        @endforeach
    </select>
</div>

<div class="ci-stat-card">
    <span class="ci-stat-label">Clientes con saldo pendiente e inactivos</span>
    <span class="ci-stat-num">{{ $filas->count() }}</span>
</div>

<div class="ci-card">
    @if($filas->isEmpty())
        <div class="ci-empty">No hay clientes con saldo pendiente que lleven ese tiempo sin visita ni abono.</div>
    @else
        <table style="width:100%;border-collapse:collapse">
            <thead class="ci-thead">
                <tr>
                    <th>Cliente</th>
                    <th>Ruta / Cobrador</th>
                    <th>Saldo</th>
                    <th>Última visita</th>
                    <th>Último abono</th>
                </tr>
            </thead>
            <tbody>
                @foreach($filas as $fila)
                    @php $cliente = $fila['cliente']; @endphp
                    <tr class="ci-tr">
                        <td class="ci-td">
                            <div class="ci-nombre">{{ $cliente->nombre_completo }}</div>
                            <div class="ci-sub">{{ $cliente->telefono_whatsapp ?: $cliente->telefono_normal ?: 'Sin teléfono' }}</div>
                        </td>
                        <td class="ci-td">
                            {{ $cliente->rutaCobro?->nombre ?? '— Sin ruta —' }}
                            @if($cliente->rutaCobro?->cobrador)
                                <div class="ci-sub">{{ $cliente->rutaCobro->cobrador->nombre }} {{ $cliente->rutaCobro->cobrador->apellido }}</div>
                            @endif
                        </td>
                        <td class="ci-td" style="font-weight:700">${{ number_format($cliente->saldo, 2) }}</td>
                        <td class="ci-td">
                            @if($fila['ultima_visita'])
                                {{ $fila['ultima_visita']->format('d/m/Y') }}
                                <span class="ci-badge" style="background:#fee2e2;color:#dc2626">hace {{ $fila['dias_sin_visita'] }} días</span>
                            @else
                                <span class="ci-nunca">Nunca visitado</span>
                            @endif
                        </td>
                        <td class="ci-td">
                            @if($fila['ultimo_pago'])
                                {{ $fila['ultimo_pago']->format('d/m/Y') }}
                                <span class="ci-badge" style="background:#fee2e2;color:#dc2626">hace {{ $fila['dias_sin_pago'] }} días</span>
                            @else
                                <span class="ci-nunca">Nunca ha abonado</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
</x-filament-panels::page>
