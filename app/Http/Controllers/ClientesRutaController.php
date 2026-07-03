<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cobrador;
use App\Models\DetalleVenta;
use App\Models\GestionCobro;
use App\Models\PagoVenta;
use App\Models\Producto;
use App\Models\RutaCobro;
use App\Models\Vendedor;
use App\Models\Venta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

        $rutas = RutaCobro::withCount('clientes')->orderBy('nombre')->get();

        if (! $rutaId && $rutas->isNotEmpty()) {
            $rutaId = $rutas->first()->id;
        }

        $sinRuta = Cliente::whereNull('ruta_cobro_id')->where('activo', true)->count();
        $cobradores = Cobrador::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'apellido']);
        $camposImportacion = self::CAMPOS_IMPORTACION;

        return view('pos.clientes-ruta', compact('tenant', 'rutas', 'rutaId', 'sinRuta', 'cobradores', 'camposImportacion'));
    }

    public function data(Request $request, $tenant): JsonResponse
    {
        $rutaId = $request->get('ruta_cobro_id');
        $buscar = trim((string) $request->get('buscar', ''));

        $query = Cliente::where('activo', true);

        if ($rutaId === 'sin_ruta') {
            $query->whereNull('ruta_cobro_id');
        } elseif ($rutaId !== 'todos') {
            $query->where('ruta_cobro_id', $rutaId);
        }

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo_anterior', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%")
                    ->orWhere('apellido', 'like', "%{$buscar}%");
            });
        }

        $clientes = $query
            ->with(['rutaCobro:id,nombre', 'ventas' => fn ($q) => $q->where('tipo_pago', 'credito')->oldest('fecha_venta')->with(['pagos' => fn ($p) => $p->oldest('fecha_pago')])])
            ->withCount(['ventas as ventas_count' => fn ($q) => $q->where('saldo_pendiente', '>', 0)])
            ->orderByRaw('orden IS NULL, orden ASC')
            ->orderBy('nombre')
            ->get()
            ->map(function (Cliente $c) {
                $ventasCredito = $c->ventas->map(fn ($v) => [
                    'venta_id' => $v->id,
                    'total' => (float) $v->total,
                    'saldo_pendiente' => (float) $v->saldo_pendiente,
                    'monto_pagado' => (float) $v->monto_pagado,
                    'abono_inicial' => $v->pagos->first() ? (float) $v->pagos->first()->monto : null,
                ])->values();

                return [
                    'id' => $c->id,
                    'orden' => $c->orden,
                    'codigo_anterior' => $c->codigo_anterior,
                    'nombre' => $c->nombre_completo,
                    'telefono' => $c->telefono_normal,
                    'direccion' => trim(collect([$c->direccion, $c->municipio, $c->departamento])->filter()->implode(', ')),
                    'direccion_raw' => $c->direccion,
                    'tiene_ubicacion' => (bool) ($c->latitud && $c->longitud),
                    'saldo' => (float) $c->saldo,
                    'ventas_pendientes' => (int) $c->ventas_count,
                    'ruta_cobro_id' => $c->ruta_cobro_id,
                    'ruta_nombre' => $c->rutaCobro?->nombre,
                    'ventas_credito' => $ventasCredito,
                    'total_pagado_cliente' => round($ventasCredito->sum('monto_pagado'), 2),
                ];
            })
            ->values();

        return response()->json([
            'clientes' => $clientes,
            'total_saldo' => round($clientes->sum('saldo'), 2),
            'total_pagado' => round($clientes->sum('total_pagado_cliente'), 2),
            'total_clientes' => $clientes->count(),
        ]);
    }

    public function detalleCliente(Request $request, $tenant, Cliente $cliente): JsonResponse
    {
        $cliente->load([
            'rutaCobro:id,nombre,dia_semana',
            'ventas' => fn ($q) => $q->orderBy('fecha_venta')
                ->with([
                    'pagos' => fn ($p) => $p->orderBy('fecha_pago'),
                    'gestionesCobro' => fn ($g) => $g->orderBy('numero_cuota'),
                ]),
        ]);

        $ventas = $cliente->ventas->map(function (Venta $v) {
            $cuotas = $v->gestionesCobro;
            $hoy = now()->startOfDay();

            return [
                'id' => $v->id,
                'numero_venta' => $v->numero_venta,
                'fecha_venta' => $v->fecha_venta?->format('d/m/Y'),
                'tipo_pago' => $v->tipo_pago,
                'estado' => $v->estado,
                'total' => (float) $v->total,
                'monto_pagado' => (float) $v->monto_pagado,
                'saldo_pendiente' => (float) $v->saldo_pendiente,
                'observaciones' => $v->observaciones,
                // Varios pagos de una misma visita quedan repartidos entre cuotas (uno por
                // cuota que se llenó ese día), así que se agrupan por fecha para que se vean
                // como un solo monto y se puedan editar/corregir como una unidad.
                'pagos' => $v->pagos
                    ->groupBy(fn ($p) => $p->fecha_pago?->toDateString())
                    ->map(function ($grupo, $fechaIso) {
                        $primero = $grupo->first();

                        return [
                            'fecha' => $primero->fecha_pago?->format('d/m/Y'),
                            'fecha_iso' => $fechaIso,
                            'monto' => round((float) $grupo->sum('monto'), 2),
                            'metodo_pago' => $grupo->pluck('metodo_pago')->unique()->count() === 1
                                ? $primero->metodo_pago
                                : 'mixto',
                            'observaciones' => $primero->observaciones,
                            'cantidad' => $grupo->count(),
                        ];
                    })
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

        return response()->json([
            'cliente' => [
                'id' => $cliente->id,
                'codigo_anterior' => $cliente->codigo_anterior,
                'nombre' => $cliente->nombre_completo,
                'dui' => $cliente->dui,
                'telefono' => $cliente->telefono_normal,
                'whatsapp' => $cliente->telefono_whatsapp,
                'direccion' => $cliente->direccion,
                'saldo' => (float) $cliente->saldo,
                'ruta_nombre' => $cliente->rutaCobro?->nombre,
                'ruta_dia' => $cliente->rutaCobro?->dia_semana,
                'activo' => (bool) $cliente->activo,
            ],
            'ventas' => $ventas,
            'resumen' => [
                'total_ventas' => $ventas->count(),
                'total_comprado' => round($ventas->sum('total'), 2),
                'total_pagado' => round($ventas->sum('monto_pagado'), 2),
                'total_pendiente' => round($ventas->sum('saldo_pendiente'), 2),
            ],
        ]);
    }

    public function reordenar(Request $request, $tenant): JsonResponse
    {
        $data = $request->validate([
            'orden' => 'required|array|min:1',
            'orden.*' => 'required|integer|exists:clientes,id',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['orden'] as $posicion => $clienteId) {
                Cliente::where('id', $clienteId)->update(['orden' => $posicion + 1]);
            }
        });

        return response()->json(['mensaje' => 'Orden actualizado.']);
    }

    public function cambiarRuta(Request $request, $tenant, Cliente $cliente): JsonResponse
    {
        $data = $request->validate([
            'ruta_cobro_id' => 'nullable|integer|exists:rutas_cobro,id',
        ]);

        $cliente->update([
            'ruta_cobro_id' => $data['ruta_cobro_id'] ?? null,
            'orden' => null,
        ]);

        return response()->json(['mensaje' => 'Cliente actualizado.']);
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

        $primerPagoId = $venta->pagos()->oldest('fecha_pago')->value('id');
        $otrosPagos = (float) $venta->pagos()
            ->when($primerPagoId, fn ($q) => $q->where('id', '!=', $primerPagoId))
            ->sum('monto');

        if ($data['monto'] + $otrosPagos > (float) $venta->total) {
            return response()->json(['mensaje' => 'El abono supera el total de la venta ($'.number_format($venta->total, 2).').'], 422);
        }

        DB::transaction(function () use ($venta, $data) {
            $pagoInicial = $venta->pagos()->oldest('fecha_pago')->first();

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
            $totalPagado = round((float) $venta->prima + (float) $venta->pagos()->sum('monto'), 2);
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
            'monto' => 'required|numeric|min:0',
        ]);

        $venta = $cliente->ventas()->where('id', $data['venta_id'])->first();

        if (! $venta) {
            return response()->json(['mensaje' => 'Esta venta no pertenece a este cliente.'], 422);
        }

        $pagosDelDia = $venta->pagos()->whereDate('fecha_pago', $data['fecha_pago'])->orderBy('id')->get();

        if ($pagosDelDia->isEmpty()) {
            return response()->json(['mensaje' => 'No hay pagos registrados en esa fecha para esta venta.'], 422);
        }

        $otrosPagos = (float) $venta->pagos()->whereDate('fecha_pago', '!=', $data['fecha_pago'])->sum('monto');

        if ($data['monto'] + $otrosPagos + (float) $venta->prima > (float) $venta->total) {
            return response()->json(['mensaje' => 'El monto supera el total de la venta ($'.number_format($venta->total, 2).').'], 422);
        }

        DB::transaction(function () use ($venta, $pagosDelDia, $data) {
            $principal = $pagosDelDia->first();
            $pagosDelDia->skip(1)->each(fn (PagoVenta $p) => $p->delete());
            $principal->update(['monto' => $data['monto']]);

            $venta->refresh();
            $totalPagado = round((float) $venta->prima + (float) $venta->pagos()->sum('monto'), 2);
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

        $totalPagado = round((float) $venta->prima + (float) $venta->pagos()->sum('monto'), 2);
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
                    $data['producto'] ?: 'Producto', null,
                    $data['codigo_anterior'] ?? '—', $productoCache,
                );
                $ventaInfo = $resultadoVenta['venta'];
            }

            return ['cliente' => $cliente, 'venta' => $ventaInfo];
        });

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
