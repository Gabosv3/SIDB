<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de cuenta — {{ $cliente->nombre_completo }}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size:11px; color:#1f2937; }
        .page { padding:28px 36px; }

        table.header { width:100%; border-collapse:collapse; margin-bottom:10px; }
        table.header td { vertical-align:middle; }
        .header-logo { width:50%; }
        .header-logo img { max-height:95px; }
        .header-logo .empresa { font-size:16px; font-weight:700; letter-spacing:.5px; color:#111827; margin-top:6px; }
        .header-logo .subtitulo { font-size:8.5px; letter-spacing:1.5px; color:#6b7280; margin-top:2px; text-transform:uppercase; }
        .header-contacto { width:50%; text-align:right; font-size:9.5px; color:#4b5563; line-height:1.7; }
        .header-contacto strong { color:#111827; }

        .divisor { border-top:1px solid #d1d5db; margin:12px 0 16px; }

        .titulo { text-align:center; font-size:16px; font-weight:700; letter-spacing:1px; color:#111827; margin-bottom:16px; }

        table.datos-cliente { width:100%; border-collapse:collapse; margin-bottom:18px; }
        table.datos-cliente td { padding:2px 0; font-size:10.5px; vertical-align:top; }
        table.datos-cliente td.label { color:#6b7280; width:90px; font-weight:600; }
        table.datos-cliente td.derecha { text-align:right; }

        .resumen-grid { width:100%; border-collapse:collapse; margin-bottom:20px; }
        .resumen-grid td { width:33.33%; border:1px solid #e5e7eb; padding:8px 10px; text-align:center; }
        .resumen-grid .label { font-size:8px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#6b7280; }
        .resumen-grid .valor { font-size:13px; font-weight:700; color:#111827; margin-top:3px; }
        .resumen-grid .valor.rojo { color:#b91c1c; }
        .resumen-grid .valor.verde { color:#15803d; }

        .venta-header { background:#f3f4f6; padding:6px 8px; font-size:10.5px; font-weight:700; color:#111827; margin-top:14px; border:1px solid #e5e7eb; border-bottom:none; }
        .venta-sub { font-weight:400; color:#6b7280; font-size:9.5px; }
        .venta-productos { font-weight:400; color:#4b5563; font-size:9.5px; margin-top:2px; }

        table.movimientos { width:100%; border-collapse:collapse; margin-bottom:4px; }
        table.movimientos th { background:#fff; border:1px solid #e5e7eb; padding:5px 8px; font-size:8.5px; font-weight:700; text-transform:uppercase; letter-spacing:.3px; color:#6b7280; text-align:left; }
        table.movimientos td { border:1px solid #e5e7eb; padding:5px 8px; font-size:10px; color:#374151; }
        table.movimientos td.derecha { text-align:right; }
        table.movimientos tr.anulado td { color:#b91c1c; font-style:italic; }

        .sin-movimientos { padding:8px; border:1px solid #e5e7eb; font-size:10px; color:#9ca3af; text-align:center; font-style:italic; }

        .pie { text-align:center; font-size:8px; color:#9ca3af; margin-top:24px; }
    </style>
</head>
<body>
<div class="page">

    <table class="header">
        <tr>
            <td class="header-logo">
                @if($config->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($config->logo))
                    <img src="{{ storage_path('app/public/'.$config->logo) }}" alt="Logo"><br>
                @else
                    <span class="empresa">{{ strtoupper($config->app_name ?? 'SIDB') }}</span><br>
                @endif
                <span class="subtitulo">Estado de cuenta del cliente</span>
            </td>
            <td class="header-contacto">
                @if($config->direccion)
                    <div>{{ $config->direccion }}</div>
                @endif
                @if($config->telefono)
                    <div><strong>Tel:</strong> {{ $config->telefono }}</div>
                @endif
                @if($config->correo_contacto)
                    <div>{{ $config->correo_contacto }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="divisor"></div>

    <div class="titulo">ESTADO DE CUENTA</div>

    <table class="datos-cliente">
        <tr>
            <td class="label">Cliente:</td>
            <td><strong>{{ $cliente->nombre_completo }}</strong>{{ $cliente->codigo_anterior ? ' (Cód. '.$cliente->codigo_anterior.')' : '' }}</td>
            <td class="label" style="width:80px;">Emitido:</td>
            <td class="derecha">{{ $fechaEmision->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Dirección:</td>
            <td colspan="3">{{ $cliente->direccion ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Teléfono:</td>
            <td colspan="3">{{ $cliente->telefono_normal ?: '—' }}</td>
        </tr>
    </table>

    <table class="resumen-grid">
        <tr>
            <td>
                <div class="label">Total comprado</div>
                <div class="valor">${{ number_format($totalVendido, 2) }}</div>
            </td>
            <td>
                <div class="label">Total pagado</div>
                <div class="valor verde">${{ number_format($totalPagado, 2) }}</div>
            </td>
            <td>
                <div class="label">Saldo actual</div>
                <div class="valor {{ $saldoActual > 0 ? 'rojo' : 'verde' }}">${{ number_format($saldoActual, 2) }}</div>
            </td>
        </tr>
    </table>

    @forelse($ventasConMovimientos as $item)
        @php $venta = $item['venta']; @endphp
        <div class="venta-header">
            Venta {{ $venta->numero_venta }} — {{ $venta->fecha_venta->format('d/m/Y') }}
            <span class="venta-sub">
                &middot; Total: ${{ number_format((float) $venta->total, 2) }}
                &middot; Saldo actual: ${{ number_format((float) $venta->saldo_pendiente, 2) }}
                &middot; Estado: {{ ucfirst($venta->estado) }}
                &middot; Vendedor: {{ $venta->vendedor ? trim($venta->vendedor->nombre.' '.$venta->vendedor->apellido) : '—' }}
            </span>
            <div class="venta-productos">
                Producto(s): {{ $venta->detalles->map(fn ($d) => $d->producto?->nombre ?? '—')->implode(', ') ?: '—' }}
            </div>
        </div>
        @if($item['movimientos']->isEmpty())
            <div class="sin-movimientos">Sin abonos registrados todavía.</div>
        @else
            <table class="movimientos">
                <thead>
                    <tr>
                        <th style="width:90px;">Fecha</th>
                        <th>Concepto</th>
                        <th style="width:90px;" class="derecha">Monto</th>
                        <th style="width:90px;" class="derecha">Saldo restante</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($item['movimientos'] as $m)
                        <tr class="{{ $m['anulado'] ? 'anulado' : '' }}">
                            <td>{{ \Illuminate\Support\Carbon::parse($m['fecha'])->format('d/m/Y') }}</td>
                            <td>{{ $m['concepto'] }}</td>
                            <td class="derecha">{{ $m['anulado'] ? '—' : '$'.number_format($m['monto'], 2) }}</td>
                            <td class="derecha">${{ number_format($m['saldo'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @empty
        <div class="sin-movimientos" style="margin-top:14px;">Este cliente no tiene ventas a crédito registradas.</div>
    @endforelse

    <div class="pie">Documento generado el {{ $fechaEmision->format('d/m/Y H:i') }} — {{ $config->app_name ?? 'SIDB' }}. Los pagos anulados no cuentan en los totales ni en el saldo.</div>

</div>
</body>
</html>
