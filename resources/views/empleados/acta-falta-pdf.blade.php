<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de falta injustificada</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size:12px; color:#1f2937; }
        .page { border:1px solid #111827; margin:24px; padding:40px 48px; }

        .header { text-align:center; margin-bottom:18px; }
        .header img { max-height:70px; margin-bottom:6px; }
        .header .empresa { font-size:15px; font-weight:700; letter-spacing:.5px; color:#111827; }
        .header .subtitulo { font-size:9px; letter-spacing:2px; color:#6b7280; margin-top:4px; text-transform:uppercase; }

        .divisor { border-top:1px solid #d1d5db; margin:16px 0 22px; }

        .titulo { text-align:center; font-size:18px; font-weight:700; letter-spacing:1px; color:#111827; margin-bottom:6px; }
        .subtitulo-acta { text-align:center; font-size:10px; color:#6b7280; margin-bottom:20px; }

        .intro { font-size:11.5px; line-height:1.6; color:#374151; margin-bottom:22px; text-align:justify; }

        .campo { margin-bottom:16px; }
        .campo .label { font-size:9px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:#6b7280; margin-bottom:4px; }
        .campo .valor { border-bottom:1px solid #9ca3af; min-height:18px; padding-bottom:3px; font-size:12.5px; color:#111827; }
        .campo .valor.texto-largo { border:1px solid #d1d5db; border-radius:4px; padding:10px; min-height:70px; text-align:justify; line-height:1.5; }

        table.fila { width:100%; border-collapse:collapse; }
        table.fila td { vertical-align:top; padding-right:24px; }
        table.fila td:last-child { padding-right:0; }

        .legal { font-size:10px; font-style:italic; color:#6b7280; line-height:1.5; margin:22px 0 36px; text-align:justify; }

        table.firmas { width:100%; border-collapse:collapse; margin-top:30px; }
        table.firmas td { width:50%; text-align:center; padding-top:36px; }
        table.firmas .linea { border-top:1px solid #111827; padding-top:6px; font-size:9.5px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; color:#374151; }

        .pie { text-align:center; font-size:8.5px; color:#9ca3af; margin-top:36px; }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        @if($config->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($config->logo))
            <img src="{{ storage_path('app/public/'.$config->logo) }}" alt="Logo">
        @endif
        <div class="empresa">{{ strtoupper($config->app_name ?? 'SIDB') }}</div>
        <div class="subtitulo">Documento oficial de recursos humanos</div>
    </div>

    <div class="divisor"></div>

    <div class="titulo">ACTA DE FALTA INJUSTIFICADA</div>
    <div class="subtitulo-acta">Levantada de conformidad con el Código de Trabajo de la República de El Salvador</div>

    <div class="intro">
        En {{ $lugar }}, siendo las {{ now()->format('h:i A') }} del día {{ $fechaLetras }}, se hace constar que el/la
        colaborador(a) que se identifica a continuación incurrió en una falta injustificada de asistencia, conforme a
        los hechos que se describen en la presente acta.
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
                    <div class="label">Fecha de la falta</div>
                    <div class="valor">{{ $acta->fecha_hecho->format('d/m/Y') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="campo">
        <div class="label">Hechos / Descripción de la falta</div>
        <div class="valor texto-largo">{{ $acta->descripcion }}</div>
    </div>

    @if($acta->observaciones)
        <div class="campo">
            <div class="label">Observaciones adicionales</div>
            <div class="valor texto-largo">{{ $acta->observaciones }}</div>
        </div>
    @endif

    <div class="legal">
        La presente acta se levanta como constancia de la falta injustificada de asistencia incurrida por el/la
        trabajador(a), quedando registrada como antecedente en su expediente laboral para los efectos que en derecho
        correspondan, incluyendo lo dispuesto en el Código de Trabajo respecto a las causales de terminación del
        contrato de trabajo sin responsabilidad para el patrono por faltas injustificadas de asistencia reiteradas.
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


</div>
</body>
</html>
