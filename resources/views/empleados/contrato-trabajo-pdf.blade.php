<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato Individual de Trabajo</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size:10.5px; color:#111827; line-height:1.55; }
        .page { margin:26px 48px; }
        .pagebreak { page-break-before: always; }

        .titulo { text-align:center; font-size:14px; font-weight:700; letter-spacing:.5px; margin-bottom:16px; text-transform:uppercase; }
        .subtitulo-tabla { text-align:center; font-size:10px; font-weight:700; text-transform:uppercase; margin-bottom:6px; }

        table.generales { width:100%; border-collapse:collapse; margin-bottom:20px; }
        table.generales col { width:50%; }
        table.generales td { vertical-align:top; padding:0 10px; }
        table.generales td:first-child { padding-left:0; }
        table.generales td:last-child { padding-right:0; }
        .g-fila { border-bottom:1px solid #9ca3af; padding:3px 0 3px; font-size:10px; }
        .g-label { font-weight:700; }

        p { text-align:justify; margin-bottom:9px; }
        .clausula { font-weight:700; }

        table.firmas { width:100%; border-collapse:collapse; margin-top:46px; }
        table.firmas td { width:50%; text-align:center; padding-top:34px; }
        table.firmas .linea { border-top:1px solid #111827; padding-top:6px; font-size:9.5px; font-weight:700; text-transform:uppercase; color:#111827; }
        table.firmas .detalle { font-size:9px; font-weight:400; text-transform:none; color:#4b5563; margin-top:2px; }

        .blanco { display:inline-block; border-bottom:1px solid #111827; min-width:110px; }
        .notarial p { margin-bottom:11px; }
    </style>
</head>
<body>
<div class="page">

    <div class="titulo">Contrato Individual de Trabajo</div>

    <div class="subtitulo-tabla">Generales de parte patronal / Generales del trabajador</div>
    <table class="generales">
        <col><col>
        @php
            $etiquetaSexo = ['masculino' => 'Masculino', 'femenino' => 'Femenino'];
            $etiquetaEstadoCivil = ['soltero' => 'Soltero(a)', 'casado' => 'Casado(a)', 'divorciado' => 'Divorciado(a)', 'viudo' => 'Viudo(a)', 'acompanado' => 'Acompañado(a)'];
        @endphp
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
            <td><div class="g-fila"><span class="g-label">Expedido en:</span> {{ $config->patrono_dui_lugar_expedicion ?: '—' }}</div></td>
            <td><div class="g-fila"><span class="g-label">Expedido en:</span> {{ $perfil->dui_lugar_expedicion ?: '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="g-fila"><span class="g-label">El:</span> {{ $config->patrono_dui_fecha_expedicion?->format('d/m/Y') ?? '—' }}</div></td>
            <td><div class="g-fila"><span class="g-label">El:</span> {{ $perfil->dui_fecha_expedicion?->format('d/m/Y') ?? '—' }}</div></td>
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

    <p>
        NOSOTROS <strong>{{ $config->patrono_nombre ?: '____________________' }}</strong>,
        de {{ $config->patrono_edad ?? '____' }} años de edad, {{ $config->patrono_profesion ?: '____________' }},
        del domicilio de {{ $config->patrono_domicilio ?: '____________________' }},
        con Documento Único de Identidad número {{ $config->patrono_dui ?: '____________' }},
        @if($config->patrono_razon_social) actuando en nombre y representación de {{ $config->patrono_razon_social }}, @endif
        quien de ahora en adelante me denominaré EL EMPLEADOR; y
        <strong>{{ $empleado->name }}</strong>,
        de {{ $perfil->edad ?? '____' }} años de edad, {{ $perfil->profesion_oficio ?: '____________' }},
        del domicilio del distrito de {{ $perfil->direccion ?: '____________' }}, municipio de {{ $perfil->municipio ?: '____________' }},
        departamento de {{ $perfil->departamento ?: '____________' }}, con documento único de identidad numero:
        {{ $perfil->dui ?: '____________' }}, actuando en el carácter que aparece expresado, convenimos en celebrar
        el presente Contrato Individual de Trabajo sujeto a las estipulaciones siguientes:
    </p>

    <p>
        <span class="clausula">A) CLASE DE TRABAJO O SERVICIO:</span> El trabajador se obliga a prestar sus
        servicios al patrono como: <strong>{{ $perfil->cargo ?: '____________' }}</strong>. Desempeñando las
        funciones propias e inherentes a dicho cargo, así como aquellas afines o complementarias que el patrono
        le asigne razonablemente.
    </p>

    <p>
        <span class="clausula">B) DURACIÓN DEL CONTRATO Y TIEMPO DE SERVICIO:</span> El presente Contrato se
        celebrará por:
        @if(($perfil->tipo_contrato ?? null) === 'indefinido') tiempo indefinido,
        @elseif(($perfil->tipo_contrato ?? null) === 'temporal') tiempo determinado,
        @elseif(($perfil->tipo_contrato ?? null) === 'por_obra') la ejecución de una obra o labor determinada,
        @elseif(($perfil->tipo_contrato ?? null) === 'practica') la modalidad de práctica/pasantía formativa,
        @else '____________',
        @endif
        a partir de: <strong>{{ $perfil->fecha_ingreso?->format('d/m/Y') ?? '____________' }}</strong>. Queda
        estipulado para los trabajadores de nuevo ingreso que los primeros treinta días serán de prueba y dentro
        de ese término cualquiera de las partes podrá dar por terminado el contrato sin expresión de causa.
    </p>

    <p>
        <span class="clausula">C) LUGAR DE PRESTACIÓN DE SERVICIOS Y DE ALOJAMIENTO:</span> El lugar de
        prestación de los servicios será: <strong>{{ $lugar ?: '____________' }}</strong>.
    </p>

    <p>
        <span class="clausula">D) HORARIO DE TRABAJO:</span>
        {{ $perfil->horario_laboral ?: '____________________________________________' }}
    </p>

    @php
        $pctComisionTxt = $perfil->porcentaje_comision !== null ? number_format((float) $perfil->porcentaje_comision, 2).'%' : '____%';
        $salarioBaseTxt = $perfil->salario_base !== null ? number_format((float) $perfil->salario_base, 2) : '____';
        $remuneracionTexto = match ($perfil->modalidad_pago) {
            'comision' => "una comisión equivalente al <strong>{$pctComisionTxt}</strong> sobre {$comisionBase}",
            'mixto' => "<strong>\${$salarioBaseTxt}</strong> mensuales, más una comisión equivalente al <strong>{$pctComisionTxt}</strong> sobre {$comisionBase}",
            default => "<strong>\${$salarioBaseTxt}</strong> mensuales",
        };
        $medioPagoTxt = ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia bancaria', 'cheque' => 'Cheque', 'deposito' => 'Depósito bancario'][$perfil->medio_pago] ?? '____________';
    @endphp
    <p>
        <span class="clausula">E) SALARIO: FORMA, PERÍODO Y LUGAR DEL PAGO:</span> El salario que recibirá el
        trabajador, por sus servicios, será la suma de {!! $remuneracionTexto !!}. Se pagará en dólares de los
        Estados Unidos de América. El pago se efectuará por medio de: <strong>{{ $medioPagoTxt }}</strong>.
        En la Dirección: {{ $lugar ?: '____________' }}. La operación del pago principiará y se continuará sin
        interrupción, a más tardar a la terminación de la jornada de trabajo correspondiente a la respectiva
        fecha; en caso de reclamo de la persona trabajadora, se estará a lo dispuesto en el artículo seiscientos
        trece del Código de Trabajo.
    </p>

    <p>
        <span class="clausula">F) HERRAMIENTAS Y MATERIALES:</span> El patrono suministrará al trabajador las
        herramientas y materiales siguientes: {{ $perfil->herramientas_material ?: 'No aplica.' }}
        Que deben ser devueltos así por el trabajador cuando sea requerido al efecto por sus jefes inmediatos,
        salvo la disminución o deterioro causados por caso fortuito o fuerza mayor, o por la acción del tiempo o
        por el consumo y uso normal de los mismos.
    </p>

    <p>
        <span class="clausula">G) PERSONAS QUE DEPENDEN ECONÓMICAMENTE DEL TRABAJADOR:</span>
        {{ $perfil->personas_dependientes ?: 'Ninguna registrada.' }}
    </p>

    <p>
        <span class="clausula">H) OTRAS ESTIPULACIONES:</span> {{ $perfil->otras_estipulaciones ?: 'Ninguna.' }}
    </p>

    <p>
        <span class="clausula">I)</span> En el presente Contrato Individual de Trabajo se entenderán incluidos,
        según el caso, los derechos y deberes laborales establecidos por las Leyes y Reglamentos de Trabajo
        pertinentes, por el Reglamento Interno de Trabajo y por el o los Contratos Colectivos de Trabajo que
        celebre el patrono; los reconocidos en las sentencias que resuelvan conflictos colectivos de trabajo en
        la empresa, y los consagrados por la costumbre.
    </p>

    <p>
        <span class="clausula">J)</span> Este contrato sustituye cualquier otro Convenio Individual de Trabajo
        anterior, ya sea escrito o verbal, que haya estado vigente entre el patrono y el trabajador, pero no
        altera en manera alguna los derechos y prerrogativas del trabajador que emanen de su antigüedad en el
        servicio, ni se entenderá como negativa de mejores condiciones concedidas al trabajador en el Contrato
        anterior y que no consten en el presente.
    </p>

    @if($config->contrato_clausulas_generales)
        <p>{{ $config->contrato_clausulas_generales }}</p>
    @endif

    <p style="margin-top:12px;">
        En {{ $lugar }}, a los {{ $fechaLetras }}, leído que fue el presente contrato y enteradas ambas partes
        de su contenido, alcance y efectos legales, lo aceptan y ratifican en todas sus partes, firmándolo en dos
        ejemplares de igual valor y contenido.
    </p>

    <table class="firmas">
        <tr>
            <td>
                <div class="linea">El Trabajador</div>
                <div class="detalle">{{ $empleado->name }}@if($perfil->dui) — DUI {{ $perfil->dui }}@endif</div>
            </td>
            <td>
                <div class="linea">El Empleador</div>
                <div class="detalle">{{ $config->patrono_nombre ?: ($config->app_name ?? 'SIDB') }}</div>
            </td>
        </tr>
    </table>

