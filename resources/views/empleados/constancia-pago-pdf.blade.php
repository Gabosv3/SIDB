<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de pago</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size:12px; color:#1f2937; }
        .page { border:1px solid #111827; margin:24px; padding:40px 48px; }

        .header { text-align:center; margin-bottom:18px; }
        .header img { max-height:70px; margin-bottom:6px; }
        .header .empresa { font-size:15px; font-weight:700; letter-spacing:.5px; color:#111827; }
        .header .subtitulo { font-size:9px; letter-spacing:2px; color:#6b7280; margin-top:4px; text-transform:uppercase; }

        .divisor { border-top:1px solid #d1d5db; margin:16px 0 22px; }

        .titulo { text-align:center; font-size:18px; font-weight:700; letter-spacing:1px; color:#111827; margin-bottom:20px; }

        .intro { font-size:11.5px; line-height:1.6; color:#374151; margin-bottom:26px; text-align:justify; }

        .campo { margin-bottom:18px; }
        .campo .label { font-size:9px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:#6b7280; margin-bottom:4px; }
        .campo .valor { border-bottom:1px solid #9ca3af; min-height:18px; padding-bottom:3px; font-size:12.5px; color:#111827; }

        table.fila { width:100%; border-collapse:collapse; }
        table.fila td { vertical-align:top; padding-right:24px; }
        table.fila td:last-child { padding-right:0; }

        .monto-prefix { color:#111827; font-weight:700; margin-right:4px; }

        .legal { font-size:10px; font-style:italic; color:#6b7280; line-height:1.5; margin:26px 0 40px; }

        table.firmas { width:100%; border-collapse:collapse; margin-top:20px; }
        table.firmas td { width:50%; text-align:center; padding-top:36px; }
        table.firmas .linea { border-top:1px solid #111827; padding-top:6px; font-size:9.5px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; color:#374151; }

        .pie { text-align:center; font-size:8.5px; color:#9ca3af; margin-top:40px; }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        @if($config->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($config->logo))
            <img src="{{ storage_path('app/public/'.$config->logo) }}" alt="Logo">
        @endif
        <div class="empresa">{{ strtoupper($config->app_name ?? 'SIDB') }}</div>
        <div class="subtitulo">Documento oficial de constancia de pago</div>
    </div>

    <div class="divisor"></div>

    <div class="titulo">CONSTANCIA DE PAGO</div>

    <div class="intro">
        Por medio de la presente se hace constar que {{ $config->app_name ?? 'la empresa' }} ha realizado el pago
        correspondiente al período indicado a continuación, de conformidad con lo acordado con el colaborador.
    </div>

    <table class="fila">
        <tr>
            <td style="width:60%;">
                <div class="campo">
                    <div class="label">Nombre del empleado</div>
                    <div class="valor">{{ $empleado->name }}</div>
                </div>
            </td>
            <td style="width:40%;">
                <div class="campo">
                    <div class="label">DUI</div>
                    <div class="valor">{{ $empleado->employeeProfile?->dui ?? '—' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="fila">
        <tr>
            <td style="width:60%;">
                <div class="campo">
                    <div class="label">Cargo</div>
                    <div class="valor">{{ $empleado->employeeProfile?->cargo ?? '—' }}</div>
                </div>
            </td>
            <td style="width:40%;">
                <div class="campo">
                    <div class="label">Período correspondiente</div>
                    <div class="valor">{{ $pago->mes_periodo }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="fila">
        <tr>
            <td style="width:40%;">
                <div class="campo">
                    <div class="label">Monto pagado</div>
                    <div class="valor"><span class="monto-prefix">$</span>{{ number_format((float) $pago->monto, 2) }}</div>
                </div>
            </td>
            <td style="width:30%;">
                <div class="campo">
                    <div class="label">Fecha de pago</div>
                    <div class="valor">{{ $pago->fecha_pago->format('d/m/Y') }}</div>
                </div>
            </td>
            <td style="width:30%;">
                <div class="campo">
                    <div class="label">Método de pago</div>
                    <div class="valor">{{ ucfirst($pago->metodo_pago ?? '—') }}</div>
                </div>
            </td>
        </tr>
    </table>

    @if($pago->referencia)
        <div class="campo">
            <div class="label">Referencia</div>
            <div class="valor">{{ $pago->referencia }}</div>
        </div>
    @endif

    @if($pago->observaciones)
        <div class="campo">
            <div class="label">Observaciones</div>
            <div class="valor">{{ $pago->observaciones }}</div>
        </div>
    @endif

    <div class="legal">
        Esta constancia se extiende a solicitud del interesado, para los fines que estime convenientes, en fe de lo cual
        se firma el presente documento en la fecha de su emisión.
    </div>

    <table class="firmas">
        <tr>
            <td>
                <div class="linea">Firma del empleado</div>
            </td>
            <td>
                <div class="linea">Firma autorizada — {{ $config->app_name ?? 'SIDB' }}</div>
            </td>
        </tr>
    </table>

    <div class="pie">Documento generado el {{ now()->format('d/m/Y H:i') }} — {{ $config->app_name ?? 'SIDB' }}</div>

</div>
</body>
</html>
