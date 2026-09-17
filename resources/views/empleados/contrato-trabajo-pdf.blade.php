<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato Individual de Trabajo</title>
    <style>
        {{-- :not(html):not(body) evita pisar el margen de página que dompdf
             inyecta en <body> a partir de @page -- un simple "* { margin:0 }"
             lo resetea a 0 y el contenido queda pegado al borde. --}}
        *:not(html):not(body) { margin:0; padding:0; box-sizing:border-box; }
        @page { size: letter portrait; margin: 2.5cm; }
        {{-- El original mezcla dos fuentes: la tabla de "Generales" (página 1)
             está en Calibri, y todo el cuerpo del contrato desde "NOSOTROS"
             en adelante (página 2+) está en Times New Roman. Calibri no está
             disponible en dompdf, así que en esa página se usa Helvetica
             como la alternativa más cercana; Times New Roman sí es nativa. --}}
        body { font-family: 'Times New Roman', Times, serif; font-size:11pt; color:#111827; line-height:1.4; }
        .pagina-generales { font-family: Helvetica, Arial, sans-serif; }
        .pagebreak { page-break-before: always; }

        .titulo { text-align:center; font-size:14pt; font-weight:700; letter-spacing:.5px; margin-bottom:16px; text-transform:uppercase; }

        table.generales { width:100%; border-collapse:collapse; margin-bottom:20px; }
        table.generales col { width:50%; }
        table.generales th { font-size:11pt; font-weight:700; text-transform:uppercase; text-align:left; padding:0 10px 6px; }
        table.generales th:first-child { padding-left:0; }
        table.generales td { vertical-align:top; padding:0 10px; }
        table.generales td:first-child { padding-left:0; }
        table.generales td:last-child { padding-right:0; }
        .g-fila { border-bottom:1px solid #9ca3af; padding:3px 0 3px; font-size:11pt; }
        .g-label { font-weight:700; }

        .cuerpo { text-align:justify; }

        table.firmas { width:100%; border-collapse:collapse; margin-top:46px; }
        table.firmas td { width:50%; text-align:center; padding-top:34px; }
        table.firmas .linea { border-top:1px solid #111827; padding-top:6px; font-size:10.5pt; font-weight:700; text-transform:uppercase; color:#111827; }

        .notarial p { text-align:justify; margin-bottom:11px; }
        .blanco { display:inline-block; border-bottom:1px solid #111827; min-width:110px; }
    </style>
</head>
<body>
@php
    $etiquetaSexo = ['masculino' => 'Masculino', 'femenino' => 'Femenino'];
    $etiquetaEstadoCivil = ['soltero' => 'Soltero(a)', 'casado' => 'Casado(a)', 'divorciado' => 'Divorciado(a)', 'viudo' => 'Viudo(a)', 'acompanado' => 'Acompañado(a)'];

    $pctComisionTxt = $perfil->porcentaje_comision !== null ? number_format((float) $perfil->porcentaje_comision, 2).'%' : '____%';
    $salarioBaseTxt = $perfil->salario_base !== null ? number_format((float) $perfil->salario_base, 2) : '____';
    $remuneracionTexto = match ($perfil->modalidad_pago) {
        'comision' => "una comisión equivalente al <strong>{$pctComisionTxt}</strong> sobre {$comisionBase}",
        'mixto' => "<strong>\${$salarioBaseTxt}</strong> mensuales, más una comisión equivalente al <strong>{$pctComisionTxt}</strong> sobre {$comisionBase}",
        default => "<strong>\${$salarioBaseTxt}</strong> mensuales",
    };
    $medioPagoTxt = ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia bancaria', 'cheque' => 'Cheque', 'deposito' => 'Depósito bancario'][$perfil->medio_pago] ?? '____________';
@endphp
<div class="page pagina-generales">

    <div class="titulo">Contrato Individual de Trabajo</div>

    <table class="generales">
        <col><col>
        <tr>
            <th>Generales de Parte Patronal</th>
            <th>Generales del Trabajador</th>
        </tr>
        <tr>
            <td><div class="g-fila"><span class="g-label">Nombre:</span> {{ $config->patrono_nombre ?: '—' }}</div></td>
            <td><div class="g-fila"><span class="g-label">Nombre:</span> {{ $empleado->name }}</div></td>
        </tr>
        <tr>
            <td><div class="g-fila"><span class="g-label">Sexo:</span> {{ $etiquetaSexo[$config->patrono_sexo] ?? '—' }}</div></td>
            <td><div class="g-fila"><span class="g-label">Sexo:</span> {{ $etiquetaSexo[$perfil->genero] ?? '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="g-fila"><span class="g-label">Edad:</span> {{ $config->patrono_edad ?? '—' }}</div></td>
            <td><div class="g-fila"><span class="g-label">Edad:</span> {{ $perfil->edad ?? '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="g-fila"><span class="g-label">Estado Civil:</span> {{ $etiquetaEstadoCivil[$config->patrono_estado_civil] ?? '—' }}</div></td>
            <td><div class="g-fila"><span class="g-label">Estado Civil:</span> {{ $etiquetaEstadoCivil[$perfil->estado_civil] ?? '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="g-fila"><span class="g-label">Profesión u Oficio:</span> {{ $config->patrono_profesion ?: '—' }}</div></td>
            <td><div class="g-fila"><span class="g-label">Profesión u Oficio:</span> {{ $perfil->profesion_oficio ?: '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="g-fila"><span class="g-label">Domicilio:</span> {{ $config->patrono_domicilio ?: '—' }}</div></td>
            <td><div class="g-fila"><span class="g-label">Domicilio:</span> {{ $perfil->direccion ?: '—' }}{{ $perfil->municipio ? ', '.$perfil->municipio : '' }}{{ $perfil->departamento ? ', '.$perfil->departamento : '' }}</div></td>
        </tr>
        <tr>
            <td><div class="g-fila"><span class="g-label">Residencia:</span> {{ $config->patrono_residencia ?: '—' }}</div></td>
            <td><div class="g-fila"><span class="g-label">Residencia:</span> {{ $perfil->residencia ?: '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="g-fila"><span class="g-label">Nacionalidad:</span> {{ $config->patrono_nacionalidad ?: '—' }}</div></td>
            <td><div class="g-fila"><span class="g-label">Nacionalidad:</span> {{ $perfil->nacionalidad ?: '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="g-fila"><span class="g-label">DUI:</span> {{ $config->patrono_dui ?: '—' }}</div></td>
            <td><div class="g-fila"><span class="g-label">DUI:</span> {{ $perfil->dui ?: '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="g-fila"><span class="g-label">Expedido en</span> {{ $config->patrono_dui_lugar_expedicion ?: '—' }}</div></td>
            <td><div class="g-fila"><span class="g-label">Expedido en</span> {{ $perfil->dui_lugar_expedicion ?: '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="g-fila">el {{ $config->patrono_dui_fecha_expedicion?->format('d') ?? '____' }} de {{ $config->patrono_dui_fecha_expedicion?->translatedFormat('F \\d\\e Y') ?? '____' }}</div></td>
            <td><div class="g-fila">el {{ $perfil->dui_fecha_expedicion?->format('d') ?? '____' }} de {{ $perfil->dui_fecha_expedicion?->translatedFormat('F \\d\\e Y') ?? '____' }}</div></td>
        </tr>
        <tr>
            <td><div class="g-fila"><span class="g-label">En Representación de (Razón Social):</span> {{ $config->patrono_razon_social ?: '—' }}</div></td>
            <td><div class="g-fila"><span class="g-label">Otros datos de Identificación:</span> —</div></td>
        </tr>
        <tr>
            <td><div class="g-fila"><span class="g-label">NIT:</span> {{ $config->patrono_nit ?: '—' }}</div></td>
            <td></td>
        </tr>
        <tr>
            <td><div class="g-fila"><span class="g-label">Actividad Económica de la Empresa:</span> {{ $config->patrono_actividad_economica ?: '—' }}</div></td>
            <td></td>
        </tr>
    </table>
</div>

<div class="page pagebreak">
    <div class="cuerpo">@include('empleados._contrato-cuerpo-pdf')</div>

    <table class="firmas">
        <tr>
            <td>
                <div class="linea">El Trabajador</div>
            </td>
            <td>
                <div class="linea">El Patrono</div>
            </td>
        </tr>
    </table>

</div>

{{-- ── CERTIFICACIÓN NOTARIAL: transcribe el documento completo, igual que
     lo entregó el abogado -- el notario reproduce el contrato dentro de su
     propia acta. Espacios en blanco para lo que solo el notario completa
     el día de la firma (lugar/hora del acto, su nombre). ── --}}
<div class="page pagebreak notarial">

    <p>
        En el Distrito de <span class="blanco">&nbsp;</span>, Municipio de <span class="blanco">&nbsp;</span>,
        Departamento de <span class="blanco">&nbsp;</span>, a las <span class="blanco">&nbsp;</span> horas del
        día <span class="blanco">&nbsp;</span> de <span class="blanco">&nbsp;</span> del año
        <span class="blanco">&nbsp;</span>, Ante Mí; <span class="blanco" style="min-width:220px;">&nbsp;</span>,
        Notario(a) de este domicilio, comparecen los Señores:
        <strong>{{ $config->patrono_nombre ?: '______________________' }}</strong>,
        de {{ $config->patrono_edad ?? '____' }} años de edad, {{ $config->patrono_profesion ?: '____________' }},
        del domicilio de {{ $config->patrono_domicilio ?: '____________' }}, persona a quien no conozco
        pero identifico por medio de su Documento Único de Identidad número {{ $config->patrono_dui ?: '____________' }}.
        Y <strong>{{ $empleado->name }}</strong>, de {{ $perfil->edad ?? '____' }} años de edad, con domicilio en el
        Distrito de {{ $perfil->direccion ?: '____________' }}, persona a quien no conozco pero identifico por medio
        de su Documento Único de Identidad número {{ $perfil->dui ?: '____________' }}, y me presentan el documento
        que antecede, elaborado en esta misma fecha y que literalmente dice:
    </p>

    <p>&ldquo;&ldquo;&ldquo;&ldquo;<span class="cuerpo">@include('empleados._contrato-cuerpo-pdf')</span>&rdquo;&rdquo;&rdquo;&rdquo;</p>

    <p>
        YO LA NOTARIO DOY FE, que las firmas que calzan tal documento, las reconocen como suyas las comparecientes
        las cuales son auténticas por haber sido puestas a mi presencia y de su puño y letra. Así se expresaron las
        comparecientes a quienes leí integro el contenido de esta acta notarial que consta de dos hojas útiles,
        leída que se las hube en un solo, acto la ratifican y firmamos.- DOY FE.-
    </p>

    <table class="firmas">
        <tr>
            <td>
                <div class="linea">El Trabajador</div>
            </td>
            <td>
                <div class="linea">El Patrono</div>
            </td>
        </tr>
    </table>

    <table class="firmas">
        <tr>
            <td style="width:100%;">
                <div class="linea">Notario(a)</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