</div>

{{-- ── CERTIFICACIÓN NOTARIAL (espacios en blanco para llenar a mano) ── --}}
<div class="page pagebreak notarial">
    <div class="titulo">Certificación Notarial</div>

    <p>
        En el Distrito de <span class="blanco">&nbsp;</span>, Municipio de <span class="blanco">&nbsp;</span>,
        Departamento de <span class="blanco">&nbsp;</span>, a las <span class="blanco">&nbsp;</span> horas del
        día <span class="blanco">&nbsp;</span> de <span class="blanco">&nbsp;</span> del año
        <span class="blanco">&nbsp;</span>, Ante Mí; <span class="blanco" style="min-width:220px;">&nbsp;</span>,
        Notario(a) de este domicilio, comparecen los señores: <strong>{{ $config->patrono_nombre ?: '____________________' }}</strong>,
        @if($config->patrono_edad) de {{ $config->patrono_edad }} años de edad, @endif
        {{ $config->patrono_profesion ?: '____________' }}, del domicilio de {{ $config->patrono_domicilio ?: '____________' }},
        persona a quien {{ '____________' }} identifico por medio de su Documento Único de Identidad número
        {{ $config->patrono_dui ?: '____________' }}. Y <strong>{{ $empleado->name }}</strong>,
        @if($perfil->edad) de {{ $perfil->edad }} años de edad, @endif
        {{ $perfil->profesion_oficio ?: '____________' }}, con domicilio en {{ $perfil->direccion ?: '____________' }},
        persona a quien {{ '____________' }} identifico por medio de su Documento Único de Identidad número
        {{ $perfil->dui ?: '____________' }}, y me presentan el documento que antecede, elaborado en esta misma
        fecha, el cual leído que les fue íntegramente en un solo acto, manifiestan su conformidad con su contenido
        y lo ratifican y firman.
    </p>

    <p>
        YO EL/LA NOTARIO(A) DOY FE, que las firmas que calzan tal documento las reconocen como suyas los
        comparecientes, las cuales son auténticas por haber sido puestas en mi presencia y de su puño y letra.
        Así se expresaron los comparecientes a quienes leí íntegro el contenido de esta acta notarial, leída que
        se las hube en un solo acto, la ratifican y firman.- DOY FE.-
    </p>

    <table class="firmas">
        <tr>
            <td>
                <div class="linea">El Trabajador</div>
            </td>
            <td>
                <div class="linea">El Empleador</div>
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
