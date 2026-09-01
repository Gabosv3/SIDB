<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Conteo de inventario</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size:11px; color:#1f2937; }
        .page { padding:28px 36px; }

        .header { text-align:center; margin-bottom:14px; }
        .header img { max-height:60px; margin-bottom:6px; }
        .header .empresa { font-size:14px; font-weight:700; letter-spacing:.5px; color:#111827; }
        .header .subtitulo { font-size:8.5px; letter-spacing:1.5px; color:#6b7280; margin-top:3px; text-transform:uppercase; }

        .divisor { border-top:1px solid #d1d5db; margin:12px 0 16px; }

        .titulo { text-align:center; font-size:16px; font-weight:700; letter-spacing:1px; color:#111827; margin-bottom:6px; }
        .instrucciones { text-align:center; font-size:9.5px; color:#6b7280; margin-bottom:6px; }

        table.datos-emision { width:100%; border-collapse:collapse; margin-bottom:16px; }
        table.datos-emision td { padding:2px 0; font-size:10px; }
        table.datos-emision td.label { color:#6b7280; font-weight:600; width:110px; }

        .categoria-header { background:#374151; color:#fff; padding:6px 10px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; margin-top:16px; }
        .categoria-header:first-of-type { margin-top:0; }

        table.conteo { width:100%; border-collapse:collapse; margin-bottom:4px; }
        table.conteo th { background:#f3f4f6; border:1px solid #e5e7eb; padding:5px 8px; font-size:8.5px; font-weight:700; text-transform:uppercase; letter-spacing:.3px; color:#6b7280; text-align:left; }
        table.conteo td { border:1px solid #e5e7eb; padding:7px 8px; font-size:10px; color:#374151; }
        table.conteo td.centro { text-align:center; }
        table.conteo td.blanco { min-width:70px; }

        .pie { text-align:center; font-size:8px; color:#9ca3af; margin-top:24px; }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        @if($config->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($config->logo))
            <img src="{{ storage_path('app/public/'.$config->logo) }}" alt="Logo"><br>
        @else
            <span class="empresa">{{ strtoupper($config->app_name ?? 'SIDB') }}</span><br>
        @endif
        <span class="subtitulo">Conteo físico de inventario</span>
    </div>

    <div class="divisor"></div>

    <div class="titulo">CONTEO FÍSICO DE INVENTARIO</div>
    <div class="instrucciones">Anotá el conteo real de cada producto en la columna "Conteo físico". No modifica el stock del sistema — es solo para comparar.</div>

    <table class="datos-emision">
        <tr>
            <td class="label">Fecha de emisión:</td>
            <td>{{ $fechaEmision->format('d/m/Y H:i') }}</td>
            <td class="label" style="text-align:right;">Total de productos:</td>
            <td style="text-align:right;">{{ $totalProductos }}</td>
        </tr>
        <tr>
            <td class="label">Contado por:</td>
            <td colspan="3">_______________________________________________</td>
        </tr>
    </table>

    @foreach($porCategoria as $categoria => $productos)
        <div class="categoria-header">{{ $categoria }} ({{ $productos->count() }})</div>
        <table class="conteo">
            <thead>
                <tr>
                    <th style="width:15%;">Código</th>
                    <th>Producto</th>
                    <th style="width:14%;" class="centro">Stock sistema</th>
                    <th style="width:16%;" class="centro">Conteo físico</th>
                    <th style="width:14%;" class="centro">Diferencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productos as $p)
                    <tr>
                        <td>{{ $p->codigo }}</td>
                        <td>{{ $p->nombre }}</td>
                        <td class="centro">{{ $p->stock }}</td>
                        <td class="blanco">&nbsp;</td>
                        <td class="blanco">&nbsp;</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="pie">Documento generado el {{ $fechaEmision->format('d/m/Y H:i') }} — {{ $config->app_name ?? 'SIDB' }}. No altera el stock del sistema.</div>

</div>
</body>
</html>
