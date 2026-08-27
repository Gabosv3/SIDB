<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato de trabajo</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size:10.5px; color:#1f2937; line-height:1.6; }
        .page { margin:26px 48px; }

        .header { text-align:center; margin-bottom:6px; padding-bottom:12px; border-bottom:2px solid #111827; }
        .header img { max-height:55px; margin-bottom:6px; }
        .header .empresa { font-size:13px; font-weight:700; color:#111827; letter-spacing:.3px; }
        .header .datos-empresa { font-size:9px; color:#6b7280; margin-top:2px; }

        .meta-contrato { display:block; text-align:right; font-size:9px; color:#6b7280; margin-top:8px; }

        .titulo { text-align:center; font-size:14px; font-weight:700; letter-spacing:.5px; margin:14px 0 16px; text-transform:uppercase; }

        p { text-align:justify; margin-bottom:9px; }

        .clausula-titulo { font-weight:700; margin-top:12px; margin-bottom:3px; font-size:10.8px; }

        .datos-box { border:1px solid #d1d5db; border-radius:4px; padding:10px 14px; margin:10px 0 14px; background:#f9fafb; }
        .datos-box .subtitulo { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#6b7280; margin-bottom:4px; }
        .datos-box table { width:100%; border-collapse:collapse; margin-bottom:8px; }
        .datos-box table:last-child { margin-bottom:0; }
        .datos-box td { padding:1.5px 0; font-size:10px; }
        .datos-box td.label { font-weight:700; width:150px; color:#374151; vertical-align:top; }

        .lista { margin:4px 0 9px 16px; }
        .lista li { margin-bottom:4px; text-align:justify; }

        table.firmas { width:100%; border-collapse:collapse; margin-top:50px; }
        table.firmas td { width:50%; text-align:center; padding-top:34px; }
        table.firmas .linea { border-top:1px solid #111827; padding-top:6px; font-size:9.5px; font-weight:700; text-transform:uppercase; color:#374151; }
        table.firmas .detalle { font-size:9px; font-weight:400; text-transform:none; color:#6b7280; margin-top:2px; }

        .pie { text-align:center; font-size:8px; color:#9ca3af; margin-top:24px; border-top:1px solid #e5e7eb; padding-top:8px; }

        .pagebreak { page-break-before: always; }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        @if($config->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($config->logo))
            <img src="{{ storage_path('app/public/'.$config->logo) }}" alt="Logo">
        @endif
        <div class="empresa">{{ strtoupper($config->app_name ?? 'SIDB') }}</div>
        <div class="datos-empresa">
            @if($config->direccion){{ $config->direccion }}@endif
            @if($config->telefono) &nbsp;·&nbsp; Tel. {{ $config->telefono }} @endif
            @if($config->correo_contacto) &nbsp;·&nbsp; {{ $config->correo_contacto }} @endif
        </div>
    </div>

    <div class="meta-contrato">N.° de contrato: CT-{{ str_pad($empleado->id, 5, '0', STR_PAD_LEFT) }} &nbsp;|&nbsp; Fecha de emisión: {{ now()->format('d/m/Y') }}</div>

    <div class="titulo">Contrato Individual de Trabajo</div>

    <p>
        En la ciudad de {{ $lugar }}, República de El Salvador, a los {{ $fechaLetras }}, comparecen por una parte
        <strong>{{ $config->app_name ?? 'la empresa' }}</strong>@if($config->direccion), con domicilio en {{ $config->direccion }}@endif,
        en adelante denominada <strong>"EL EMPLEADOR"</strong>; y por otra parte
        <strong>{{ $empleado->name }}</strong>@if($perfil->dui), portador(a) de Documento Único de Identidad (DUI) número
        <strong>{{ $perfil->dui }}</strong>@endif@if($perfil->nit), con Número de Identificación Tributaria (NIT) {{ $perfil->nit }}@endif,
        @if($perfil->nacionalidad) de nacionalidad {{ $perfil->nacionalidad }}, @endif
        @if($perfil->fecha_nacimiento) nacido(a) el {{ $perfil->fecha_nacimiento->format('d/m/Y') }}, @endif
        @if($perfil->estado_civil) de estado civil {{ $perfil->estado_civil }}, @endif
        @if($perfil->direccion) con domicilio en {{ $perfil->direccion }}{{ $perfil->municipio ? ', '.$perfil->municipio : '' }}{{ $perfil->departamento ? ', '.$perfil->departamento : '' }}, @endif
        en adelante denominado(a) <strong>"EL TRABAJADOR/A"</strong>; ambas partes mayores de edad y con capacidad legal
        suficiente para obligarse, convienen en celebrar el presente Contrato Individual de Trabajo, el cual se regirá
        por las cláusulas siguientes y, en todo lo no previsto expresamente en ellas, por las disposiciones del Código
        de Trabajo de la República de El Salvador y demás leyes laborales y de seguridad social aplicables:
    </p>

    <div class="datos-box">
        <div class="subtitulo">Resumen de condiciones laborales</div>
        <table>
            <tr><td class="label">Cargo / puesto:</td><td>{{ $perfil->cargo ?: '—' }}</td></tr>
            <tr><td class="label">Fecha de ingreso:</td><td>{{ $perfil->fecha_ingreso?->format('d/m/Y') ?? '—' }}</td></tr>
            <tr><td class="label">Tipo de contrato:</td><td>{{ $tipoContratoLabel }}</td></tr>
            <tr><td class="label">Modalidad de pago:</td><td>{{ $modalidadPagoLabel }}</td></tr>
            <tr><td class="label">Horario laboral:</td><td>{{ $perfil->horario_laboral ?: '—' }}</td></tr>
            <tr><td class="label">Lugar de trabajo:</td><td>{{ $config->direccion ?: '—' }}</td></tr>
        </table>
    </div>

    <div class="clausula-titulo">PRIMERA: Objeto del contrato.</div>
    <p>
        EL TRABAJADOR/A se compromete a prestar a EL EMPLEADOR sus servicios personales, materiales e intelectuales,
        de forma subordinada, desempeñando el cargo de <strong>{{ $perfil->cargo ?: '(cargo a definir)' }}</strong>
        y las funciones inherentes al mismo, así como aquellas afines o complementarias que EL EMPLEADOR le asigne
        razonablemente, comprometiéndose a realizar dicho trabajo con la eficiencia, cuidado, honradez y esmero
        apropiados, sujetándose a las órdenes, instrucciones y reglamentos internos de EL EMPLEADOR o de sus
        representantes.
    </p>

    <div class="clausula-titulo">SEGUNDA: Período de prueba.</div>
    <p>
        Se establece un período de prueba de treinta (30) días calendario contados a partir de la fecha de ingreso,
        durante el cual cualquiera de las partes podrá dar por terminado el presente contrato sin responsabilidad,
        conforme a lo establecido en el Código de Trabajo. Transcurrido dicho período sin que ninguna de las partes
        manifieste su voluntad de terminarlo, el contrato continuará vigente en los términos aquí pactados.
    </p>
    <p>
        La terminación del contrato durante el período de prueba —sin importar el número de días efectivamente
        laborados, incluso si fueran solamente uno o dos días— en ningún caso exime a EL EMPLEADOR de pagar a EL
        TRABAJADOR/A la remuneración correspondiente al tiempo o labor realmente trabajado hasta la fecha de
        terminación, ya sea en forma de salario proporcional a los días laborados, comisión sobre lo efectivamente
        cobrado o vendido durante ese lapso, o ambos, según la modalidad de pago pactada en la cláusula CUARTA. Dicho
        pago deberá liquidarse dentro de los plazos que establece el Código de Trabajo, junto con cualquier otra
        prestación proporcional a la que EL TRABAJADOR/A tenga derecho por el tiempo laborado (incluyendo vacación y
        aguinaldo proporcionales, cuando corresponda).
    </p>

    <div class="clausula-titulo">TERCERA: Jornada y horario de trabajo.</div>
    <p>
        @if($perfil->modalidad_pago === 'comision')
            Dado que la remuneración de EL TRABAJADOR/A se establece por comisión sobre resultados (según la cláusula
            CUARTA), su jornada estará orientada al cumplimiento de la ruta, cartera de clientes o metas de
            cobro/venta que le sean asignadas, dentro de los límites máximos que establece el Código de Trabajo
            (jornada diurna máxima de 8 horas diarias y 44 horas semanales). El monto efectivamente cobrado o vendido
            en un día determinado —sea este mayor o menor al promedio habitual— no modifica el porcentaje de comisión
            pactado ni constituye por sí solo una variación de la jornada convenida. EL TRABAJADOR/A gozará de los
            descansos y del séptimo día remunerado que la ley le reconoce.
        @else
            La jornada de trabajo será la establecida en el horario laboral indicado en el resumen anterior, dentro de
            los límites que establece el Código de Trabajo (jornada diurna máxima de 8 horas diarias y 44 horas
            semanales). EL TRABAJADOR/A gozará de los descansos y del séptimo día remunerado que la ley le reconoce.
        @endif
        El horario podrá ser modificado de común acuerdo entre las partes, o unilateralmente por EL EMPLEADOR cuando
        las necesidades de la operación lo justifiquen, respetando siempre los límites legales.
    </p>

    <div class="clausula-titulo">CUARTA: Remuneración.</div>
    <p>
        @if($perfil->modalidad_pago === 'comision')
            EL TRABAJADOR/A percibirá como única remuneración una comisión equivalente al
            <strong>{{ $perfil->porcentaje_comision !== null ? number_format((float) $perfil->porcentaje_comision, 2).'%' : '____%' }}</strong>
            sobre {{ $comisionBase }}, la cual será calculada, liquidada y pagada conforme a los períodos, reportes y
            procedimientos internos que EL EMPLEADOR tenga establecidos. En ningún caso la remuneración percibida en
            un período completo de trabajo podrá ser inferior al salario mínimo legal vigente para la actividad
            correspondiente; de resultar inferior, EL EMPLEADOR completará la diferencia hasta alcanzar dicho mínimo.
        @elseif($perfil->modalidad_pago === 'mixto')
            EL TRABAJADOR/A percibirá un salario base mensual de
            <strong>${{ $perfil->salario_base !== null ? number_format((float) $perfil->salario_base, 2) : '____' }}</strong>,
            más una comisión equivalente al
            <strong>{{ $perfil->porcentaje_comision !== null ? number_format((float) $perfil->porcentaje_comision, 2).'%' : '____%' }}</strong>
            sobre {{ $comisionBase }}, conforme a los períodos, reportes y procedimientos internos que EL EMPLEADOR
            tenga establecidos.
        @else
            EL TRABAJADOR/A percibirá un salario mensual fijo de
            <strong>${{ $perfil->salario_base !== null ? number_format((float) $perfil->salario_base, 2) : '____' }}</strong>,
            pagadero en los períodos que EL EMPLEADOR tenga establecidos internamente (quincenal o mensual).
        @endif
        De dicha remuneración se efectuarán las deducciones de ley correspondientes, incluyendo las cotizaciones al
        Instituto Salvadoreño del Seguro Social (ISSS), a la Administradora de Fondos de Pensiones (AFP) que
        corresponda, y el Impuesto Sobre la Renta cuando aplique.
    </p>

    <div class="clausula-titulo">QUINTA: Prestaciones de ley.</div>
    <ul class="lista">
        <li><strong>Vacación anual remunerada:</strong> quince (15) días conforme al Código de Trabajo, una vez cumplido el año de servicio continuo.</li>
        <li><strong>Aguinaldo:</strong> conforme a los porcentajes y plazos establecidos en la Ley de Aguinaldos, según los años de servicio de EL TRABAJADOR/A.</li>
        <li><strong>Seguridad social:</strong> afiliación y cotización al ISSS y a la AFP correspondiente, desde la fecha de ingreso.</li>
        <li><strong>Días de asueto:</strong> los establecidos en el Código de Trabajo, remunerados conforme a la ley.</li>
    </ul>

    <div class="clausula-titulo">SEXTA: Lugar de trabajo.</div>
    <p>
        EL TRABAJADOR/A prestará sus servicios en las instalaciones de EL EMPLEADOR
        @if($config->direccion) ubicadas en {{ $config->direccion }}, @endif
        o en cualquier otro lugar dentro del territorio nacional que las funciones del cargo requieran (incluyendo
        rutas, zonas de cobro o venta asignadas), previo aviso razonable de EL EMPLEADOR.
    </p>

    <div class="clausula-titulo">SÉPTIMA: Duración del contrato.</div>
    <p>
        El presente contrato se celebra
        @if(($perfil->tipo_contrato ?? null) === 'indefinido')
            por tiempo indefinido, a partir de la fecha de ingreso indicada, y podrá darse por terminado únicamente
            conforme a las causales establecidas en el Código de Trabajo.
        @elseif(($perfil->tipo_contrato ?? null) === 'temporal')
            por tiempo determinado, conforme a las necesidades de EL EMPLEADOR, y finalizará en la fecha que las
            partes acuerden o al concluir la labor para la cual fue contratado, sin que ello genere responsabilidad
            adicional para EL EMPLEADOR salvo lo que la ley disponga.
        @elseif(($perfil->tipo_contrato ?? null) === 'por_obra')
            para la ejecución de una obra o labor determinada, y finalizará automáticamente al concluir dicha obra o
            labor, sin necesidad de aviso previo adicional al ya convenido entre las partes.
        @elseif(($perfil->tipo_contrato ?? null) === 'practica')
            bajo la modalidad de práctica/pasantía formativa, por el período que las partes acuerden, orientado al
            desarrollo de competencias laborales de EL TRABAJADOR/A.
        @else
            bajo las condiciones que las partes acuerden, a partir de la fecha de ingreso indicada.
        @endif
    </p>

    <div class="clausula-titulo">OCTAVA: Obligaciones de EL TRABAJADOR/A.</div>
    <ul class="lista">
        <li>Cumplir con las órdenes, instrucciones y reglamentos internos que reciba de EL EMPLEADOR relacionados con el desempeño de sus labores.</li>
        <li>Guardar la debida confidencialidad y reserva sobre la información de clientes, precios, procesos, rutas de cobro/venta, bases de datos y operaciones internas de EL EMPLEADOR, tanto durante la vigencia del contrato como después de su terminación.</li>
        <li>Cuidar y responder por los bienes, equipos, dinero, mercadería y valores que se le asignen o confíen para el desempeño de su trabajo, reportando de inmediato cualquier pérdida, daño o anomalía.</li>
        <li>Rendir cuentas oportunas y exactas de los cobros, ventas o gestiones que realice a nombre de EL EMPLEADOR.</li>
        <li>Observar buena conducta, honradez y respeto hacia clientes, compañeros de trabajo y representantes de EL EMPLEADOR.</li>
        <li>Asistir puntualmente a su lugar de trabajo y cumplir con el horario y ruta que le sean asignados.</li>
    </ul>

    <div class="clausula-titulo">NOVENA: Obligaciones de EL EMPLEADOR.</div>
    <ul class="lista">
        <li>Pagar la remuneración pactada en la forma, período y monto convenidos.</li>
        <li>Proporcionar a EL TRABAJADOR/A las herramientas, equipo o información necesaria para el desempeño de sus labores.</li>
        <li>Afiliar y cotizar oportunamente al ISSS y AFP correspondiente.</li>
        <li>Respetar los derechos laborales, prestaciones y garantías que la ley reconoce a EL TRABAJADOR/A.</li>
        <li>Guardar la debida consideración a la dignidad de EL TRABAJADOR/A durante la relación laboral.</li>
    </ul>

    <div class="clausula-titulo">DÉCIMA: Régimen disciplinario.</div>
    <p>
        El incumplimiento de las obligaciones establecidas en el presente contrato, en el reglamento interno de
        trabajo (cuando exista) o en las disposiciones del Código de Trabajo, podrá dar lugar a las sanciones
        disciplinarias que correspondan conforme a la gravedad de la falta, incluyendo amonestación verbal o escrita,
        suspensión, o despido con responsabilidad para EL TRABAJADOR/A cuando la causal lo amerite conforme a la ley.
    </p>

    <div class="clausula-titulo">DÉCIMA PRIMERA: Terminación del contrato.</div>
    <p>
        El presente contrato podrá terminar por cualquiera de las causas establecidas en el Código de Trabajo de la
        República de El Salvador, incluyendo mutuo acuerdo de las partes, renuncia voluntaria de EL TRABAJADOR/A
        (con el aviso previo que la ley establece), o despido con o sin responsabilidad para EL EMPLEADOR, según
        corresponda conforme a la causal invocada.
    </p>

    <div class="clausula-titulo">DÉCIMA SEGUNDA: Legislación aplicable y jurisdicción.</div>
    <p>
        Para todo lo no previsto expresamente en el presente contrato, las partes se someten a lo dispuesto en el
        Código de Trabajo y demás legislación laboral y de seguridad social vigente en la República de El Salvador.
        Cualquier controversia derivada de la interpretación o cumplimiento del presente contrato será dirimida ante
        los tribunales de trabajo competentes.
    </p>

    <p style="margin-top:14px;">
        Ambas partes, leído que fue el presente contrato y enteradas de su contenido, alcance y efectos legales, lo
        aceptan y ratifican en todas sus partes, firmándolo en dos ejemplares de igual valor y contenido, en
        {{ $lugar }}, a los {{ $fechaLetras }}.
    </p>

    <table class="firmas">
        <tr>
            <td>
                <div class="linea">EL TRABAJADOR/A</div>
                <div class="detalle">{{ $empleado->name }}@if($perfil->dui) — DUI {{ $perfil->dui }}@endif</div>
            </td>
            <td>
                <div class="linea">EL EMPLEADOR</div>
                <div class="detalle">{{ $config->app_name ?? 'SIDB' }} — Firma y sello</div>
            </td>
        </tr>
    </table>

    <div class="pie">{{ $config->app_name ?? 'SIDB' }} — Contrato Individual de Trabajo N.° CT-{{ str_pad($empleado->id, 5, '0', STR_PAD_LEFT) }} — Documento generado el {{ now()->format('d/m/Y H:i') }}</div>

</div>
</body>
</html>
