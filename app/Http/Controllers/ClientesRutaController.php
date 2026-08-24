<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cobrador;
use App\Models\ConfiguracionSistema;
use App\Models\DetalleVenta;
use App\Models\GestionCobro;
use App\Models\PagoVenta;
use App\Models\Producto;
use App\Models\Reintegro;
use App\Models\RutaCobro;
use App\Models\User;
use App\Models\Vendedor;
use App\Models\Venta;
use App\Models\VisitaCobro;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Activitylog\Models\Activity;

class ClientesRutaController extends Controller
{
    private const CAMPOS_IMPORTACION = [
        'codigo_anterior' => 'Código (anterior)',
        'nombre' => 'Nombre completo',
        'telefono' => 'Teléfono',
        'direccion' => 'Dirección',
        'producto' => 'Producto',
        'valor_total' => 'Valor total de la venta',
        'monto_cobrado' => 'Monto ya cobrado (abono inicial)',
        'saldo_pendiente' => 'Saldo pendiente',
        'fecha_venta' => 'Fecha de venta',
        'vendedor' => 'Vendedor',
    ];

    public function index(Request $request, $tenant)
    {
        $rutaId = $request->get('ruta_cobro_id') ?: null;

        $rutas = RutaCobro::withCount('clientes')->with('cobrador:id,nombre,apellido')->orderBy('nombre')->get();

        if (! $rutaId && $rutas->isNotEmpty()) {
            $rutaId = $rutas->first()->id;
        }

        // "Sin ruta asignada" ahora es solo lo que de verdad necesita atención
        // (nunca se le asignó ruta, o quedó suelto por algún otro motivo) — un
        // cliente que ya pagó todo y por eso salió de su ruta no es un pendiente,
        // así que se cuenta aparte en "Cuentas cerradas" para no mezclarlos.
        $sinRuta = Cliente::whereNull('ruta_cobro_id')->where('activo', true)->where('saldo', '>', 0)->count();
        $cuentasCerradas = Cliente::whereNull('ruta_cobro_id')->where('activo', true)->where('saldo', '<=', 0)->count();
        $cobradores = Cobrador::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'apellido']);
        $vendedores = Vendedor::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'apellido']);
        $camposImportacion = self::CAMPOS_IMPORTACION;
        $esSuperAdmin = auth()->user()?->hasRole('super_admin') ?? false;

        // Precomputado en el controlador (no inline en el blade con @json) — una
        // expresión larga dentro de @json() se trunca por una limitación del
        // parser de directivas de Blade con arrays literales de varias llaves.
        $rutasParaJs = $rutas->map(fn (RutaCobro $r) => [
            'id' => $r->id,
            'nombre' => $r->nombre,
            'dia_semana' => $r->dia_semana,
            'cobrador_id' => $r->cobrador_id,
            'cobrador_nombre' => $r->cobrador ? trim($r->cobrador->nombre.' '.$r->cobrador->apellido) : null,
        ])->values();

        return view('pos.clientes-ruta', compact('tenant', 'rutas', 'rutaId', 'sinRuta', 'cuentasCerradas', 'cobradores', 'vendedores', 'camposImportacion', 'esSuperAdmin', 'rutasParaJs'));
    }

    /**
     * Página completa del cliente (reemplaza el antiguo modal "Ver detalle").
     * Los datos (ventas, pagos, historial de movimiento) se cargan por AJAX
     * contra el mismo endpoint /detalle que ya existía.
     */
    public function perfilCliente(Request $request, $tenant, Cliente $cliente)
    {
        $esSuperAdmin = auth()->user()?->hasRole('super_admin') ?? false;
        $vendedores = Vendedor::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'apellido']);

        return view('pos.cliente-perfil', compact('tenant', 'cliente', 'esSuperAdmin', 'vendedores'));
    }

    /**
     * Historial general de movimientos de ruta — todos los clientes, no solo
     * uno. Página contenedora; los datos se cargan vía historialData().
     */
    public function historialGeneral(Request $request, $tenant)
    {
        $rutas = RutaCobro::orderBy('nombre')->get(['id', 'nombre']);

        return view('pos.historial-movimientos', compact('tenant', 'rutas'));
    }

    public function historialData(Request $request, $tenant): JsonResponse
    {
        $buscar = trim((string) $request->get('buscar', ''));
        $porPagina = max(10, min(200, $request->integer('por_pagina', 50)));
        $pagina = max(1, $request->integer('page', 1));
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $rutaAnteriorId = $request->get('ruta_anterior_id');
        $rutaNuevaId = $request->get('ruta_nueva_id');
        $ordenCol = $request->get('orden_col');
        $ordenDir = $request->get('orden_dir') === 'desc' ? 'desc' : 'asc';

        $query = Activity::where('log_name', 'cliente_ruta_cambio')
            ->where('subject_type', Cliente::class)
            ->with('causer:id,name');

        if ($buscar !== '') {
            $clienteIds = Cliente::where('nombre', 'like', "%{$buscar}%")
                ->orWhere('apellido', 'like', "%{$buscar}%")
                ->orWhere('codigo_anterior', 'like', "%{$buscar}%")
                ->pluck('id');
            $query->whereIn('subject_id', $clienteIds);
        }

        if ($fechaDesde) {
            $query->whereDate('created_at', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->whereDate('created_at', '<=', $fechaHasta);
        }
        if ($rutaAnteriorId !== null && $rutaAnteriorId !== '') {
            $query->where('properties->ruta_anterior_id', (int) $rutaAnteriorId);
        }
        if ($rutaNuevaId !== null && $rutaNuevaId !== '') {
            $query->where('properties->ruta_nueva_id', (int) $rutaNuevaId);
        }

        // Orden por columna — cliente/usuario se resuelven con subconsulta (igual
        // que se hizo para el sort de "cobrador" en RutaCobroResource, ya que no
        // son columnas propias de activity_log); ruta_anterior/ruta_nueva viven
        // dentro de la columna JSON `properties`.
        if ($ordenCol === 'cliente') {
            $query->orderBy(Cliente::select('nombre')->whereColumn('id', 'activity_log.subject_id'), $ordenDir);
        } elseif ($ordenCol === 'usuario') {
            $query->orderBy(User::select('name')->whereColumn('id', 'activity_log.causer_id'), $ordenDir);
        } elseif ($ordenCol === 'ruta_anterior') {
            $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.ruta_anterior_nombre')) {$ordenDir}");
        } elseif ($ordenCol === 'ruta_nueva') {
            $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.ruta_nueva_nombre')) {$ordenDir}");
        } elseif ($ordenCol === 'fecha' && $ordenDir === 'asc') {
            $query->oldest();
        } else {
            $query->latest();
        }

        $paginator = $query->paginate($porPagina, ['*'], 'page', $pagina);

        // Los nombres se resuelven aparte por id — si el cliente ya fue eliminado
        // (registro no encontrado), el historial se conserva igual, solo sin link.
        $clientesIds = $paginator->getCollection()->pluck('subject_id')->filter()->unique();
        $clientesPorId = Cliente::whereIn('id', $clientesIds)->get(['id', 'nombre', 'apellido', 'codigo_anterior'])->keyBy('id');

        $items = $paginator->getCollection()->map(function (Activity $a) use ($clientesPorId) {
            $cliente = $clientesPorId->get($a->subject_id);

            return [
                'fecha' => $a->created_at->format('d/m/Y H:i'),
                'usuario' => $a->causer?->name ?? 'Sistema',
                'cliente_id' => $a->subject_id,
                'cliente_nombre' => $cliente?->nombre_completo ?? 'Cliente eliminado',
                'ruta_anterior' => $a->properties->get('ruta_anterior_nombre'),
                'cobrador_anterior' => $a->properties->get('cobrador_anterior'),
                'ruta_nueva' => $a->properties->get('ruta_nueva_nombre'),
                'cobrador_nuevo' => $a->properties->get('cobrador_nuevo'),
            ];
        })->values();

        return response()->json([
            'items' => $items,
            'pagina_actual' => $paginator->currentPage(),
            'total_paginas' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'por_pagina' => $porPagina,
            'orden_col' => $ordenCol,
            'orden_dir' => $ordenDir,
        ]);
    }

    public function data(Request $request, $tenant): JsonResponse
    {
        $rutaId = $request->get('ruta_cobro_id');
        $buscar = trim((string) $request->get('buscar', ''));
        // Paginación aplica a cualquier filtro (ruta puntual, "sin_ruta" o "todos") —
        // así ninguna pantalla trae de golpe una lista enorme. La mayoría de rutas
        // caben en una sola página (< 100 clientes), así que el arrastre para
        // reordenar sigue funcionando igual que antes en el caso normal; solo las
        // rutas realmente grandes quedan repartidas en varias páginas.
        $esTodos = $rutaId === 'todos';
        $porPagina = max(10, min(500, $request->integer('por_pagina', 100)));
        $pagina = max(1, $request->integer('page', 1));
        $ordenCol = $request->get('orden_col');
        $ordenDir = $request->get('orden_dir') === 'desc' ? 'desc' : 'asc';
        $saldoDesde = $request->get('saldo_desde');
        $saldoHasta = $request->get('saldo_hasta');

        $query = Cliente::where('activo', true);

        if ($rutaId === 'sin_ruta') {
            $query->whereNull('ruta_cobro_id')->where('saldo', '>', 0);
        } elseif ($rutaId === 'cuentas_cerradas') {
            $query->whereNull('ruta_cobro_id')->where('saldo', '<=', 0);
        } elseif (! $esTodos) {
            $query->where('ruta_cobro_id', $rutaId);
        }

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo_anterior', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%")
                    ->orWhere('apellido', 'like', "%{$buscar}%");
            });
        }

        if ($saldoDesde !== null && $saldoDesde !== '') {
            $query->where('saldo', '>=', (float) $saldoDesde);
        }

        if ($saldoHasta !== null && $saldoHasta !== '') {
            $query->where('saldo', '<=', (float) $saldoHasta);
        }

        // Totales del set completo que calza con el filtro — no solo lo que se
        // ve en la página actual — para que las tarjetas de arriba no mientan.
        $totalClientes = (clone $query)->count();
        $totalSaldo = round((float) (clone $query)->sum('saldo'), 2);
        $totalPagado = round((float) Venta::where('tipo_pago', 'credito')
            ->whereIn('cliente_id', (clone $query)->select('clientes.id'))
            ->sum('monto_pagado'), 2);
        $totalRevisados = (clone $query)->whereNotNull('revisado_en')->count();
        $totalSinGps = (clone $query)->where(fn ($q) => $q->whereNull('latitud')->orWhereNull('longitud'))->count();

        $listado = $query
            ->with(['rutaCobro:id,nombre,cobrador_id', 'ventas' => fn ($q) => $q->where('tipo_pago', 'credito')
                // Solo las cuentas que todavía deben — una venta ya pagada al 100%
                // (completada) no debe seguir apareciendo en la lista de la ruta,
                // aunque el cliente siga ahí por tener otra cuenta con saldo.
                ->where('saldo_pendiente', '>', 0)
                ->oldest('fecha_venta')
                // Antes traía TODOS los pagos de cada venta (a veces 20+ cuotas) solo
                // para quedarse con el primero — con una subconsulta se trae ya
                // calculado, sin cargar el historial completo de pagos por venta.
                ->addSelect([
                    'abono_inicial' => PagoVenta::selectRaw('monto')
                        ->whereColumn('venta_id', 'ventas.id')
                        ->whereNull('anulado_en')
                        ->oldest('fecha_pago')
                        ->limit(1),
                ])])
            ->withCount(['ventas as ventas_count' => fn ($q) => $q->where('saldo_pendiente', '>', 0)])
            // "Última visita" = lo más reciente entre el último pago y la última
            // visita sin cobro de ese cliente — se calculan aparte (no un GREATEST
            // en SQL) porque si a un cliente le falta uno de los dos, GREATEST con
            // NULL da NULL en MySQL y se perdería el otro dato.
            ->addSelect([
                // Total pagado histórico del cliente — independiente del filtro de
                // saldo_pendiente > 0 en la relación 'ventas' de arriba, para que
                // no se pierda lo pagado en cuentas que ya se terminaron de pagar.
                'total_pagado_historico' => PagoVenta::selectRaw('COALESCE(SUM(monto), 0)')
                    ->whereColumn('cliente_id', 'clientes.id')
                    ->whereNull('anulado_en'),
                'ultimo_pago_fecha' => PagoVenta::selectRaw('MAX(fecha_pago)')
                    ->whereColumn('cliente_id', 'clientes.id')
                    ->whereNull('anulado_en'),
                'ultima_visita_fecha' => VisitaCobro::selectRaw('MAX(created_at)')
                    ->whereColumn('cliente_id', 'clientes.id'),
                // Resultado de la visita más reciente (no_encontrado, rechazo, etc.) —
                // corresponde a la misma fila que ultima_visita_fecha (mismo MAX(created_at)).
                'ultima_visita_resultado' => VisitaCobro::select('resultado')
                    ->whereColumn('cliente_id', 'clientes.id')
                    ->orderByDesc('created_at')
                    ->limit(1),
            ]);

        // Orden manual (arrastre) por defecto; si se pide una columna específica,
        // esa manda — pero entonces el arrastre para reordenar se desactiva en el
        // frontend (no tiene sentido combinar ambos a la vez).
        $columnasOrdenables = [
            'nombre' => 'nombre',
            'saldo' => 'saldo',
            'telefono' => 'telefono_normal',
            'direccion' => 'direccion',
        ];

        if ($ordenCol && isset($columnasOrdenables[$ordenCol])) {
            $listado->orderBy($columnasOrdenables[$ordenCol], $ordenDir);
        } elseif ($ordenCol === 'ventas_pendientes') {
            $listado->orderBy('ventas_count', $ordenDir);
        } elseif ($ordenCol === 'ruta_nombre') {
            $listado->orderBy(RutaCobro::select('nombre')->whereColumn('id', 'clientes.ruta_cobro_id'), $ordenDir);
        } elseif ($ordenCol === 'ultima_visita') {
            $listado->orderByRaw(
                "GREATEST(COALESCE(ultimo_pago_fecha, '1900-01-01'), COALESCE(ultima_visita_fecha, '1900-01-01')) {$ordenDir}"
            );
        } else {
            $listado->orderByRaw('orden IS NULL, orden ASC')->orderBy('nombre');
        }

        $paginator = $listado->paginate($porPagina, ['*'], 'page', $pagina);
        $coleccion = $paginator->getCollection();

        $clientes = $coleccion
            ->map(function (Cliente $c) {
                $ventasCredito = $c->ventas->map(fn ($v) => [
                    'venta_id' => $v->id,
                    'total' => (float) $v->total,
                    'saldo_pendiente' => (float) $v->saldo_pendiente,
                    'monto_pagado' => (float) $v->monto_pagado,
                    'abono_inicial' => $v->abono_inicial !== null ? (float) $v->abono_inicial : null,
                ])->values();

                $fechaPago = $c->ultimo_pago_fecha ? \Carbon\Carbon::parse($c->ultimo_pago_fecha) : null;
                $fechaVisita = $c->ultima_visita_fecha ? \Carbon\Carbon::parse($c->ultima_visita_fecha) : null;

                // Cuál de los dos eventos fue el más reciente determina qué se muestra
                // en la columna "Última visita": si ganó una visita sin cobro, se
                // muestra su resultado (no encontrado, rechazo, etc.); si ganó un pago,
                // se muestra como "Pagó".
                $ultimaVisitaTipo = null;
                if ($fechaVisita && (! $fechaPago || $fechaVisita->gt($fechaPago))) {
                    $ultimaVisitaTipo = 'visita';
                } elseif ($fechaPago) {
                    $ultimaVisitaTipo = 'pago';
                }

                $ultimaVisita = collect([$fechaPago, $fechaVisita])->filter()->sort()->last();

                return [
                    'id' => $c->id,
                    'orden' => $c->orden,
                    'codigo_anterior' => $c->codigo_anterior,
                    'nombre' => $c->nombre_completo,
                    'telefono' => $c->telefono_normal,
                    'direccion' => trim(collect([$c->direccion, $c->municipio, $c->departamento])->filter()->implode(', ')),
                    'direccion_raw' => $c->direccion,
                    'tiene_ubicacion' => (bool) ($c->latitud && $c->longitud),
                    'latitud' => $c->latitud,
                    'longitud' => $c->longitud,
                    'saldo' => (float) $c->saldo,
                    'ventas_pendientes' => (int) $c->ventas_count,
                    'ultima_visita' => $ultimaVisita?->format('d/m/Y'),
                    'ultima_visita_tipo' => $ultimaVisitaTipo,
                    'ultima_visita_resultado' => $ultimaVisitaTipo === 'visita' ? $c->ultima_visita_resultado : null,
                    'ruta_cobro_id' => $c->ruta_cobro_id,
                    'ruta_nombre' => $c->rutaCobro?->nombre,
                    'cobrador_id_ruta' => $c->rutaCobro?->cobrador_id,
                    'ventas_credito' => $ventasCredito,
                    'total_pagado_cliente' => round((float) $c->total_pagado_historico, 2),
                    'revisado' => $c->revisado_en !== null,
                ];
            })
            ->values();

        return response()->json([
            'clientes' => $clientes,
            'total_saldo' => $totalSaldo,
            'total_pagado' => $totalPagado,
            'total_clientes' => $totalClientes,
            'total_revisados' => $totalRevisados,
            'total_sin_gps' => $totalSinGps,
            'paginado' => $paginator->lastPage() > 1,
            'pagina_actual' => $paginator->currentPage(),
            'total_paginas' => $paginator->lastPage(),
            'por_pagina' => $porPagina,
            'orden_col' => $ordenCol,
            'orden_dir' => $ordenDir,
        ]);
    }

    /**
     * Clientes (solo código y nombre) que calzan con el filtro actual de
     * /clientes-ruta — mismo criterio que data(), pero sin paginar: la
     * exportación siempre trae la lista completa, no solo la página visible.
     */
    private function clientesParaExportar(Request $request)
    {
        $rutaId = $request->get('ruta_cobro_id');
        $buscar = trim((string) $request->get('buscar', ''));
        $esTodos = $rutaId === 'todos';
        $saldoDesde = $request->get('saldo_desde');
        $saldoHasta = $request->get('saldo_hasta');

        $query = Cliente::where('activo', true);

        if ($rutaId === 'sin_ruta') {
            $query->whereNull('ruta_cobro_id')->where('saldo', '>', 0);
        } elseif ($rutaId === 'cuentas_cerradas') {
            $query->whereNull('ruta_cobro_id')->where('saldo', '<=', 0);
        } elseif (! $esTodos) {
            $query->where('ruta_cobro_id', $rutaId);
        }

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo_anterior', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%")
                    ->orWhere('apellido', 'like', "%{$buscar}%");
            });
        }

        if ($saldoDesde !== null && $saldoDesde !== '') {
            $query->where('saldo', '>=', (float) $saldoDesde);
        }

        if ($saldoHasta !== null && $saldoHasta !== '') {
            $query->where('saldo', '<=', (float) $saldoHasta);
        }

        return $query->select('id', 'codigo_anterior', 'nombre', 'apellido', 'orden')
            ->orderByRaw('orden IS NULL, orden ASC')
            ->orderBy('nombre')
            ->get();
    }

    /** Exporta código + nombre de los clientes filtrados a un .xlsx. */
    public function exportarExcel(Request $request, $tenant)
    {
        $clientes = $this->clientesParaExportar($request);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Código');
        $sheet->setCellValue('B1', 'Nombre');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);

        $fila = 2;
        foreach ($clientes as $c) {
            $sheet->setCellValueExplicit('A'.$fila, $c->codigo_anterior ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('B'.$fila, $c->nombre_completo);
            $fila++;
        }
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(38);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $nombreArchivo = 'clientes-'.now()->format('Y-m-d_His').'.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Exporta código + nombre de los clientes filtrados a un .doc (HTML con
     * el encabezado que Word reconoce — evita agregar phpoffice/phpword como
     * dependencia solo para una tabla de 2 columnas).
     */
    public function exportarWord(Request $request, $tenant)
    {
        $clientes = $this->clientesParaExportar($request);

        $filas = $clientes->map(fn (Cliente $c) => '<tr><td>'.e($c->codigo_anterior ?? '—').'</td><td>'.e($c->nombre_completo).'</td></tr>')->implode('');

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">'
            .'<head><meta charset="UTF-8"><title>Listado de clientes</title>'
            .'<style>body{font-family:Arial,sans-serif;} table{border-collapse:collapse;width:100%;font-size:11pt;} th,td{border:1px solid #333;padding:6px 10px;text-align:left;} th{background:#f0f0f0;}</style>'
            .'</head><body>'
            .'<h2>Listado de clientes</h2>'
            .'<table><tr><th>Código</th><th>Nombre</th></tr>'.$filas.'</table>'
            .'</body></html>';

        $nombreArchivo = 'clientes-'.now()->format('Y-m-d_His').'.doc';

        return response($html, 200, [
            'Content-Type' => 'application/msword',
            'Content-Disposition' => 'attachment; filename="'.$nombreArchivo.'"',
        ]);
    }

    /**
     * Marca (o desmarca) un cliente como "revisado" en el checklist de
     * /clientes-ruta. Antes vivía solo en localStorage del navegador; ahora
     * es compartido entre cualquiera que entre a esta pantalla.
     */
    public function marcarRevisado(Request $request, $tenant, Cliente $cliente): JsonResponse
    {
        $data = $request->validate([
            'revisado' => 'required|boolean',
        ]);

        $cliente->update(['revisado_en' => $data['revisado'] ? now() : null]);

        return response()->json(['mensaje' => 'Revisión actualizada.']);
    }

    /**
     * Limpia el checklist de "revisado" para todos los clientes que calzan
     * con el filtro actual (una ruta puntual, "sin_ruta" o "todos").
     */
    public function limpiarRevision(Request $request, $tenant): JsonResponse
    {
        $rutaId = $request->get('ruta_cobro_id');

        $query = Cliente::where('activo', true);

        if ($rutaId === 'sin_ruta') {
            $query->whereNull('ruta_cobro_id')->where('saldo', '>', 0);
        } elseif ($rutaId === 'cuentas_cerradas') {
            $query->whereNull('ruta_cobro_id')->where('saldo', '<=', 0);
        } elseif ($rutaId !== 'todos') {
            $query->where('ruta_cobro_id', $rutaId);
        }

        $cantidad = $query->whereNotNull('revisado_en')->update(['revisado_en' => null]);

        return response()->json(['mensaje' => 'Revisión reiniciada.', 'cantidad' => $cantidad]);
    }

    public function detalleCliente(Request $request, $tenant, Cliente $cliente): JsonResponse
    {
        $cliente->load([
            'rutaCobro:id,nombre,dia_semana',
            'ventas' => fn ($q) => $q->orderBy('fecha_venta')
                ->with([
                    // Más reciente primero — el detalle de la venta muestra solo los
                    // últimos movimientos con un "ver más" para el resto.
                    'pagos' => fn ($p) => $p->orderByDesc('fecha_pago')->orderByDesc('id'),
                    'pagos.anuladoPor:id,name',
                    'gestionesCobro' => fn ($g) => $g->orderBy('numero_cuota'),
                    'detalles.producto:id,nombre',
                    'vendedor:id,nombre,apellido',
                ]),
        ]);

        // Las visitas sin cobro se mezclan con los pagos de la venta a la que
        // pertenecen (misma cuota) para verse como un solo historial cronológico
        // por venta, no dos listas separadas. Si la visita no quedó ligada a una
        // cuota (dato viejo o registrada sin gestión puntual), se le asigna a la
        // única venta del cliente, o si tiene varias, a la más reciente — mejor
        // aproximación posible sin ese dato.
        $visitasCliente = VisitaCobro::where('cliente_id', $cliente->id)
            ->with(['user:id,name', 'gestionCobro:id,venta_id'])
            ->latest('created_at')
            ->limit(60)
            ->get();

        $ventaFallbackId = $cliente->ventas->count() === 1
            ? $cliente->ventas->first()->id
            : $cliente->ventas->sortByDesc('fecha_venta')->first()?->id;

        $visitasPorVenta = $visitasCliente->groupBy(
            fn (VisitaCobro $v) => $v->gestionCobro?->venta_id ?? $ventaFallbackId
        );

        $ventas = $cliente->ventas->map(function (Venta $v) use ($visitasPorVenta) {
            $cuotas = $v->gestionesCobro;
            $hoy = now()->startOfDay();

            return [
                'id' => $v->id,
                'numero_venta' => $v->numero_venta,
                'fecha_venta' => $v->fecha_venta?->format('d/m/Y'),
                'fecha_venta_iso' => $v->fecha_venta?->toDateString(),
                'tipo_pago' => $v->tipo_pago,
                'estado' => $v->estado,
                'total' => (float) $v->total,
                'monto_pagado' => (float) $v->monto_pagado,
                'saldo_pendiente' => (float) $v->saldo_pendiente,
                'dias_credito' => $v->dias_credito,
                'vendedor_id' => $v->vendedor_id,
                'vendedor_nombre' => $v->vendedor ? trim($v->vendedor->nombre.' '.$v->vendedor->apellido) : null,
                'productos' => $v->detalles->map(fn ($d) => [
                    'nombre' => $d->producto?->nombre ?? 'Producto eliminado',
                    'cantidad' => (int) $d->cantidad,
                    'precio_unitario' => (float) $d->precio_unitario,
                    'subtotal' => (float) $d->subtotal,
                ])->values(),
                'observaciones' => $v->observaciones,
                // Varios pagos de una misma visita quedan repartidos entre cuotas (uno por
                // cuota que se llenó ese día), así que se agrupan por numero_recibo para que
                // se vean como un solo movimiento — mismo criterio que /cobros/historial en
                // la API móvil. Los pagos antiguos, sin recibo asignado, quedan cada uno como
                // su propio grupo (no se mezclan entre sí ni con los que sí tienen recibo).
                // Luego se mezclan (revueltos) con las visitas sin cobro de esta venta, en
                // un solo historial ordenado del evento más reciente al más antiguo.
                'eventos' => $v->pagos
                    ->groupBy(fn ($p) => $p->numero_recibo ?? 'sin_recibo_' . $p->id)
                    ->map(function ($grupo) {
                        $primero = $grupo->first();

                        return [
                            'tipo' => 'pago',
                            'sort_key' => $primero->created_at?->timestamp ?? 0,
                            'numero_recibo' => $primero->numero_recibo,
                            'fecha' => $primero->fecha_pago?->format('d/m/Y'),
                            'fecha_iso' => $primero->fecha_pago?->toDateString(),
                            'monto' => round((float) $grupo->sum('monto'), 2),
                            'metodo_pago' => $grupo->pluck('metodo_pago')->unique()->count() === 1
                                ? $primero->metodo_pago
                                : 'mixto',
                            'observaciones' => $primero->observaciones,
                            'cantidad' => $grupo->count(),
                            'anulado' => $primero->anulado_en !== null,
                            'anulado_en' => $primero->anulado_en?->format('d/m/Y H:i'),
                            'anulado_por' => $primero->anuladoPor?->name,
                            'motivo_anulacion' => $primero->motivo_anulacion,
                        ];
                    })
                    ->concat(
                        $visitasPorVenta->get($v->id, collect())->map(fn (VisitaCobro $vis) => [
                            'tipo' => 'visita',
                            'sort_key' => $vis->created_at->timestamp,
                            'fecha' => $vis->created_at->format('d/m/Y H:i'),
                            'resultado' => $vis->resultado,
                            'usuario' => $vis->user?->name ?? 'Sistema',
                            'observaciones' => $vis->observaciones,
                            'promesa_fecha' => $vis->promesa_fecha?->format('d/m/Y'),
                            'foto_hogar_url' => $vis->foto_hogar ? Storage::url($vis->foto_hogar) : null,
                            'latitud' => $vis->latitud,
                            'longitud' => $vis->longitud,
                        ])
                    )
                    ->sortByDesc('sort_key')
                    ->values(),
                'cuotas_resumen' => $cuotas->isEmpty() ? null : [
                    'total' => $cuotas->count(),
                    'cobradas' => $cuotas->where('estado', 'cobrado')->count(),
                    'pendientes' => $cuotas->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])->count(),
                    'vencidas' => $cuotas->filter(fn ($g) => $g->estado !== 'cobrado' && \Carbon\Carbon::parse($g->fecha_vencimiento)->lt($hoy))->count(),
                ],
                'proxima_cuota' => $cuotas->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])->sortBy('fecha_vencimiento')->first()?->only(['numero_cuota', 'total_cuotas', 'monto_cuota', 'monto_pagado', 'fecha_vencimiento']),
            ];
        })->values();

        $historialRuta = Activity::where('log_name', 'cliente_ruta_cambio')
            ->where('subject_type', Cliente::class)
            ->where('subject_id', $cliente->id)
            ->with('causer:id,name')
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn (Activity $a) => [
                'fecha' => $a->created_at->format('d/m/Y H:i'),
                'usuario' => $a->causer?->name ?? 'Sistema',
                'ruta_anterior' => $a->properties->get('ruta_anterior_nombre'),
                'cobrador_anterior' => $a->properties->get('cobrador_anterior'),
                'ruta_nueva' => $a->properties->get('ruta_nueva_nombre'),
                'cobrador_nuevo' => $a->properties->get('cobrador_nuevo'),
            ])
            ->values();

        // Si el cliente no tiene ninguna venta no hay dónde mezclar las visitas —
        // se listan aparte para que no se pierdan.
        $visitasSinCobro = $cliente->ventas->isEmpty()
            ? $visitasCliente->map(fn (VisitaCobro $v) => [
                'fecha' => $v->created_at->format('d/m/Y H:i'),
                'resultado' => $v->resultado,
                'usuario' => $v->user?->name ?? 'Sistema',
                'observaciones' => $v->observaciones,
                'promesa_fecha' => $v->promesa_fecha?->format('d/m/Y'),
                'foto_hogar_url' => $v->foto_hogar ? Storage::url($v->foto_hogar) : null,
                'latitud' => $v->latitud,
                'longitud' => $v->longitud,
            ])->values()
            : collect();

        return response()->json([
            'cliente' => [
                'id' => $cliente->id,
                'codigo_anterior' => $cliente->codigo_anterior,
                'nombre' => $cliente->nombre_completo,
                'dui' => $cliente->dui,
                'nit' => $cliente->nit,
                'email' => $cliente->email,
                'telefono' => $cliente->telefono_normal,
                'whatsapp' => $cliente->telefono_whatsapp,
                'direccion' => $cliente->direccion,
                'departamento' => $cliente->departamento,
                'municipio' => $cliente->municipio,
                'distrito' => $cliente->distrito,
                'latitud' => $cliente->latitud,
                'longitud' => $cliente->longitud,
                'saldo' => (float) $cliente->saldo,
                'limite_credito' => $cliente->limite_credito !== null ? (float) $cliente->limite_credito : null,
                'ruta_nombre' => $cliente->rutaCobro?->nombre,
                'ruta_dia' => $cliente->rutaCobro?->dia_semana,
                'activo' => (bool) $cliente->activo,
                'dui_foto_frente' => $cliente->dui_foto_frente ? asset('storage/'.$cliente->dui_foto_frente) : null,
                'dui_foto_reverso' => $cliente->dui_foto_reverso ? asset('storage/'.$cliente->dui_foto_reverso) : null,
                'foto_casa' => $cliente->foto_casa ? asset('storage/'.$cliente->foto_casa) : null,
                'referencias_familiares' => collect([
                    ['nombre' => $cliente->ref_fam1_nombre, 'telefono' => $cliente->ref_fam1_telefono, 'parentesco' => $cliente->ref_fam1_parentesco],
                    ['nombre' => $cliente->ref_fam2_nombre, 'telefono' => $cliente->ref_fam2_telefono, 'parentesco' => $cliente->ref_fam2_parentesco],
                ])->filter(fn ($r) => filled($r['nombre']))->values(),
                'referencias_conocidas' => collect([
                    ['nombre' => $cliente->ref_con1_nombre, 'telefono' => $cliente->ref_con1_telefono, 'trabajo' => $cliente->ref_con1_trabajo],
                    ['nombre' => $cliente->ref_con2_nombre, 'telefono' => $cliente->ref_con2_telefono, 'trabajo' => $cliente->ref_con2_trabajo],
                ])->filter(fn ($r) => filled($r['nombre']))->values(),
            ],
            'ventas' => $ventas,
            'resumen' => [
                'total_ventas' => $ventas->count(),
                'total_comprado' => round($ventas->sum('total'), 2),
                'total_pagado' => round($ventas->sum('monto_pagado'), 2),
                'total_pendiente' => round($ventas->sum('saldo_pendiente'), 2),
            ],
            'historial_ruta' => $historialRuta,
            'visitas_sin_cobro' => $visitasSinCobro,
        ]);
    }

    /**
     * Genera el PDF del recibo de un pago (o grupo de pagos con el mismo
     * numero_recibo — una sola visita puede repartirse en varias cuotas),
     * con el mismo formato de tiquete que ya imprime la app móvil.
     */
    public function generarRecibo(Request $request, $tenant, string $numeroRecibo)
    {
        $pagos = PagoVenta::where('numero_recibo', $numeroRecibo)
            ->with([
                'cliente:id,nombre,apellido,codigo_anterior,direccion',
                'venta:id,numero_venta,total,saldo_pendiente',
                'venta.detalles.producto:id,nombre',
                'user:id,name',
                'user.cobrador:id,user_id,nombre,apellido',
            ])
            ->orderBy('id')
            ->get();

        if ($pagos->isEmpty()) {
            abort(404, 'No se encontró ese recibo.');
        }

        $primero = $pagos->first();
        $venta = $primero->venta;

        $productos = $pagos->flatMap(fn (PagoVenta $p) => $p->venta?->detalles ?? collect())
            ->pluck('producto.nombre')
            ->filter()
            ->unique()
            ->values();

        // "Debía"/"Resta" no se guardan en el pago — se reconstruyen a partir del
        // saldo actual de la venta y de los pagos posteriores a este recibo (si
        // el recibo se genera después de que ya hubo más abonos, el saldo actual
        // ya no refleja el momento de este pago).
        $abona = round((float) $pagos->sum('monto'), 2);
        $sumaPosteriores = $venta
            ? (float) $venta->pagos()->where('id', '>', $pagos->max('id'))->whereNull('anulado_en')->sum('monto')
            : 0.0;
        $resta = round((float) ($venta?->saldo_pendiente ?? 0) + $sumaPosteriores, 2);
        $debia = round($resta + $abona, 2);

        $proximaVisita = $venta
            ? GestionCobro::where('venta_id', $venta->id)
                ->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
                ->orderBy('fecha_vencimiento')
                ->value('fecha_vencimiento')
            : null;

        $cobrador = $primero->user?->cobrador;

        $pdf = Pdf::loadView('recibo-pago-pdf', [
            'numeroRecibo' => $numeroRecibo,
            'cliente' => $primero->cliente,
            'venta' => $venta,
            'fecha' => $primero->fecha_pago,
            'metodoPago' => $pagos->pluck('metodo_pago')->unique()->count() === 1 ? $primero->metodo_pago : 'Mixto',
            'nombreCobrador' => $cobrador?->nombre ?? $primero->user?->name,
            'productos' => $productos,
            'debia' => $debia,
            'abona' => $abona,
            'resta' => $resta,
            'proximaVisita' => $proximaVisita ? \Carbon\Carbon::parse($proximaVisita) : null,
            'anulado' => $primero->anulado_en !== null,
            'config' => ConfiguracionSistema::instance(),
        ]);

        $pdf->setPaper([0, 0, 226.77, 400], 'portrait');

        return $pdf->stream("Recibo-{$numeroRecibo}.pdf");
    }

    /**
     * Genera el Estado de Cuenta del cliente: cada venta a crédito con su
     * historial completo de abonos (fecha, recibo, monto, saldo restante
     * después de cada uno), y los totales generales. Incluye todas las
     * ventas a crédito del cliente, sin importar su estado, para que el
     * documento refleje el historial real completo, no solo lo pendiente.
     */
    public function generarEstadoCuenta(Request $request, $tenant, Cliente $cliente)
    {
        $ventas = $cliente->ventas()
            ->where('tipo_pago', 'credito')
            ->with(['pagos' => fn ($q) => $q->orderBy('fecha_pago')->orderBy('id')])
            ->orderBy('fecha_venta')
            ->get();

        $ventasConMovimientos = $ventas->map(function (Venta $venta) {
            $saldoCorrido = (float) $venta->total;
            $movimientos = collect();

            // La prima (abono inicial) no vive en pago_ventas -- se registra en la
            // venta misma, así que se agrega como el primer movimiento a mano.
            if ((float) $venta->prima > 0) {
                $saldoCorrido -= (float) $venta->prima;
                $movimientos->push([
                    'fecha' => $venta->fecha_venta,
                    'concepto' => 'Abono inicial (prima)',
                    'monto' => (float) $venta->prima,
                    'saldo' => $saldoCorrido,
                    'anulado' => false,
                ]);
            }

            foreach ($venta->pagos as $pago) {
                if ($pago->anulado_en) {
                    $movimientos->push([
                        'fecha' => $pago->fecha_pago,
                        'concepto' => 'Pago '.($pago->numero_recibo ?? '').' — ANULADO',
                        'monto' => 0,
                        'saldo' => $saldoCorrido,
                        'anulado' => true,
                    ]);

                    continue;
                }

                $saldoCorrido -= (float) $pago->monto;
                $movimientos->push([
                    'fecha' => $pago->fecha_pago,
                    'concepto' => 'Pago '.($pago->numero_recibo ?? 'sin recibo'),
                    'monto' => (float) $pago->monto,
                    'saldo' => $saldoCorrido,
                    'anulado' => false,
                ]);
            }

            return [
                'venta' => $venta,
                'movimientos' => $movimientos,
            ];
        });

        $totalVendido = (float) $ventas->sum('total');
        $totalPagado = (float) $ventas->sum('prima')
            + (float) $ventas->flatMap(fn (Venta $v) => $v->pagos)->whereNull('anulado_en')->sum('monto');

        $pdf = Pdf::loadView('estado-cuenta-pdf', [
            'cliente' => $cliente,
            'ventasConMovimientos' => $ventasConMovimientos,
            'totalVendido' => $totalVendido,
            'totalPagado' => $totalPagado,
            'saldoActual' => (float) $cliente->saldo,
            'config' => ConfiguracionSistema::instance(),
            'fechaEmision' => now(),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('Estado-de-cuenta-'.Str::slug($cliente->nombre_completo).'.pdf');
    }

    /**
     * Anula un recibo (todos los pagos que comparten ese numero_recibo) sin
     * borrar nada — el registro queda marcado como anulado y deja de contar
     * en el saldo/cuotas de la venta. Solo super_admin, con confirmación de
     * su propia contraseña y motivo obligatorio (mismo criterio que eliminar
     * un cliente, por tratarse de una reversión de dinero ya cobrado).
     */
    public function anularRecibo(Request $request, $tenant, string $numeroRecibo): JsonResponse
    {
        if (! auth()->user()?->hasRole('super_admin')) {
            return response()->json(['mensaje' => 'Solo un super administrador puede anular un recibo.'], 403);
        }

        $data = $request->validate([
            'password' => ['required', 'current_password'],
            'motivo' => ['required', 'string', 'max:255'],
        ], [
            'password.required' => 'Debes ingresar tu contraseña para confirmar.',
            'password.current_password' => 'La contraseña ingresada no es correcta.',
            'motivo.required' => 'Debes indicar el motivo de la anulación.',
        ]);

        try {
            $resultado = \App\Services\AnularReciboService::anular($numeroRecibo, auth()->id(), $data['motivo']);
        } catch (\RuntimeException $e) {
            return response()->json(['mensaje' => $e->getMessage()], 422);
        }

        return response()->json([
            'mensaje' => "Recibo {$numeroRecibo} anulado.",
            'cantidad' => $resultado['cantidad'],
            'monto_total' => $resultado['monto_total'],
        ]);
    }

    /**
     * Elimina un cliente junto con toda su gestión: ventas, detalle de venta,
     * pagos, cuotas, visitas de cobro y reintegros. Varias de esas tablas
     * tienen la FK a `clientes`/`ventas` en modo restrict (no cascade), así
     * que hay que borrar en el orden correcto para no chocar con la BD.
     * Solo super_admin, con confirmación de su propia contraseña.
     */
    public function eliminarCliente(Request $request, $tenant, Cliente $cliente): JsonResponse
    {
        if (! auth()->user()?->hasRole('super_admin')) {
            return response()->json(['mensaje' => 'Solo un super administrador puede eliminar un cliente.'], 403);
        }

        $data = $request->validate([
            'password' => ['required', 'current_password'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ], [
            'password.required' => 'Debes ingresar tu contraseña para confirmar.',
            'password.current_password' => 'La contraseña ingresada no es correcta.',
        ]);

        $resumen = [
            'cliente_id' => $cliente->id,
            'nombre' => $cliente->nombre_completo,
            'codigo_anterior' => $cliente->codigo_anterior,
            'telefono' => $cliente->telefono_normal,
            'direccion' => $cliente->direccion,
            'ruta' => $cliente->rutaCobro?->nombre,
            'saldo' => (float) $cliente->saldo,
            'ventas_count' => $cliente->ventas()->count(),
            'ventas_total' => round((float) $cliente->ventas()->sum('total'), 2),
            'pagos_count' => $cliente->pagosVenta()->count(),
            'pagos_total' => round((float) $cliente->pagosVenta()->sum('monto'), 2),
            'motivo' => $data['motivo'] ?? null,
        ];

        DB::transaction(function () use ($cliente) {
            // Orden obligatorio: reintegros y visitas referencian ventas/cuotas
            // en modo restrict, así que deben borrarse antes que esas filas.
            Reintegro::where('cliente_id', $cliente->id)->delete();
            VisitaCobro::where('cliente_id', $cliente->id)->delete();
            // detalle_ventas, pago_ventas y gestion_cobros cascadean por venta_id.
            Venta::where('cliente_id', $cliente->id)->delete();
            $cliente->delete();
        });

        activity('cliente_eliminado')
            ->causedBy(auth()->user())
            ->withProperties($resumen)
            ->log(sprintf(
                'Eliminó al cliente %s (%s) con toda su gestión — %d venta(s) por $%s%s',
                $resumen['nombre'],
                $resumen['codigo_anterior'] ?? 'sin código anterior',
                $resumen['ventas_count'],
                number_format($resumen['ventas_total'], 2),
                $resumen['motivo'] ? " — Motivo: {$resumen['motivo']}" : ''
            ));

        return response()->json(['mensaje' => 'Cliente eliminado junto con toda su gestión.']);
    }

    public function reordenar(Request $request, $tenant): JsonResponse
    {
        $data = $request->validate([
            'orden' => 'required|array|min:1',
            'orden.*' => 'required|integer|exists:clientes,id',
            // Cuando la lista está paginada, cada página solo reordena su propio
            // rango — el offset (posición absoluta del primer elemento de la
            // página) evita que dos páginas se pisen el mismo rango de "orden".
            'offset' => 'nullable|integer|min:0',
        ]);

        $offset = $data['offset'] ?? 0;

        DB::transaction(function () use ($data, $offset) {
            foreach ($data['orden'] as $posicion => $clienteId) {
                Cliente::where('id', $clienteId)->update(['orden' => $offset + $posicion + 1]);
            }
        });

        return response()->json(['mensaje' => 'Orden actualizado.']);
    }

    /**
     * Sugiere un orden de visita para una ruta usando "vecino más cercano"
     * sobre las coordenadas GPS (distancia en línea recta, fórmula de
     * Haversine) — arranca del cliente con el orden actual más bajo y en
     * cada paso salta al más cercano sin visitar. No es la ruta más corta
     * por calle (no considera tráfico ni sentido de las vías), es una
     * aproximación rápida y gratuita con los datos que ya tenemos.
     * Los clientes sin coordenadas quedan al final, en su orden actual —
     * esto NO aplica el orden, solo lo calcula; para aplicarlo el frontend
     * llama a /reordenar con el arreglo que aquí se devuelve.
     */
    public function sugerirOrden(Request $request, $tenant, int $rutaId): JsonResponse
    {
        $clientes = Cliente::where('ruta_cobro_id', $rutaId)
            ->where('activo', true)
            ->get(['id', 'latitud', 'longitud', 'orden']);

        $conGps = $clientes->filter(fn (Cliente $c) => $c->latitud && $c->longitud)->values();
        $sinGps = $clientes->reject(fn (Cliente $c) => $c->latitud && $c->longitud)->values();

        if ($conGps->count() < 2) {
            return response()->json([
                'mensaje' => 'Se necesitan al menos 2 clientes con coordenadas GPS en esta ruta para sugerir un orden.',
            ], 422);
        }

        $restantes = $conGps->keyBy('id');
        $actual = $conGps->sortBy(fn (Cliente $c) => $c->orden ?? PHP_INT_MAX)->first();
        $ordenSugerido = [$actual->id];
        $restantes->forget($actual->id);

        while ($restantes->isNotEmpty()) {
            $masCercano = $restantes->sortBy(
                fn (Cliente $c) => $this->distanciaKm((float) $actual->latitud, (float) $actual->longitud, (float) $c->latitud, (float) $c->longitud)
            )->first();

            $ordenSugerido[] = $masCercano->id;
            $restantes->forget($masCercano->id);
            $actual = $masCercano;
        }

        $ordenSugerido = array_merge(
            $ordenSugerido,
            $sinGps->sortBy(fn (Cliente $c) => $c->orden ?? PHP_INT_MAX)->pluck('id')->all()
        );

        return response()->json([
            'orden' => $ordenSugerido,
            'con_gps' => $conGps->count(),
            'sin_gps' => $sinGps->count(),
        ]);
    }

    /** Distancia en línea recta entre 2 coordenadas GPS, en kilómetros (fórmula de Haversine). */
    private function distanciaKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $radioTierra = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $radioTierra * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function cambiarRuta(Request $request, $tenant, Cliente $cliente): JsonResponse
    {
        $data = $request->validate([
            'ruta_cobro_id' => 'nullable|integer|exists:rutas_cobro,id',
        ]);

        $rutaAnterior = $cliente->rutaCobro()->with('cobrador:id,nombre,apellido')->first();
        $rutaNueva = ! empty($data['ruta_cobro_id'])
            ? RutaCobro::with('cobrador:id,nombre,apellido')->find($data['ruta_cobro_id'])
            : null;

        $cliente->update([
            'ruta_cobro_id' => $data['ruta_cobro_id'] ?? null,
            'orden' => null,
        ]);

        $this->registrarMovimientoRuta($cliente, $rutaAnterior, $rutaNueva);

        return response()->json(['mensaje' => 'Cliente actualizado.']);
    }

    /**
     * Registra en el historial de movimiento (visible en "Ver detalle" del
     * cliente) cada vez que su ruta/cobrador cambia — incluyendo la primera
     * asignación al crear el cliente (ahí $rutaAnterior va null).
     */
    private function registrarMovimientoRuta(Cliente $cliente, ?RutaCobro $rutaAnterior, ?RutaCobro $rutaNueva): void
    {
        $nombreCobrador = fn (?RutaCobro $r) => $r?->cobrador ? trim($r->cobrador->nombre.' '.$r->cobrador->apellido) : null;

        activity('cliente_ruta_cambio')
            ->causedBy(auth()->user())
            ->performedOn($cliente)
            ->withProperties([
                'ruta_anterior_id' => $rutaAnterior?->id,
                'ruta_anterior_nombre' => $rutaAnterior?->nombre,
                'cobrador_anterior' => $nombreCobrador($rutaAnterior),
                'ruta_nueva_id' => $rutaNueva?->id,
                'ruta_nueva_nombre' => $rutaNueva?->nombre,
                'cobrador_nuevo' => $nombreCobrador($rutaNueva),
            ])
            ->log(sprintf(
                'Movió a %s de "%s" a "%s"',
                $cliente->nombre_completo,
                $rutaAnterior?->nombre ?? 'Sin ruta',
                $rutaNueva?->nombre ?? 'Sin ruta'
            ));
    }

    public function actualizarAbonoInicial(Request $request, $tenant, Cliente $cliente): JsonResponse
    {
        $data = $request->validate([
            'venta_id' => 'required|integer',
            'monto' => 'required|numeric|min:0',
        ]);

        $venta = $cliente->ventas()->where('tipo_pago', 'credito')->where('id', $data['venta_id'])->first();

        if (! $venta) {
            return response()->json(['mensaje' => 'Esta venta no pertenece a este cliente.'], 422);
        }

        $primerPagoId = $venta->pagos()->whereNull('anulado_en')->oldest('fecha_pago')->value('id');
        $otrosPagos = (float) $venta->pagos()
            ->whereNull('anulado_en')
            ->when($primerPagoId, fn ($q) => $q->where('id', '!=', $primerPagoId))
            ->sum('monto');

        if ($data['monto'] + $otrosPagos > (float) $venta->total) {
            return response()->json(['mensaje' => 'El abono supera el total de la venta ($'.number_format($venta->total, 2).').'], 422);
        }

        DB::transaction(function () use ($venta, $data) {
            $pagoInicial = $venta->pagos()->whereNull('anulado_en')->oldest('fecha_pago')->first();

            if ($pagoInicial) {
                $pagoInicial->update(['monto' => $data['monto']]);
            } else {
                PagoVenta::create([
                    'venta_id' => $venta->id,
                    'cliente_id' => $venta->cliente_id,
                    'user_id' => auth()->id() ?? 1,
                    'monto' => $data['monto'],
                    'fecha_pago' => $venta->fecha_venta->toDateString(),
                    'metodo_pago' => 'efectivo',
                    'observaciones' => 'Saldo inicial importado (cobro en papel)',
                ]);
            }

            $venta->refresh();
            $totalPagado = round((float) $venta->prima + (float) $venta->pagos()->whereNull('anulado_en')->sum('monto'), 2);
            $venta->monto_pagado = $totalPagado;
            $venta->saldo_pendiente = max(0, round($venta->total - $totalPagado, 2));
            $venta->estado = $venta->saldo_pendiente <= 0 ? 'completada' : 'pendiente';
            $venta->save();

            $this->redistribuirCuotas($venta, $totalPagado);

            $cliente = $venta->cliente;
            $cliente->saldo = round($cliente->ventas()->sum('saldo_pendiente'), 2);
            $cliente->save();
        });

        return response()->json(['mensaje' => 'Abono inicial actualizado.']);
    }

    /**
     * Corrige el monto total de los pagos de una venta registrados en una
     * misma fecha (p.ej. una sola visita del cobrador que quedó repartida en
     * varios pagos, uno por cada cuota que se llenó ese día). Consolida todos
     * esos pagos en uno solo con el monto correcto.
     */
    public function actualizarPagoFecha(Request $request, $tenant, Cliente $cliente): JsonResponse
    {
        $data = $request->validate([
            'venta_id' => 'required|integer',
            'fecha_pago' => 'required|date',
            'numero_recibo' => 'nullable|string',
            'monto' => 'required|numeric|min:0',
        ]);

        $venta = $cliente->ventas()->where('id', $data['venta_id'])->first();

        if (! $venta) {
            return response()->json(['mensaje' => 'Esta venta no pertenece a este cliente.'], 422);
        }

        // Los pagos se agrupan (y se corrigen) por numero_recibo — una sola visita
        // puede repartirse en varias cuotas con el mismo recibo. Los registros
        // antiguos sin recibo asignado siguen agrupándose por fecha, como antes.
        // Un recibo ya anulado no se puede "corregir" — primero hay que reactivarlo.
        $pagosDelGrupo = ! empty($data['numero_recibo'])
            ? $venta->pagos()->where('numero_recibo', $data['numero_recibo'])->whereNull('anulado_en')->orderBy('id')->get()
            : $venta->pagos()->whereDate('fecha_pago', $data['fecha_pago'])->whereNull('numero_recibo')->whereNull('anulado_en')->orderBy('id')->get();

        if ($pagosDelGrupo->isEmpty()) {
            return response()->json(['mensaje' => 'No se encontraron pagos vigentes para ese recibo en esta venta.'], 422);
        }

        $totalTodosPagos = (float) $venta->pagos()->whereNull('anulado_en')->sum('monto');
        $otrosPagos = round($totalTodosPagos - (float) $pagosDelGrupo->sum('monto'), 2);

        if ($data['monto'] + $otrosPagos + (float) $venta->prima > (float) $venta->total) {
            return response()->json(['mensaje' => 'El monto supera el total de la venta ($'.number_format($venta->total, 2).').'], 422);
        }

        DB::transaction(function () use ($venta, $pagosDelGrupo, $data) {
            $principal = $pagosDelGrupo->first();
            $pagosDelGrupo->skip(1)->each(fn (PagoVenta $p) => $p->delete());
            $principal->update(['monto' => $data['monto']]);

            $venta->refresh();
            $totalPagado = round((float) $venta->prima + (float) $venta->pagos()->whereNull('anulado_en')->sum('monto'), 2);
            $venta->monto_pagado = $totalPagado;
            $venta->saldo_pendiente = max(0, round($venta->total - $totalPagado, 2));
            $venta->estado = $venta->saldo_pendiente <= 0 ? 'completada' : 'pendiente';
            $venta->save();

            $this->redistribuirCuotas($venta, $totalPagado);

            $cliente = $venta->cliente;
            $cliente->saldo = round($cliente->ventas()->sum('saldo_pendiente'), 2);
            $cliente->save();
        });

        return response()->json(['mensaje' => 'Pago actualizado.']);
    }

    public function actualizarPrecioVenta(Request $request, $tenant, Cliente $cliente): JsonResponse
    {
        $data = $request->validate([
            'venta_id' => 'required|integer',
            'total' => 'required|numeric|min:0.01',
        ]);

        $venta = $cliente->ventas()->where('tipo_pago', 'credito')->where('id', $data['venta_id'])->first();

        if (! $venta) {
            return response()->json(['mensaje' => 'Esta venta no pertenece a este cliente.'], 422);
        }

        $totalPagado = round((float) $venta->prima + (float) $venta->pagos()->whereNull('anulado_en')->sum('monto'), 2);
        $nuevoTotal = round((float) $data['total'], 2);

        if ($nuevoTotal < $totalPagado) {
            return response()->json(['mensaje' => 'El nuevo precio ($'.number_format($nuevoTotal, 2).') no puede ser menor a lo ya pagado ($'.number_format($totalPagado, 2).').'], 422);
        }

        DB::transaction(function () use ($venta, $nuevoTotal, $totalPagado) {
            $venta->subtotal = $nuevoTotal;
            $venta->total = $nuevoTotal;
            $venta->saldo_pendiente = max(0, round($nuevoTotal - $totalPagado, 2));
            $venta->estado = $venta->saldo_pendiente <= 0 ? 'completada' : 'pendiente';
            $venta->save();

            $detalle = $venta->detalles()->first();
            if ($detalle) {
                $detalle->update(['precio_unitario' => $nuevoTotal, 'subtotal' => $nuevoTotal]);
            }

            $this->redistribuirCuotas($venta, $totalPagado, recalcularMontoCuota: true);

            $cliente = $venta->cliente;
            $cliente->saldo = round($cliente->ventas()->sum('saldo_pendiente'), 2);
            $cliente->save();
        });

        return response()->json(['mensaje' => 'Precio de la venta actualizado.']);
    }

    /**
     * Corrige el vendedor y/o la fecha de una venta desde el perfil del
     * cliente — pensado para arreglar datos mal capturados (ej. ventas
     * importadas sin vendedor, o con la fecha equivocada), sin necesidad de
     * entrar al panel principal de Filament.
     */
    public function actualizarVentaVendedorFecha(Request $request, $tenant, Cliente $cliente): JsonResponse
    {
        if (! (auth()->user()?->hasRole('super_admin') ?? false)) {
            return response()->json(['mensaje' => 'No tenés permiso para hacer este cambio.'], 403);
        }

        $data = $request->validate([
            'venta_id' => 'required|integer',
            'vendedor_id' => 'nullable|integer|exists:vendedores,id',
            'fecha_venta' => 'nullable|date',
        ]);

        $venta = $cliente->ventas()->where('id', $data['venta_id'])->first();

        if (! $venta) {
            return response()->json(['mensaje' => 'Esta venta no pertenece a este cliente.'], 422);
        }

        $venta->vendedor_id = $data['vendedor_id'] ?? null;

        if (! empty($data['fecha_venta'])) {
            $venta->fecha_venta = $data['fecha_venta'];
        }

        $venta->save();

        return response()->json(['mensaje' => 'Venta actualizada.']);
    }

    /**
     * Redistribuye el total pagado entre las cuotas en orden (FIFO).
     * Si $recalcularMontoCuota es true, primero recalcula monto_cuota = venta->total / 20
     * (cuotas 1-20, residuo en la 20; las cuotas 21-24 son de colchón con el mismo valor base).
     */
    private function redistribuirCuotas(Venta $venta, float $totalPagado, bool $recalcularMontoCuota = false): void
    {
        $cuotas = $venta->gestionesCobro()->orderBy('numero_cuota')->get();

        if ($recalcularMontoCuota) {
            $montoBase = floor(($venta->total / 20) * 100) / 100;
            $residuo = round($venta->total - ($montoBase * 20), 2);
        }

        $restante = $totalPagado;
        foreach ($cuotas as $cuota) {
            if ($recalcularMontoCuota) {
                $montoCuota = ($cuota->numero_cuota === 20) ? round($montoBase + $residuo, 2) : $montoBase;
            } else {
                $montoCuota = (float) $cuota->monto_cuota;
            }

            $pagadoCuota = round(min($restante, $montoCuota), 2);
            $restante = round($restante - $pagadoCuota, 2);

            $estado = 'pendiente';
            if ($pagadoCuota >= $montoCuota) { $estado = 'cobrado'; }
            elseif ($pagadoCuota > 0) { $estado = 'parcialmente_cobrado'; }

            $cuota->update(['monto_cuota' => $montoCuota, 'monto_pagado' => $pagadoCuota, 'estado' => $estado]);
        }
    }

    public function actualizarCampo(Request $request, $tenant, Cliente $cliente): JsonResponse
    {
        $data = $request->validate([
            'campo' => 'required|in:nombre,codigo_anterior,telefono,direccion,saldo',
            'valor' => 'required',
        ]);

        switch ($data['campo']) {
            case 'nombre':
                $valor = trim((string) $data['valor']);
                if ($valor === '') {
                    return response()->json(['mensaje' => 'El nombre no puede quedar vacío.'], 422);
                }
                $partes = preg_split('/\s+/', $valor, 2);
                $cliente->nombre = $partes[0];
                $cliente->apellido = $partes[1] ?? '';
                break;

            case 'codigo_anterior':
                $cliente->codigo_anterior = trim((string) $data['valor']) ?: null;
                break;

            case 'telefono':
                $cliente->telefono_normal = trim((string) $data['valor']);
                break;

            case 'direccion':
                $cliente->direccion = trim((string) $data['valor']);
                break;

            case 'saldo':
                if (! is_numeric($data['valor']) || (float) $data['valor'] < 0) {
                    return response()->json(['mensaje' => 'Saldo inválido.'], 422);
                }
                $cliente->saldo = round((float) $data['valor'], 2);
                break;
        }

        $cliente->save();

        return response()->json(['mensaje' => 'Cliente actualizado.']);
    }

    /**
     * Fija la ubicación GPS de un cliente desde /clientes-ruta, sin tener que
     * entrar a su ficha completa. Acepta lat/lng ya separados, o el texto tal
     * cual llega al pegar una ubicación de WhatsApp (mismo patrón que el
     * "Pegar ubicación de WhatsApp" del formulario de edición de cliente).
     */
    public function actualizarUbicacion(Request $request, $tenant, Cliente $cliente): JsonResponse
    {
        $data = $request->validate([
            'texto' => 'nullable|string',
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180',
        ]);

        $lat = $data['latitud'] ?? null;
        $lng = $data['longitud'] ?? null;

        if (($lat === null || $lng === null) && ! empty($data['texto'])) {
            if (preg_match('/(-?\d{1,3}\.\d{3,})[,\s]+(-?\d{1,3}\.\d{3,})/', $data['texto'], $m)) {
                $lat = (float) $m[1];
                $lng = (float) $m[2];
            }
        }

        if ($lat === null || $lng === null || abs((float) $lat) > 90 || abs((float) $lng) > 180) {
            return response()->json(['mensaje' => 'No se encontraron coordenadas válidas en lo que pegaste. Copiá el link de ubicación de WhatsApp tal cual, o las coordenadas separadas por coma.'], 422);
        }

        $cliente->update([
            'latitud' => round((float) $lat, 6),
            'longitud' => round((float) $lng, 6),
        ]);

        return response()->json(['mensaje' => 'Ubicación guardada.']);
    }

    // ── Importación de Excel ─────────────────────────────────────────────────

    public function previewExcel(Request $request, $tenant): JsonResponse
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $archivo = $request->file('archivo');
        $token = Str::random(20) . '.' . $archivo->getClientOriginalExtension();
        $path = $archivo->storeAs('imports-temp', $token, 'local');

        [$filas, $totalFilas] = $this->leerExcel(Storage::disk('local')->path($path), limite: 6);

        if (empty($filas)) {
            Storage::disk('local')->delete($path);
            return response()->json(['mensaje' => 'El archivo no tiene datos legibles.'], 422);
        }

        $encabezados = array_keys($filas[0]);

        return response()->json([
            'token' => $token,
            'encabezados' => $encabezados,
            'muestra' => $filas,
            'total_filas_detectadas' => $totalFilas,
            'campos' => self::CAMPOS_IMPORTACION,
        ]);
    }

    public function procesarExcel(Request $request, $tenant): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required|string',
            'mapeo' => 'required|array',
            'mapeo.*' => 'nullable|string',
            'mapeo.nombre' => 'required|string',
            'mapeo.valor_total' => 'required|string',
            'fila_inicio' => 'required|integer|min:1',
            'ruta_modo' => 'required|in:nueva,existente',
            'ruta_cobro_id' => 'required_if:ruta_modo,existente|nullable|integer|exists:rutas_cobro,id',
            'ruta_nombre' => 'required_if:ruta_modo,nueva|nullable|string|max:150',
            'ruta_dia' => 'required_if:ruta_modo,nueva|nullable|in:lunes,martes,miércoles,jueves,viernes,sábado,domingo',
            'ruta_cobrador_id' => 'required_if:ruta_modo,nueva|nullable|integer|exists:cobradores,id',
        ]);

        $path = Storage::disk('local')->path('imports-temp/' . basename($data['token']));
        if (! file_exists($path)) {
            return response()->json(['mensaje' => 'El archivo expiró, vuelve a subirlo.'], 422);
        }

        [$todasLasFilas] = $this->leerExcel($path, limite: null, desde: $data['fila_inicio']);

        if (empty($todasLasFilas)) {
            return response()->json(['mensaje' => 'No se encontraron filas de datos a partir de la fila indicada.'], 422);
        }

        $mapeo = $data['mapeo'];

        $resultado = DB::transaction(function () use ($data, $mapeo, $todasLasFilas) {
            if ($data['ruta_modo'] === 'nueva') {
                $ruta = RutaCobro::create([
                    'sucursal_id' => 1,
                    'cobrador_id' => $data['ruta_cobrador_id'],
                    'nombre' => $data['ruta_nombre'],
                    'dia_semana' => $data['ruta_dia'],
                    'descripcion' => 'Importada desde Excel',
                    'activa' => true,
                ]);
            } else {
                $ruta = RutaCobro::findOrFail($data['ruta_cobro_id']);
            }

            $vendedorCache = [];
            $productoCache = [];
            foreach (Producto::all(['id', 'nombre']) as $p) {
                $productoCache[mb_strtolower(trim($p->nombre))] = $p->id;
            }

            $clientesCreados = 0;
            $ventasCreadas = 0;
            $pagosCreados = 0;
            $cuotasCreadas = 0;
            $omitidas = [];

            foreach ($todasLasFilas as $i => $fila) {
                $get = fn (string $campo) => isset($mapeo[$campo], $fila[$mapeo[$campo]]) ? trim((string) $fila[$mapeo[$campo]]) : null;

                $nombreCompleto = $get('nombre');
                $valorTotalRaw = $get('valor_total');
                $valorTotalNum = $this->parseNumero($valorTotalRaw);

                if (! $nombreCompleto || $valorTotalNum === null) {
                    $omitidas[] = $i + 1;
                    continue;
                }

                $valorTotal = $valorTotalNum;
                $montoCobrado = $this->parseNumero($get('monto_cobrado')) ?? 0.0;
                $saldoNum = $this->parseNumero($get('saldo_pendiente'));
                $saldo = $saldoNum ?? round($valorTotal - $montoCobrado, 2);

                $partes = preg_split('/\s+/', $nombreCompleto, 2);
                $nombre = $partes[0];
                $apellido = $partes[1] ?? '';

                $cliente = Cliente::create([
                    'codigo_anterior' => $get('codigo_anterior'),
                    'sucursal_id' => 1,
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'telefono_normal' => $get('telefono'),
                    'telefono_whatsapp' => $get('telefono'),
                    'direccion' => $get('direccion'),
                    'saldo' => $saldo,
                    'activo' => true,
                    'ruta_cobro_id' => $ruta->id,
                ]);
                $clientesCreados++;

                $fechaRaw = $get('fecha_venta');
                try {
                    $fechaVenta = $fechaRaw ? \Carbon\Carbon::parse($fechaRaw) : now();
                } catch (\Throwable) {
                    $fechaVenta = now();
                }

                $nombreProducto = $get('producto') ?: 'Producto importado';

                $vendedorNombre = $get('vendedor');
                $vendedorId = null;
                if ($vendedorNombre) {
                    if (! isset($vendedorCache[$vendedorNombre])) {
                        $vendedorCache[$vendedorNombre] = Vendedor::firstOrCreate(
                            ['nombre' => $vendedorNombre],
                            ['sucursal_id' => 1, 'apellido' => '', 'activo' => true]
                        )->id;
                    }
                    $vendedorId = $vendedorCache[$vendedorNombre];
                }

                $resultadoVenta = $this->crearVentaCredito(
                    $cliente, $valorTotal, $montoCobrado, $saldo, $fechaVenta,
                    $nombreProducto, $vendedorId, ($get('codigo_anterior') ?? '—'),
                    $productoCache,
                );
                $ventasCreadas++;
                $cuotasCreadas += $resultadoVenta['cuotas'];
                if ($resultadoVenta['tuvo_pago']) $pagosCreados++;
            }

            return [
                'ruta' => $ruta,
                'clientes' => $clientesCreados,
                'ventas' => $ventasCreadas,
                'pagos' => $pagosCreados,
                'cuotas' => $cuotasCreadas,
                'omitidas' => $omitidas,
            ];
        });

        Storage::disk('local')->delete('imports-temp/' . basename($data['token']));

        return response()->json([
            'mensaje' => "Importación completa: {$resultado['clientes']} clientes en la ruta \"{$resultado['ruta']->nombre}\".",
            'ruta_id' => $resultado['ruta']->id,
            'clientes' => $resultado['clientes'],
            'ventas' => $resultado['ventas'],
            'pagos' => $resultado['pagos'],
            'cuotas' => $resultado['cuotas'],
            'filas_omitidas' => $resultado['omitidas'],
        ]);
    }

    /**
     * Crea una venta a crédito completa para un cliente: venta, detalle, las 24
     * cuotas quincenales (20 + 4 de colchón) y el abono inicial si aplica.
     * Usa $productoCache (nombre normalizado => id) compartido entre llamadas
     * para no repetir búsquedas en `productos`.
     */
    private function crearVentaCredito(
        Cliente $cliente,
        float $valorTotal,
        float $montoCobrado,
        float $saldo,
        \Carbon\Carbon $fechaVenta,
        string $nombreProducto,
        ?int $vendedorId,
        string $codigoParaObservacion,
        array &$productoCache = [],
    ): array {
        $productoId = $this->resolverProducto($nombreProducto, $productoCache);
        $estado = $saldo <= 0 ? 'completada' : 'pendiente';

        $venta = Venta::create([
            'cliente_id' => $cliente->id,
            'user_id' => auth()->id() ?? 1,
            'vendedor_id' => $vendedorId,
            'sucursal_id' => 1,
            'fecha_venta' => $fechaVenta,
            'dias_credito' => 0,
            'estado' => $estado,
            'tipo_pago' => 'credito',
            'subtotal' => $valorTotal,
            'descuento_porcentaje' => 0,
            'descuento_monto' => 0,
            'impuesto_porcentaje' => 0,
            'impuesto_monto' => 0,
            'total' => $valorTotal,
            'prima' => 0,
            'monto_pagado' => $montoCobrado,
            'saldo_pendiente' => $saldo,
            'observaciones' => "Código anterior: {$codigoParaObservacion}. Producto: {$nombreProducto}",
        ]);

        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $productoId,
            'cantidad' => 1,
            'precio_unitario' => $valorTotal,
            'descuento_porcentaje' => 0,
            'subtotal' => $valorTotal,
            'tipo_pago' => 'credito',
        ]);

        // Cuotas: 20 base + 4 extra, quincenales desde la fecha de venta
        $montoBase = floor(($valorTotal / 20) * 100) / 100;
        $residuo = round($valorTotal - ($montoBase * 20), 2);
        $restante = $montoCobrado;
        $gestiones = [];

        for ($n = 1; $n <= 24; $n++) {
            $montoCuota = ($n === 20) ? round($montoBase + $residuo, 2) : $montoBase;
            $pagadoCuota = round(min($restante, $montoCuota), 2);
            $restante = round($restante - $pagadoCuota, 2);

            $estadoCuota = 'pendiente';
            if ($pagadoCuota >= $montoCuota) { $estadoCuota = 'cobrado'; }
            elseif ($pagadoCuota > 0) { $estadoCuota = 'parcialmente_cobrado'; }

            $gestiones[] = [
                'venta_id' => $venta->id,
                'cliente_id' => $cliente->id,
                'numero_cuota' => $n,
                'total_cuotas' => 24,
                'monto_cuota' => $montoCuota,
                'monto_pagado' => $pagadoCuota,
                'fecha_vencimiento' => $fechaVenta->copy()->addDays(14 * $n)->toDateString(),
                'estado' => $estadoCuota,
                'observaciones' => $n > 20 ? 'Cuota extra' : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        GestionCobro::insert($gestiones);

        $tuvoPago = false;
        if ($montoCobrado > 0) {
            PagoVenta::create([
                'venta_id' => $venta->id,
                'cliente_id' => $cliente->id,
                'user_id' => auth()->id() ?? 1,
                'monto' => $montoCobrado,
                'fecha_pago' => $fechaVenta->toDateString(),
                'metodo_pago' => 'efectivo',
                'observaciones' => 'Saldo inicial importado (cobro en papel)',
            ]);
            $tuvoPago = true;
        }

        return ['venta' => $venta, 'cuotas' => count($gestiones), 'tuvo_pago' => $tuvoPago];
    }

    public function crearCliente(Request $request, $tenant): JsonResponse
    {
        $data = $request->validate([
            'codigo_anterior' => 'nullable|string|max:100',
            'nombre' => 'required|string|max:200',
            'telefono' => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:500',
            'ruta_cobro_id' => 'nullable|integer|exists:rutas_cobro,id',
            'tiene_venta' => 'required|boolean',
            'producto' => 'nullable|string|max:150',
            'valor_total' => 'required_if:tiene_venta,1|nullable|numeric|min:0.01',
            'monto_cobrado' => 'nullable|numeric|min:0',
            'fecha_venta' => 'nullable|date',
            'vendedor_id' => 'nullable|integer|exists:vendedores,id',
        ]);

        $partes = preg_split('/\s+/', trim($data['nombre']), 2);
        $nombre = $partes[0];
        $apellido = $partes[1] ?? '';

        $montoCobrado = (float) ($data['monto_cobrado'] ?? 0);
        $valorTotal = (float) ($data['valor_total'] ?? 0);

        if ($data['tiene_venta'] && $montoCobrado > $valorTotal) {
            return response()->json(['mensaje' => 'El monto ya cobrado no puede ser mayor al valor total.'], 422);
        }

        $resultado = DB::transaction(function () use ($data, $nombre, $apellido, $montoCobrado, $valorTotal) {
            $cliente = Cliente::create([
                'codigo_anterior' => $data['codigo_anterior'] ?? null,
                'sucursal_id' => 1,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'telefono_normal' => $data['telefono'] ?? null,
                'telefono_whatsapp' => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'saldo' => $data['tiene_venta'] ? round($valorTotal - $montoCobrado, 2) : 0,
                'activo' => true,
                'ruta_cobro_id' => $data['ruta_cobro_id'] ?? null,
            ]);

            $ventaInfo = null;
            if ($data['tiene_venta']) {
                $fechaVenta = ! empty($data['fecha_venta']) ? \Carbon\Carbon::parse($data['fecha_venta']) : now();
                $saldo = round($valorTotal - $montoCobrado, 2);
                $productoCache = [];

                $resultadoVenta = $this->crearVentaCredito(
                    $cliente, $valorTotal, $montoCobrado, $saldo, $fechaVenta,
                    $data['producto'] ?: 'Producto', $data['vendedor_id'] ?? null,
                    $data['codigo_anterior'] ?? '—', $productoCache,
                );
                $ventaInfo = $resultadoVenta['venta'];
            }

            return ['cliente' => $cliente, 'venta' => $ventaInfo];
        });

        if (! empty($data['ruta_cobro_id'])) {
            $this->registrarMovimientoRuta(
                $resultado['cliente'],
                null,
                RutaCobro::with('cobrador:id,nombre,apellido')->find($data['ruta_cobro_id'])
            );
        }

        return response()->json([
            'mensaje' => 'Cliente "' . $resultado['cliente']->nombre_completo . '" creado correctamente.',
            'cliente_id' => $resultado['cliente']->id,
        ], 201);
    }

    /**
     * Convierte valores tipo " $ 160.00 ", "160,00", "8.00" a float.
     * Devuelve null si no se puede interpretar como número.
     */
    private function parseNumero(?string $valor): ?float
    {
        if ($valor === null) return null;
        $limpio = preg_replace('/[^0-9.,\-]/', '', $valor);
        $limpio = str_replace(',', '', $limpio);
        if ($limpio === '' || ! is_numeric($limpio)) return null;
        return (float) $limpio;
    }

    private function resolverProducto(string $nombre, array &$cache): int
    {
        $norm = mb_strtolower(trim($nombre));
        $sinPrefijo = preg_replace('/^1\s+/', '', $norm);

        if (isset($cache[$norm])) return $cache[$norm];
        if (isset($cache[$sinPrefijo])) return $cache[$sinPrefijo];

        foreach ($cache as $nombreExistente => $id) {
            if (str_contains($nombreExistente, $sinPrefijo) || str_contains($sinPrefijo, $nombreExistente)) {
                return $id;
            }
        }

        $nuevo = Producto::create([
            'nombre' => $nombre,
            'codigo' => 'PROD-' . strtoupper(Str::random(6)),
            'unidad_medida' => 'unidad',
            'precio_compra' => 0,
            'precio_venta' => 0,
            'stock' => 0,
            'stock_minimo' => 0,
            'activo' => true,
            'sucursal_id' => 1,
        ]);
        $cache[$norm] = $nuevo->id;

        return $nuevo->id;
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function leerExcel(string $path, ?int $limite, int $desde = 1): array
    {
        $spreadsheet = IOFactory::load($path);
        $hoja = $spreadsheet->getActiveSheet();
        $filasCrudas = $hoja->toArray(null, true, true, true);

        $filasNoVacias = array_filter($filasCrudas, fn ($f) => collect($f)->filter(fn ($v) => $v !== null && trim((string) $v) !== '')->isNotEmpty());

        if (empty($filasNoVacias)) {
            return [[], 0];
        }

        $primeraFilaNum = array_key_first($filasNoVacias);
        $encabezados = $filasCrudas[$primeraFilaNum];
        $encabezados = array_map(fn ($v) => $v !== null ? trim((string) $v) : '', $encabezados);

        // columnas sin encabezado legible -> usar letra de columna
        foreach ($encabezados as $col => $nombre) {
            if ($nombre === '') $encabezados[$col] = "Columna {$col}";
        }

        $filasDatos = [];
        foreach ($filasCrudas as $numFila => $fila) {
            if ($numFila <= $primeraFilaNum) continue;
            if ($numFila < $desde) continue;
            $vacia = collect($fila)->filter(fn ($v) => $v !== null && trim((string) $v) !== '')->isEmpty();
            if ($vacia) continue;

            $filaAsoc = [];
            foreach ($encabezados as $col => $nombreCol) {
                $valor = $fila[$col] ?? null;
                $filaAsoc[$nombreCol] = $valor;
            }
            $filasDatos[] = $filaAsoc;

            if ($limite !== null && count($filasDatos) >= $limite) break;
        }

        $total = $limite !== null ? count(array_filter($filasCrudas, fn ($f, $n) => $n > $primeraFilaNum && collect($f)->filter(fn ($v) => $v !== null && trim((string) $v) !== '')->isNotEmpty(), ARRAY_FILTER_USE_BOTH)) : count($filasDatos);

        return [$filasDatos, $total];
    }
}
