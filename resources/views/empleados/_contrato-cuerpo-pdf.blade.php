{{--
    Cuerpo del contrato, textual e idéntico al formato del abogado (solo se
    rellenan los datos donde el original trae blancos/######). Se incluye DOS
    veces en contrato-trabajo-pdf.blade.php: una como el documento en sí, y
    otra vez tal cual dentro de la certificación notarial (así es como el
    abogado lo entregó -- el notario transcribe el documento completo dentro
    de su acta). No cambiar el texto aquí, solo los datos que se rellenan.
--}}
NOSOTROS {{ $config->patrono_nombre ?: '______________________' }}, de {{ $config->patrono_edad ?? '____' }} años de edad,
{{ $config->patrono_profesion ?: '____________' }}, del domicilio de {{ $config->patrono_domicilio ?: '____________' }},
con Documento Único de Identidad número {{ $config->patrono_dui ?: '____________' }}, quien de ahora en adelante me denominaré EL PATRONO;
y {{ $empleado->name }}, de {{ $perfil->edad ?? '____' }} años de edad, {{ $perfil->profesion_oficio ?: 'empleado' }}, del domicilio del distrito de
{{ $perfil->direccion ?: '____________' }}, municipio de {{ $perfil->municipio ?: '____________' }}, departamento de {{ $perfil->departamento ?: '____________' }},
con documento único de identidad numero: {{ $perfil->dui ?: '____________' }}, actuando en el carácter que aparece expresado, convenimos en
celebrar el presente Contrato Individual de Trabajo sujeto a las estipulaciones siguientes:
A) CLASE DE TRABAJO O SERVICIO: El trabajador se obliga a prestar sus servicios al patrono como: {{ $perfil->cargo ?: '____________' }}
Desempeñando las funciones de: {{ $perfil->cargo ?: '____________' }}
B) DURACIÓN DEL CONTRATO Y TIEMPO DE SERVICIO: El presente Contrato se celebrará por:
A partir de: {{ $perfil->fecha_ingreso?->format('d/m/Y') ?? '____________' }}. Queda estipulado para los trabajadores de nuevo ingreso que los
primeros treinta días serán de prueba y dentro de ese término cualquiera de las partes podrá dar por terminado el contrato sin
expresión de causa.
C) LUGAR DE PRESTACIÓN DE SERVICIOS Y DE ALOJAMIENTO:
El lugar de prestación de los servicios será: {{ $lugar ?: '____________' }}
D) HORARIO DE TRABAJO: @if($perfil->horario_laboral){{ $perfil->horario_laboral }}@else Del día ________________ al día _________________, de _____________, a _______________
Y de __________________, a ________________. El día _____________________ de ______________ a __________________.
De las ___________________ a las _____________________, para la toma de alimentos. Cumpliendo con la semana Laboral ________________________ horas.@endif {{ '' }}
E) SALARIO: FORMA, PERÍODO Y LUGAR DEL PAGO: El salario que recibirá el trabajador, por sus servicios, será la suma de
{!! $remuneracionTexto !!}. Se pagará en dólares de los Estados Unidos de América de la siguiente forma: ____________; El pago se efectuará por
medio de: {{ $medioPagoTxt }}. En la Dirección: {{ $lugar ?: '____________' }}. Dicho pago se efectuará de la manera siguiente: ____________. La
operación del pago principiará y se continuará sin interrupción, a más tardar a la Terminación de la jornada de trabajo correspondiente
a la respectiva fecha, en caso de reclamo de la persona trabajadora, se estará a lo dispuesto en el artículo seiscientos trece del Código
de Trabajo.
F) HERRAMIENTAS Y MATERIALES: El patrono suministrará al trabajador las herramientas y materiales siguientes: {{ rtrim($perfil->herramientas_material ?: '____________', '.') }}.
Que se entregan en ___________________________ y deben ser devueltos así por el trabajador, cuando sea requerida al efecto por sus
jefes inmediatos, salvo la disminución o deterioro causados por caso fortuito o fuerza mayor, o por la acción del tiempo o por el
consumo y uso normal de los mismos.
G) PERSONAS QUE DEPENDEN ECONÓMICAMENTE DEL TRABAJADOR: {{ rtrim($perfil->personas_dependientes ?: '____________', '.') }}.
H) OTRAS ESTIPULACIONES: {{ rtrim($perfil->otras_estipulaciones ?: '____________', '.') }}.
I) En el presente Contrato Individual de Trabajo se entenderán incluidos, según el caso, los derechos y deberes laborales
establecidos por las Leyes y Reglamentos de Trabajo pertinentes, por el Reglamento Interno de Trabajo y por el o los Contratos
Colectivos de Trabajo que celebre el patrono; los reconocidos en las sentencias que resuelvan conflictos colectivos de trabajo en la
empresa, y los consagrados por la costumbre.
J) Este contrato sustituye cualquier otro Convenio Individual de Trabajo anterior, ya sea escrito o verbal, que haya estado vigente
entre el patrono y el trabajador, pero no altera en manera alguna los derechos y prerrogativas del trabajador que emanen de su
antigüedad en el servicio, ni se entenderá como negativa de mejores condiciones concedidas al trabajador en el Contrato anterior y
que no consten el presente.{{ !empty($config->contrato_clausulas_generales) ? ' '.$config->contrato_clausulas_generales : '' }}
