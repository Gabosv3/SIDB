<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha de datos del empleado</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size:11px; color:#1f2937; }
        .page { padding:28px 40px; }

        .header { text-align:center; margin-bottom:14px; }
        .header img { max-height:70px; margin-bottom:6px; }
        .header .empresa { font-size:14px; font-weight:700; letter-spacing:.5px; color:#111827; }
        .header .subtitulo { font-size:8.5px; letter-spacing:1.5px; color:#6b7280; margin-top:3px; text-transform:uppercase; }

        .divisor { border-top:1px solid #d1d5db; margin:12px 0 16px; }

        .titulo { text-align:center; font-size:16px; font-weight:700; letter-spacing:1px; color:#111827; margin-bottom:6px; }
        .instrucciones { text-align:center; font-size:9.5px; color:#6b7280; margin-bottom:18px; }

        .seccion { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#fff; background:#374151; padding:5px 10px; margin:16px 0 10px; }
        .seccion:first-of-type { margin-top:0; }

        table.fila { width:100%; border-collapse:collapse; margin-bottom:14px; }
        table.fila td { vertical-align:top; padding-right:20px; }
        table.fila td:last-child { padding-right:0; }

        .campo .label { font-size:8.5px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; color:#6b7280; margin-bottom:5px; }
        .campo .linea { border-bottom:1px solid #9ca3af; min-height:16px; }

        .checkbox-fila { display:table; width:100%; margin-bottom:14px; }
        .checkbox-item { display:table-cell; padding-right:24px; font-size:10.5px; color:#374151; }
        .checkbox-caja { display:inline-block; width:11px; height:11px; border:1px solid #6b7280; margin-right:5px; vertical-align:middle; }

        .pie { text-align:center; font-size:8px; color:#9ca3af; margin-top:24px; }
        .nota-firma { margin-top:26px; }
        table.firmas { width:100%; border-collapse:collapse; margin-top:30px; }
        table.firmas td { width:50%; text-align:center; padding-top:34px; }
        table.firmas .linea-firma { border-top:1px solid #111827; padding-top:6px; font-size:9px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; color:#374151; }
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
        <span class="subtitulo">Ficha de datos del empleado</span>
    </div>

    <div class="divisor"></div>

    <div class="titulo">FICHA DE DATOS DEL EMPLEADO</div>
    <div class="instrucciones">Complete todos los campos con letra clara. Esta información se usará para crear su expediente en el sistema.</div>

    {{-- ── Identidad ─────────────────────────────────────────────────────── --}}
    <div class="seccion">Datos de identidad</div>
    <table class="fila">
        <tr>
            <td style="width:60%;"><div class="campo"><div class="label">Nombre completo</div><div class="linea">&nbsp;</div></div></td>
            <td style="width:40%;"><div class="campo"><div class="label">DUI</div><div class="linea">&nbsp;</div></div></td>
        </tr>
    </table>
    <table class="fila">
        <tr>
            <td style="width:33.33%;"><div class="campo"><div class="label">NIT</div><div class="linea">&nbsp;</div></div></td>
            <td style="width:33.33%;"><div class="campo"><div class="label">Número de afiliación (ISSS/AFP)</div><div class="linea">&nbsp;</div></div></td>
            <td style="width:33.33%;"><div class="campo"><div class="label">Fecha de nacimiento</div><div class="linea">&nbsp;</div></div></td>
        </tr>
    </table>
    <table class="fila">
        <tr>
            <td style="width:25%;"><div class="campo"><div class="label">Género</div><div class="linea">&nbsp;</div></div></td>
            <td style="width:25%;"><div class="campo"><div class="label">Estado civil</div><div class="linea">&nbsp;</div></div></td>
            <td style="width:25%;"><div class="campo"><div class="label">Tipo de sangre</div><div class="linea">&nbsp;</div></div></td>
            <td style="width:25%;"><div class="campo"><div class="label">Nacionalidad</div><div class="linea">&nbsp;</div></div></td>
        </tr>
    </table>

    {{-- ── Contacto ─────────────────────────────────────────────────────── --}}
    <div class="seccion">Contacto y dirección</div>
    <table class="fila">
        <tr>
            <td style="width:33.33%;"><div class="campo"><div class="label">Teléfono / WhatsApp</div><div class="linea">&nbsp;</div></div></td>
            <td style="width:66.66%;"><div class="campo"><div class="label">Correo electrónico</div><div class="linea">&nbsp;</div></div></td>
        </tr>
    </table>
    <table class="fila">
        <tr>
            <td style="width:33.33%;"><div class="campo"><div class="label">Departamento</div><div class="linea">&nbsp;</div></div></td>
            <td style="width:33.33%;"><div class="campo"><div class="label">Municipio</div><div class="linea">&nbsp;</div></div></td>
            <td style="width:33.33%;"><div class="campo"><div class="label">&nbsp;</div><div class="linea"></div></div></td>
        </tr>
    </table>
    <table class="fila">
        <tr>
            <td><div class="campo"><div class="label">Dirección completa</div><div class="linea">&nbsp;</div></div></td>
        </tr>
    </table>

    {{-- ── Contacto de emergencia ──────────────────────────────────────────── --}}
    <div class="seccion">Contacto de emergencia</div>
    <table class="fila">
        <tr>
            <td style="width:60%;"><div class="campo"><div class="label">Nombre</div><div class="linea">&nbsp;</div></div></td>
            <td style="width:40%;"><div class="campo"><div class="label">Teléfono</div><div class="linea">&nbsp;</div></div></td>
        </tr>
    </table>

    {{-- ── Datos laborales ─────────────────────────────────────────────────── --}}
    <div class="seccion">Datos laborales</div>
    <table class="fila">
        <tr>
            <td style="width:50%;"><div class="campo"><div class="label">Cargo / Puesto</div><div class="linea">&nbsp;</div></div></td>
            <td style="width:50%;"><div class="campo"><div class="label">Fecha de ingreso</div><div class="linea">&nbsp;</div></div></td>
        </tr>
    </table>

    <div class="campo" style="margin-bottom:8px;">
        <div class="label">Tipo de empleado (marque los que correspondan)</div>
    </div>
    <div class="checkbox-fila">
        <div class="checkbox-item"><span class="checkbox-caja">&nbsp;</span>Vendedor</div>
        <div class="checkbox-item"><span class="checkbox-caja">&nbsp;</span>Cobrador</div>
        <div class="checkbox-item"><span class="checkbox-caja">&nbsp;</span>Supervisor</div>
    </div>

    <table class="fila">
        <tr>
            <td style="width:34%;"><div class="campo"><div class="label">Horario laboral</div><div class="linea">&nbsp;</div></div></td>
            <td style="width:33%;"><div class="campo"><div class="label">Hora de entrada</div><div class="linea">&nbsp;</div></div></td>
            <td style="width:33%;"><div class="campo"><div class="label">Hora de salida</div><div class="linea">&nbsp;</div></div></td>
        </tr>
    </table>

    <div class="nota-firma">
        <table class="firmas">
            <tr>
                <td><div class="linea-firma">Firma del empleado</div></td>
                <td><div class="linea-firma">Recibido por — {{ $config->app_name ?? 'SIDB' }}</div></td>
            </tr>
        </table>
    </div>

    <div class="pie">Ficha generada el {{ now()->format('d/m/Y H:i') }} — {{ $config->app_name ?? 'SIDB' }}. Entregar completa para su registro en el sistema.</div>

</div>
</body>
</html>
