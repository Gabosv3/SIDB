<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Cobrador;
use App\Models\DetalleVenta;
use App\Models\GestionCobro;
use App\Models\Producto;
use App\Models\RutaCobro;
use App\Models\Venta;
use App\Models\Vendedor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClienteImportService
{
    // ── Mapeo de columnas: nombre original → nombre interno ───────────────────
    // Acepta el formato original del Excel del cobrador Y el formato nuevo
    private const MAPA_COLUMNAS = [
        // Formato original (Excel del cobrador)
        'ID CLIENTE'         => 'codigo_anterior',
        'ID CLIEN.'          => 'codigo_anterior',
        'ID_CLIENTE'         => 'codigo_anterior',
        'FECHA'              => 'fecha_venta',
        'CLIENTES'           => 'nombre_completo',
        'NOMBRE CONYUGE'     => 'estado_civil',    // En el Excel es "soltera/soltero"
        'NOMBRE CONYUG.'     => 'estado_civil',
        'TIPO DE PRODUCTO'   => 'producto',
        'TIPO_PRODUCTO'      => 'producto',
        'CUOTAS'             => 'cuota_quincenal',   // encabezado real del CSV de Nelson
        'CUOTAS2'            => 'cuota_mensual',    // encabezado real del CSV de Nelson
        'CUOTAS QUINCENAL'   => 'cuota_quincenal',
        'CUOTAS2 MENSUAL'    => 'cuota_mensual',
        'CUOTAS MENSUAL'     => 'cuota_mensual',
        'VALOR'              => 'monto_total',
        'MONTO COBRADO'      => 'monto_cobrado',
        'SALDO PENDIENTE'    => 'saldo_pendiente',
        'TELEFONO'           => 'telefono',
        'DPTO.'              => 'departamento',
        'DPTO'               => 'departamento',

        // Formato nuevo (plantilla del sistema)
        'CODIGO_ANTERIOR'    => 'codigo_anterior',
        'CODIGO_ACTUAL'      => '_ignorar',
        'FECHA_VENTA'        => 'fecha_venta',
        'NOMBRE'             => 'nombre',
        'APELLIDO'           => 'apellido',
        'NOMBRE_CONYUGE'     => 'nombre_conyuge',
        'TIPO_DE_PRODUCTO'   => 'producto',
        'PRODUCTO'           => 'producto',
        'CUOTAS_QUINCENAL'   => 'cuota_quincenal',
        'CUOTAS_MENSUAL'     => 'cuota_mensual',
        'MONTO_TOTAL'        => 'monto_total',
        'MONTO_CUOTA'        => 'cuota_quincenal',
        'FRECUENCIA_CUOTA'   => 'frecuencia',
        'MONTO_COBRADO'      => 'monto_cobrado',
        'SALDO_PENDIENTE'    => 'saldo_pendiente',
        'TELEFONO_WHATSAPP'  => 'telefono_whatsapp',
        'DEPARTAMENTO'       => 'departamento',

        // Comunes en ambos
        'DUI'                => 'dui',
        'DIRECCION'          => 'direccion',
        'COBRADOR'           => 'cobrador',
        'VENDEDOR'           => 'vendedor',
        'ZONA'               => 'zona',
        'RUTA'               => 'ruta',
    ];

    private int  $sucursalId;
    private bool $omitirDuplicados;

    private array $cacheCobradores = [];
    private array $cacheVendedores = [];
    private array $cacheRutas      = [];
    private array $cacheProductos  = [];

    private array $resultado = [
        'creados'  => 0,
        'omitidos' => 0,
        'errores'  => 0,
        'filas'    => [],
    ];

    // ── Punto de entrada ──────────────────────────────────────────────────────

    public function importar(string $rutaArchivo, int $sucursalId, bool $omitirDuplicados = true): array
    {
        $this->sucursalId       = $sucursalId;
        $this->omitirDuplicados = $omitirDuplicados;

        $filas = $this->parsearCsv($rutaArchivo);

        foreach ($filas as $indice => $fila) {
            $this->procesarFila($indice + 2, $fila);
        }

        return $this->resultado;
    }

    // ── Parseo del CSV ────────────────────────────────────────────────────────

    private function parsearCsv(string $ruta): array
    {
        if (! file_exists($ruta)) {
            throw new \RuntimeException("Archivo no encontrado: {$ruta}");
        }

        $contenido = file_get_contents($ruta);

        // Convertir encoding si es necesario
        $encoding = mb_detect_encoding($contenido, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', $encoding);
        }

        // Quitar BOM
        $contenido = ltrim($contenido, "\xEF\xBB\xBF");

        $lineas = preg_split('/\r\n|\r|\n/', trim($contenido));
        if (empty($lineas)) {
            throw new \RuntimeException('El archivo CSV está vacío.');
        }

        // Detectar separador
        $separador = str_contains($lineas[0], ';') ? ';' : ',';

        // Leer la primera línea como cabecera
        $cabecera = str_getcsv(array_shift($lineas), $separador);
        $cabecera = array_map(fn($c) => strtoupper(trim($c)), $cabecera);

        // Si la segunda línea es también cabecera (sub-encabezados como QUINCENAL/MENSUAL), saltarla
        if (! empty($lineas)) {
            $primera = str_getcsv($lineas[0], $separador);
            $esSubCabecera = collect($primera)->filter(fn($v) => trim($v) !== '')->every(
                fn($v) => ! is_numeric(preg_replace('/[$,.\s]/', '', trim($v)))
                       && ! preg_match('/\d{2}\/\d{2}\/\d{4}/', trim($v))
            );
            if ($esSubCabecera) {
                array_shift($lineas);
            }
        }

        // Normalizar cabecera usando el mapa
        $cabeceraInterna = array_map(fn($col) => self::MAPA_COLUMNAS[$col] ?? strtolower(str_replace(' ', '_', $col)), $cabecera);

        $filas = [];
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '') continue;

            $valores = str_getcsv($linea, $separador);
            while (count($valores) < count($cabeceraInterna)) {
                $valores[] = '';
            }

            $fila = array_combine($cabeceraInterna, $valores);
            $filas[] = array_map('trim', $fila);
        }

        return $filas;
    }

    // ── Procesamiento por fila ────────────────────────────────────────────────

    private function procesarFila(int $numeroFila, array $fila): void
    {
        try {
            $codigoAnterior = trim($fila['codigo_anterior'] ?? '');

            // Saltar filas vacías o de totales (sin ID y sin nombre de cliente)
            $nombreRaw = trim($fila['nombre_completo'] ?? $fila['nombre'] ?? '');
            if ($codigoAnterior === '' && $nombreRaw === '') {
                return;
            }

            // Verificar duplicado
            if ($this->omitirDuplicados && $codigoAnterior !== '') {
                $existe = Cliente::where('codigo_anterior', $codigoAnterior)
                    ->where('sucursal_id', $this->sucursalId)
                    ->exists();

                if ($existe) {
                    $this->resultado['omitidos']++;
                    $this->resultado['filas'][] = [
                        'fila'     => $numeroFila,
                        'estado'   => 'omitido',
                        'nombre'   => $this->resolverNombre($fila)['completo'],
                        'motivo'   => "Código {$codigoAnterior} ya existe.",
                        'anterior' => $codigoAnterior,
                    ];
                    return;
                }
            }

            DB::transaction(function () use ($fila, $codigoAnterior, $numeroFila) {

                // ── Nombre: detectar si viene junto o separado ─────────────────
                $nombreData    = $this->resolverNombre($fila);
                $cobrador      = $this->resolverCobrador($fila);
                $vendedor      = $this->resolverVendedor($fila);
                $ruta          = $this->resolverRuta($fila, $cobrador);
                $producto      = $this->resolverProducto($fila);

                // ── Montos ─────────────────────────────────────────────────────
                $montoTotal     = $this->limpiarMonto($fila['monto_total']     ?? 0);
                $montoCobrado   = $this->limpiarMonto($fila['monto_cobrado']   ?? 0);
                $saldoPendiente = $this->limpiarMonto($fila['saldo_pendiente'] ?? $montoTotal);

                // Cuota y frecuencia: puede venir como QUINCENAL o MENSUAL
                [$montoCuota, $frecuencia] = $this->resolverCuota($fila);

                if ($montoTotal <= 0) {
                    throw new \RuntimeException('VALOR/MONTO_TOTAL inválido o cero.');
                }
                if ($montoCuota <= 0) {
                    throw new \RuntimeException('CUOTA inválida o cero.');
                }

                $fechaVenta = $this->parsearFecha($fila['fecha_venta'] ?? '');

                // ── CLIENTE ────────────────────────────────────────────────────
                $cliente = Cliente::create([
                    'sucursal_id'       => $this->sucursalId,
                    'codigo_anterior'   => $codigoAnterior ?: null,
                    'nombre'            => $nombreData['nombre'],
                    'apellido'          => $nombreData['apellido'],
                    'dui'               => $this->limpiarDui($fila['dui'] ?? ''),
                    'direccion'         => $fila['direccion'] ?? '',
                    'nombre_conyuge'    => $this->resolverConyuge($fila),
                    'telefono_normal'   => $this->limpiarTelefono($fila['telefono'] ?? ''),
                    'telefono_whatsapp' => $this->limpiarTelefono($fila['telefono_whatsapp'] ?? $fila['telefono'] ?? ''),
                    'departamento'      => $fila['departamento'] ?? '',
                    'municipio'         => $fila['zona'] ?? '',
                    'distrito'          => '',
                    'latitud'           => 13.6929,
                    'longitud'          => -89.2182,
                    'ruta_cobro_id'     => $ruta?->id,
                    'saldo'             => $saldoPendiente,
                    'limite_credito'    => $montoTotal,
                    'activo'            => true,
                ]);

                // ── VENTA ──────────────────────────────────────────────────────
                $venta = Venta::create([
                    'sucursal_id'          => $this->sucursalId,
                    'cliente_id'           => $cliente->id,
                    'vendedor_id'          => $vendedor?->id,
                    'user_id'              => auth()->id() ?? 1,
                    'fecha_venta'          => $fechaVenta,
                    'estado'               => $saldoPendiente > 0 ? 'pendiente' : 'completada',
                    'tipo_pago'            => 'credito',
                    'subtotal'             => $montoTotal,
                    'descuento_porcentaje' => 0,
                    'descuento_monto'      => 0,
                    'impuesto_porcentaje'  => 0,
                    'impuesto_monto'       => 0,
                    'total'                => $montoTotal,
                    'monto_pagado'         => $montoCobrado,
                    'saldo_pendiente'      => $saldoPendiente,
                    'observaciones'        => $codigoAnterior
                        ? "Importado. Código anterior: {$codigoAnterior}"
                        : 'Importado desde CSV',
                ]);

                // ── DETALLE VENTA ──────────────────────────────────────────────
                // Regla fija: 20 cuotas principales + 4 extra buffer = 24 total
                // monto_cuota = total / 20 (las 4 extra tienen el mismo valor)
                $cuotasFijas    = 20;
                $cuotasExtra    = 4;
                $totalCuotas    = $cuotasFijas + $cuotasExtra;   // 24
                $montoCuotaReal = round($montoTotal / $cuotasFijas, 2);

                if ($producto) {
                    DetalleVenta::withoutEvents(function () use ($venta, $producto, $montoTotal, $montoCuotaReal, $totalCuotas) {
                        DetalleVenta::create([
                            'venta_id'             => $venta->id,
                            'producto_id'          => $producto->id,
                            'cantidad'             => 1,
                            'precio_unitario'      => $montoTotal,
                            'descuento_porcentaje' => 0,
                            'subtotal'             => $montoTotal,
                            'cuotas'               => $totalCuotas,
                            'precio_cuota'         => $montoCuotaReal,
                        ]);
                    });
                }

                // ── CUOTAS PENDIENTES ──────────────────────────────────────────
                $this->generarCuotas(
                    venta:          $venta,
                    cliente:        $cliente,
                    montoTotal:     $montoTotal,
                    montoCuota:     $montoCuotaReal,
                    montoCobrado:   $montoCobrado,
                    saldoPendiente: $saldoPendiente,
                    fechaVenta:     $fechaVenta,
                );

                $this->resultado['creados']++;
                $this->resultado['filas'][] = [
                    'fila'     => $numeroFila,
                    'estado'   => 'creado',
                    'nombre'   => $cliente->nombre_completo,
                    'codigo'   => $cliente->id,
                    'anterior' => $codigoAnterior,
                    'motivo'   => '',
                ];
            });

        } catch (\Throwable $e) {
            $this->resultado['errores']++;
            $this->resultado['filas'][] = [
                'fila'   => $numeroFila,
                'estado' => 'error',
                'nombre' => $this->resolverNombre($fila)['completo'],
                'motivo' => $e->getMessage(),
            ];
        }
    }

    // ── Generación de cuotas pendientes ──────────────────────────────────────

    private function generarCuotas(
        Venta   $venta,
        Cliente $cliente,
        float   $montoTotal,
        float   $montoCuota,     // = montoTotal / 20 (ya calculado antes de llamar)
        float   $montoCobrado,
        float   $saldoPendiente,
        Carbon  $fechaVenta,
    ): void {
        if ($montoCuota <= 0 || $saldoPendiente <= 0) return;

        // ── Regla fija: 20 cuotas principales + 4 extra buffer ───────────────
        // Intervalo siempre 14 días (quincenal)
        $cuotasMain  = 20;
        $totalCuotas = 24;   // 20 principales + 4 extra
        $diasIntervalo = 14;

        // Cuántas ya están pagadas (completas)
        $cuotasPagadas = $montoCuota > 0
            ? (int) floor($montoCobrado / $montoCuota)
            : 0;

        // Cuota parcial: si hay residuo, la primera pendiente ya tiene algo abonado
        $restoParcial = round($montoCobrado - ($cuotasPagadas * $montoCuota), 2);

        // Fecha base: la primera cuota vence 14 días después de la venta
        $fechaBase = (clone $fechaVenta)->addDays($diasIntervalo);

        for ($numeroCuota = 1; $numeroCuota <= $totalCuotas; $numeroCuota++) {
            $fechaVencimiento = (clone $fechaBase)->addDays($diasIntervalo * ($numeroCuota - 1));
            $observacion      = $numeroCuota > $cuotasMain ? 'Cuota extra' : null;

            if ($numeroCuota <= $cuotasPagadas) {
                // Cuota ya cobrada
                GestionCobro::create([
                    'venta_id'          => $venta->id,
                    'cliente_id'        => $cliente->id,
                    'numero_cuota'      => $numeroCuota,
                    'total_cuotas'      => $totalCuotas,
                    'monto_cuota'       => $montoCuota,
                    'monto_pagado'      => $montoCuota,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'estado'            => 'cobrado',
                    'observaciones'     => $observacion,
                ]);
            } elseif ($numeroCuota === $cuotasPagadas + 1 && $restoParcial > 0) {
                // Primera pendiente con pago parcial
                GestionCobro::create([
                    'venta_id'          => $venta->id,
                    'cliente_id'        => $cliente->id,
                    'numero_cuota'      => $numeroCuota,
                    'total_cuotas'      => $totalCuotas,
                    'monto_cuota'       => $montoCuota,
                    'monto_pagado'      => $restoParcial,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'estado'            => 'parcialmente_cobrado',
                    'observaciones'     => $observacion,
                ]);
            } else {
                // Cuota pendiente normal
                GestionCobro::create([
                    'venta_id'          => $venta->id,
                    'cliente_id'        => $cliente->id,
                    'numero_cuota'      => $numeroCuota,
                    'total_cuotas'      => $totalCuotas,
                    'monto_cuota'       => $montoCuota,
                    'monto_pagado'      => 0,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'estado'            => 'pendiente',
                    'observaciones'     => $observacion,
                ]);
            }
        }
    }

    // ── Resolución de nombre ──────────────────────────────────────────────────

    /**
     * El Excel tiene el nombre completo en una sola celda.
     * Estrategia: último apellido conocido = últimas 1-2 palabras, nombre = el resto.
     * Si ya viene separado (NOMBRE + APELLIDO) usa eso directamente.
     */
    private function resolverNombre(array $fila): array
    {
        // Formato nuevo: viene separado
        if (! empty($fila['nombre']) && isset($fila['apellido'])) {
            $nombre   = ucwords(strtolower(trim($fila['nombre'])));
            $apellido = ucwords(strtolower(trim($fila['apellido'])));
            return [
                'nombre'   => $nombre,
                'apellido' => $apellido,
                'completo' => trim("$nombre $apellido"),
            ];
        }

        // Formato original: nombre completo junto
        $completo = trim($fila['nombre_completo'] ?? $fila['nombre'] ?? 'Sin nombre');
        $completo = ucwords(strtolower($completo));
        $palabras = explode(' ', $completo);

        if (count($palabras) === 1) {
            return ['nombre' => $palabras[0], 'apellido' => '', 'completo' => $palabras[0]];
        }

        // Detectar partículas de apellido compuesto (de, del, de la, etc.)
        $particulas = ['de', 'del', 'de la', 'de los', 'las', 'los'];

        // Tomar las últimas 1 o 2 palabras como apellido
        // Si la penúltima es una partícula, tomar 3 palabras como apellido
        $totalPalabras = count($palabras);
        $cantApellido  = 1;

        if ($totalPalabras >= 3) {
            $penultima = strtolower($palabras[$totalPalabras - 2]);
            if (in_array($penultima, $particulas)) {
                $cantApellido = 2; // ej: "Hernandez de Garcia"
            } else {
                $cantApellido = 1; // ej: "Maria Carmen Hernandez" → apellido = "Hernandez"
            }
        }

        // Para nombres largos como "Maria del Carmen Hernandez" → nombre = "Maria del Carmen"
        $apellidoParts = array_slice($palabras, -$cantApellido);
        $nombreParts   = array_slice($palabras, 0, $totalPalabras - $cantApellido);

        return [
            'nombre'   => implode(' ', $nombreParts),
            'apellido' => implode(' ', $apellidoParts),
            'completo' => $completo,
        ];
    }

    // ── Conyuge / estado civil ────────────────────────────────────────────────

    private function resolverConyuge(array $fila): ?string
    {
        $valor = trim($fila['nombre_conyuge'] ?? $fila['estado_civil'] ?? '');
        if (empty($valor)) return null;

        // Si es estado civil, ignorar
        $estadosCiviles = ['soltera', 'soltero', 'casada', 'casado', 'divorciada', 'divorciado', 'viuda', 'viudo'];
        if (in_array(strtolower($valor), $estadosCiviles)) {
            return null;
        }

        return ucwords(strtolower($valor));
    }

    // ── Cuota y frecuencia ────────────────────────────────────────────────────

    private function resolverCuota(array $fila): array
    {
        // Si ya viene la frecuencia explícita
        if (! empty($fila['frecuencia'])) {
            $monto = $this->limpiarMonto($fila['cuota_quincenal'] ?? $fila['cuota_mensual'] ?? 0);
            return [$monto, $fila['frecuencia']];
        }

        $quincenal = $this->limpiarMonto($fila['cuota_quincenal'] ?? 0);
        $mensual   = $this->limpiarMonto($fila['cuota_mensual']   ?? 0);

        if ($quincenal > 0) {
            return [$quincenal, 'quincenal'];
        }

        if ($mensual > 0) {
            return [$mensual, 'mensual'];
        }

        return [0, 'quincenal'];
    }

    // ── Entidades relacionadas ────────────────────────────────────────────────

    private function resolverCobrador(array $fila): ?Cobrador
    {
        $nombre = trim($fila['cobrador'] ?? '');
        if (empty($nombre)) return null;

        $clave = strtolower($nombre);
        if (isset($this->cacheCobradores[$clave])) return $this->cacheCobradores[$clave];

        $cobrador = Cobrador::where('sucursal_id', $this->sucursalId)
            ->whereRaw('LOWER(nombre) = ?', [$clave])
            ->first()
            ?? Cobrador::create([
                'sucursal_id' => $this->sucursalId,
                'nombre'      => ucfirst(strtolower($nombre)),
                'apellido'    => '',
                'activo'      => true,
            ]);

        return $this->cacheCobradores[$clave] = $cobrador;
    }

    private function resolverVendedor(array $fila): ?Vendedor
    {
        $nombre = trim($fila['vendedor'] ?? '');
        if (empty($nombre)) return null;

        $clave = strtolower($nombre);
        if (isset($this->cacheVendedores[$clave])) return $this->cacheVendedores[$clave];

        $vendedor = Vendedor::where('sucursal_id', $this->sucursalId)
            ->whereRaw('LOWER(nombre) = ?', [$clave])
            ->first()
            ?? Vendedor::create([
                'sucursal_id' => $this->sucursalId,
                'nombre'      => ucfirst(strtolower($nombre)),
                'apellido'    => '',
                'activo'      => true,
            ]);

        return $this->cacheVendedores[$clave] = $vendedor;
    }

    private function resolverRuta(array $fila, ?Cobrador $cobrador): ?RutaCobro
    {
        $nombreRuta = trim($fila['ruta'] ?? '');
        if (empty($nombreRuta)) return null;

        $clave = strtolower($nombreRuta) . '_' . ($cobrador?->id ?? 'x');
        if (isset($this->cacheRutas[$clave])) return $this->cacheRutas[$clave];

        $ruta = RutaCobro::where('sucursal_id', $this->sucursalId)
            ->whereRaw('LOWER(nombre) = ?', [strtolower($nombreRuta)])
            ->when($cobrador, fn($q) => $q->where('cobrador_id', $cobrador->id))
            ->first()
            ?? RutaCobro::create([
                'sucursal_id' => $this->sucursalId,
                'cobrador_id' => $cobrador?->id,
                'nombre'      => $nombreRuta,
                'dia_semana'  => $this->extraerDia($nombreRuta),
                'activa'      => true,
            ]);

        return $this->cacheRutas[$clave] = $ruta;
    }

    private function resolverProducto(array $fila): ?Producto
    {
        $nombre = trim($fila['producto'] ?? '');
        if (empty($nombre)) return null;

        $clave = strtolower($nombre);
        if (isset($this->cacheProductos[$clave])) return $this->cacheProductos[$clave];

        $producto = Producto::where('sucursal_id', $this->sucursalId)
            ->whereRaw('LOWER(nombre) = ?', [$clave])
            ->first()
            ?? Producto::create([
                'sucursal_id'  => $this->sucursalId,
                'nombre'       => $nombre,
                'codigo'       => 'IMP-' . strtoupper(Str::random(5)),
                'precio_venta' => $this->limpiarMonto($fila['monto_total'] ?? 0),
                'precio_compra'=> 0,
                'stock'        => 0,
                'stock_minimo' => 0,
                'activo'       => true,
                'unidad_medida'=> 'unidad',
            ]);

        return $this->cacheProductos[$clave] = $producto;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function limpiarMonto(mixed $valor): float
    {
        if (is_numeric($valor)) return (float) $valor;
        // Quitar: $, espacios, comas de miles
        $s = str_replace([' ', ','], ['', ''], (string) $valor);
        $s = preg_replace('/[^\d.]/', '', $s);
        return (float) $s;
    }

    private function limpiarDui(?string $dui): ?string
    {
        return empty(trim($dui ?? '')) ? null : trim($dui);
    }

    private function limpiarTelefono(?string $tel): ?string
    {
        if (empty($tel)) return null;
        $limpio = preg_replace('/[^\d\-+]/', '', trim($tel));
        return $limpio ?: null;
    }

    private function parsearFecha(string $fecha): Carbon
    {
        if (empty($fecha)) return Carbon::now();

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'] as $formato) {
            try {
                return Carbon::createFromFormat($formato, trim($fecha));
            } catch (\Throwable) {
                continue;
            }
        }

        return Carbon::now();
    }

    private function extraerDia(string $nombreRuta): ?string
    {
        $ruta = strtolower($nombreRuta);
        $dias = ['lunes', 'martes', 'miercoles', 'miércoles', 'jueves', 'viernes', 'sabado', 'sábado', 'domingo'];
        foreach ($dias as $dia) {
            if (str_contains($ruta, $dia)) return $dia;
        }
        return null;
    }
}
