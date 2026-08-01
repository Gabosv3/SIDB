<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Cobrador;
use App\Models\ConfiguracionSistema;
use App\Models\GestionCobro;
use App\Models\PagoVenta;
use App\Models\Reintegro;
use App\Models\RutaCobro;
use App\Models\Vale;
use App\Models\Venta;
use App\Models\VisitaCobro;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ResumenCobrosDiaService
{
    /**
     * Un cobrador puede cubrir más de una ruta el mismo día (día compartido entre
     * 2 rutas, o cobro extra fuera de su ruta habitual). Para que cada ruta se vea
     * como una entrada aparte en vez de sumarse en un solo total del cobrador, se
     * genera una entrada por cada combinación (cobrador, ruta) que tuvo actividad
     * o tenía ruta programada ese día.
     *
     * @param  int[]  $cobradorIds  Vacío = todos los cobradores.
     */
    public static function resumen(string $fecha, array $cobradorIds = []): array
    {
        $fechaCarbon = Carbon::parse($fecha);
        $diasEs = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
        $diaFecha = $diasEs[$fechaCarbon->dayOfWeekIso - 1];
        $semanaFecha = ConfiguracionSistema::instance()->semanaParaFecha($fechaCarbon);

        $cobradores = Cobrador::with('user')
            ->where('activo', true)
            ->where('excluir_reportes', false)
            ->when(! empty($cobradorIds), fn ($q) => $q->whereIn('id', $cobradorIds))
            ->get();

        $resumen = collect();

        foreach ($cobradores as $cobrador) {
            // Incluye pagos anulados: deben seguir apareciendo en el detalle (marcados
            // como anulados), solo se excluyen de los totales/montos más abajo.
            $pagosTodos = PagoVenta::where('user_id', $cobrador->user_id)
                ->whereDate('fecha_pago', $fechaCarbon)
                ->with('cliente:id,nombre,apellido,codigo_anterior,ruta_cobro_id', 'venta:id,numero_venta,saldo_pendiente')
                ->orderBy('created_at')
                ->get();

            $visitasTodas = VisitaCobro::where('user_id', $cobrador->user_id)
                ->whereDate('created_at', $fechaCarbon)
                ->with('cliente:id,nombre,apellido,codigo_anterior,ruta_cobro_id')
                ->orderBy('created_at')
                ->get();

            // Ventas canceladas/devueltas ese día — se cuentan por venta.user_id (el
            // cobrador que la registró), NO por la ruta actual del cliente: al
            // cancelarse, si era su única cuenta activa el cliente sale de la ruta
            // (Venta::boot) y ruta_cobro_id queda en null, perdiendo el vínculo con
            // la ruta. user_id nunca cambia, así que es el único dato confiable para
            // saber que esa cancelación fue de este cobrador.
            $ventasCanceladasCobrador = Venta::where('user_id', $cobrador->user_id)
                ->whereIn('estado', ['cancelada', 'devuelta'])
                ->whereDate('updated_at', $fechaCarbon)
                ->count();

            $rutasHoyIds = $cobrador->rutasCobro()
                ->where('dia_semana', $diaFecha)
                ->when($semanaFecha !== null, fn ($q) => $q->where(
                    fn ($q2) => $q2->whereNull('semana_ciclo')->orWhere('semana_ciclo', $semanaFecha)
                ))
                ->pluck('id');

            // La ruta de un pago es la que quedó congelada en pago_ventas.ruta_cobro_id
            // al momento del cobro (con fallback a la ruta actual del cliente para
            // filas antiguas sin ese dato) — NUNCA la ruta actual del cliente en vivo.
            // Si se usara la ruta actual, un cliente que sale de su ruta el mismo día
            // (ej. reintegro justo después de cobrarle) haría que el pago no calce con
            // ninguna ruta y desaparezca del reporte, dejando un hueco para ocultar cobros.
            $rutaIdsConActividad = $pagosTodos->map(fn ($p) => $p->ruta_cobro_id ?? $p->cliente?->ruta_cobro_id)
                ->merge($visitasTodas->pluck('cliente.ruta_cobro_id'))
                ->merge($rutasHoyIds)
                ->filter()
                ->unique()
                ->values();

            if ($rutaIdsConActividad->isEmpty()) {
                continue;
            }

            $rutas = RutaCobro::whereIn('id', $rutaIdsConActividad)->get()->keyBy('id');

            $clientesAtendidosTodos = $pagosTodos->pluck('cliente_id')
                ->merge($visitasTodas->pluck('cliente_id'))
                ->unique();

            foreach ($rutaIdsConActividad as $rutaId) {
                $pagos = $pagosTodos->filter(fn ($p) => ($p->ruta_cobro_id ?? $p->cliente?->ruta_cobro_id) === $rutaId)->values();
                $visitas = $visitasTodas->filter(fn ($v) => $v->cliente?->ruta_cobro_id === $rutaId)->values();

                // Los pagos anulados se muestran en "detalle" (marcados como anulados)
                // pero no cuentan en ningún total/monto/gráfica.
                $pagosValidos = $pagos->whereNull('anulado_en')->values();

                $porMetodo = $pagosValidos->groupBy('metodo_pago')->map(fn ($grupo, $metodo) => (object) [
                    'metodo_pago' => $metodo,
                    'cantidad' => $grupo->count(),
                    'monto' => (float) $grupo->sum('monto'),
                ]);

                // Clientes NO visitados: en ESTA ruta, con cuotas pendientes, sin
                // pago ni visita registrada por este cobrador en la fecha seleccionada.
                $noVisitados = Cliente::where('ruta_cobro_id', $rutaId)
                    ->whereNotIn('id', $clientesAtendidosTodos)
                    ->whereHas('gestionesCobro', fn ($q) => $q->whereIn('estado', ['pendiente', 'parcialmente_cobrado']))
                    ->select('id', 'nombre', 'apellido', 'telefono_normal', 'codigo_anterior')
                    ->orderBy('nombre')
                    ->get();

                // "Llevó en la ruta al inicio" — clientes activos de ESTA ruta a día de
                // hoy. No es un snapshot histórico (no existe ese dato en el sistema);
                // para la fecha de hoy es exacto, para fechas pasadas es la mejor
                // aproximación disponible (misma convención que $noVisitados arriba).
                $clientesRutaInicio = Cliente::where('ruta_cobro_id', $rutaId)
                    ->where('activo', true)
                    ->count();

                // Reintegros SÍ guardan la ruta de origen (ruta_cobro_id_original), a
                // diferencia de las cancelaciones — por eso este conteo sí se puede
                // hacer de forma confiable por ruta.
                $reintegrosEnviados = Reintegro::where('ruta_cobro_id_original', $rutaId)
                    ->whereDate('fecha_asignacion', $fechaCarbon)
                    ->count();

                $resumen->push([
                    'cobrador' => $cobrador,
                    'ruta' => $rutas->get($rutaId),
                    'total_cobrado' => (float) $pagosValidos->sum('monto'),
                    'total_pagos' => $pagosValidos->pluck('cliente_id')->unique()->count(),
                    'clientes_visitados' => $pagosValidos->pluck('cliente_id')->unique()->count(),
                    'por_metodo' => $porMetodo,
                    'detalle' => $pagos,
                    'visitas_sin_cobro' => $visitas,
                    'no_visitados' => $noVisitados,
                    'clientes_ruta_inicio' => $clientesRutaInicio,
                    'ventas_canceladas' => $ventasCanceladasCobrador,
                    'reintegros_enviados' => $reintegrosEnviados,
                ]);
            }
        }

        return $resumen
            ->filter(fn ($r) => $r['total_pagos'] > 0 || $r['detalle']->isNotEmpty() || $r['visitas_sin_cobro']->isNotEmpty() || $r['no_visitados']->isNotEmpty() || ! empty($cobradorIds))
            ->values()
            ->all();
    }

    public static function totales(array $resumen): array
    {
        $col = collect($resumen);

        return [
            'total_cobrado' => round($col->sum('total_cobrado'), 2),
            'total_pagos' => $col->sum('total_pagos'),
            'clientes_visitados' => $col->sum('clientes_visitados'),
            'total_sin_cobro' => $col->sum(fn ($r) => $r['visitas_sin_cobro']->count()),
            'total_no_visitados' => $col->sum(fn ($r) => $r['no_visitados']->count()),
        ];
    }

    /**
     * Totales rápidos por cobrador (user_id) para una fecha, sin detalle ni relaciones.
     * Usado para mostrar cifras precisas en la tabla de POS sin el costo de la consulta completa.
     */
    public static function ligero(string $fecha): Collection
    {
        return PagoVenta::whereDate('fecha_pago', Carbon::parse($fecha))
            ->whereNull('anulado_en')
            ->selectRaw('user_id, COUNT(*) AS pagos, COUNT(DISTINCT cliente_id) AS clientes, SUM(monto) AS cobrado')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');
    }

    /**
     * user_id de los cobradores que sí deben contar en reportes/estadísticas:
     * activos y sin la marca "Excluir de reportes" (perfiles administrativos
     * o de prueba). Si se pasan $cobradorIds, se limita además a esos.
     *
     * @param  int[]  $cobradorIds
     */
    private static function userIdsParaReportes(array $cobradorIds = []): array
    {
        return Cobrador::where('activo', true)
            ->where('excluir_reportes', false)
            ->when(! empty($cobradorIds), fn ($q) => $q->whereIn('id', $cobradorIds))
            ->pluck('user_id')
            ->all();
    }

    /** Igual que userIdsParaReportes() pero devuelve el id de Cobrador (para filtrar rutas_cobro.cobrador_id). */
    private static function cobradorIdsParaReportes(array $cobradorIds = []): array
    {
        return Cobrador::where('activo', true)
            ->where('excluir_reportes', false)
            ->when(! empty($cobradorIds), fn ($q) => $q->whereIn('id', $cobradorIds))
            ->pluck('id')
            ->all();
    }

    /**
     * Totales del negocio para una fecha, sin desglose por cobrador. Usado
     * para comparar "hoy vs ayer". Si se pasan $cobradorIds, se limita a esos
     * cobradores (mismos KPIs de arriba, pero para el subconjunto elegido).
     *
     * @param  int[]  $cobradorIds
     */
    public static function totalesSimples(string $fecha, array $cobradorIds = []): array
    {
        $fechaCarbon = Carbon::parse($fecha);
        $userIds = self::userIdsParaReportes($cobradorIds);

        $pagos = PagoVenta::whereDate('fecha_pago', $fechaCarbon)
            ->whereIn('user_id', $userIds)
            ->whereNull('anulado_en')
            ->selectRaw('SUM(monto) AS total_cobrado, COUNT(DISTINCT CONCAT(cliente_id, "-", venta_id)) AS total_pagos, COUNT(DISTINCT cliente_id) AS clientes_visitados')
            ->first();

        $totalSinCobro = VisitaCobro::whereDate('created_at', $fechaCarbon)
            ->whereIn('user_id', $userIds)
            ->count();

        $totalPendientes = self::contarPendientes($fechaCarbon, $cobradorIds);

        return [
            'total_cobrado' => (float) ($pagos->total_cobrado ?? 0),
            'total_pagos' => (int) ($pagos->total_pagos ?? 0),
            'clientes_visitados' => (int) ($pagos->clientes_visitados ?? 0),
            'total_sin_cobro' => $totalSinCobro,
            'total_pendientes' => $totalPendientes,
        ];
    }

    /**
     * Métricas generales del día: cobradores activos, meta diaria (monto esperado),
     * efectividad de cobro y promedio por pago. Si se pasan $cobradorIds, se limita
     * a esos cobradores (total_cobradores sigue siendo el total de la empresa).
     *
     * @param  int[]  $cobradorIds
     */
    public static function resumenGeneral(string $fecha, array $cobradorIds = []): array
    {
        $fechaCarbon = Carbon::parse($fecha);
        $userIds = self::userIdsParaReportes($cobradorIds);
        $cobradorIdsValidos = self::cobradorIdsParaReportes($cobradorIds);

        $totalCobradores = Cobrador::where('activo', true)->where('excluir_reportes', false)->count();

        $userIdsConPago = PagoVenta::whereDate('fecha_pago', $fechaCarbon)->whereIn('user_id', $userIds)->whereNull('anulado_en')->distinct()->pluck('user_id');
        $userIdsConVisita = VisitaCobro::whereDate('created_at', $fechaCarbon)->whereIn('user_id', $userIds)->distinct()->pluck('user_id');
        $cobradoresActivos = $userIdsConPago->merge($userIdsConVisita)->unique()->count();

        $totalEsperado = (float) GestionCobro::whereDate('fecha_vencimiento', $fechaCarbon)
            ->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
            ->whereHas('cliente.rutaCobro', fn ($q2) => $q2->whereIn('cobrador_id', $cobradorIdsValidos))
            ->sum('monto_cuota');

        $pagos = PagoVenta::whereDate('fecha_pago', $fechaCarbon)
            ->whereIn('user_id', $userIds)
            ->whereNull('anulado_en')
            ->selectRaw('SUM(monto) AS total_cobrado, COUNT(DISTINCT CONCAT(cliente_id, "-", venta_id)) AS total_pagos, COUNT(DISTINCT cliente_id) AS clientes_con_pago')
            ->first();

        $visitasSinCobro = VisitaCobro::whereDate('created_at', $fechaCarbon)
            ->whereIn('user_id', $userIds)
            ->count();
        $clientesConPago = (int) ($pagos->clientes_con_pago ?? 0);
        $totalVisitas = $clientesConPago + $visitasSinCobro;

        return [
            'total_cobradores' => $totalCobradores,
            'cobradores_activos' => $cobradoresActivos,
            'total_esperado' => $totalEsperado,
            'total_cobrado' => (float) ($pagos->total_cobrado ?? 0),
            'efectividad_cobro' => $totalVisitas > 0 ? round(($clientesConPago / $totalVisitas) * 100) : 0,
            'promedio_pago' => ($pagos->total_pagos ?? 0) > 0 ? round(((float) ($pagos->total_cobrado ?? 0)) / $pagos->total_pagos, 2) : 0,
        ];
    }

    /**
     * Total de vales (gastos) del día que descuentan del efectivo cobrado —
     * mismo criterio que valesPorCobrador(), pero como un solo número.
     *
     * @param  int[]  $cobradorIds
     */
    public static function totalValesDia(string $fecha, array $cobradorIds = []): float
    {
        return round((float) Vale::whereDate('fecha_gasto', Carbon::parse($fecha))
            ->where('descuenta_cobro_diario', true)
            ->whereIn('user_id', self::userIdsParaReportes($cobradorIds))
            ->sum('monto'), 2);
    }

    /**
     * Morosidad actual (no depende de la fecha filtrada — es el estado real de
     * la cartera hoy): cuotas vencidas y su monto pendiente. Si se pasan
     * $cobradorIds, se limita a los clientes de esas rutas.
     *
     * @param  int[]  $cobradorIds
     */
    public static function morosidad(array $cobradorIds = []): array
    {
        $query = GestionCobro::whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
            ->whereDate('fecha_vencimiento', '<', today())
            ->when(! empty($cobradorIds), fn ($q) => $q->whereHas('cliente.rutaCobro', fn ($q2) => $q2->whereIn('cobrador_id', $cobradorIds)));

        return [
            'cantidad' => (clone $query)->count(),
            'monto' => round((float) (clone $query)->selectRaw('SUM(monto_cuota - monto_pagado) AS total')->value('total'), 2),
        ];
    }

    /**
     * Total cobrado desde el lunes de la semana de $fecha hasta $fecha, contra
     * el mismo rango de la semana anterior (mismos días de la semana).
     *
     * @param  int[]  $cobradorIds
     */
    public static function comparativoSemanal(string $fecha, array $cobradorIds = []): array
    {
        $fechaCarbon = Carbon::parse($fecha);
        $userIds = self::userIdsParaReportes($cobradorIds);

        $inicioSemana = $fechaCarbon->copy()->startOfWeek(Carbon::MONDAY);
        $inicioSemanaPasada = $inicioSemana->copy()->subWeek();
        $finSemanaPasada = $fechaCarbon->copy()->subWeek();

        $totalEstaSemana = (float) PagoVenta::whereBetween('fecha_pago', [$inicioSemana->toDateString(), $fechaCarbon->toDateString()])
            ->whereIn('user_id', $userIds)
            ->whereNull('anulado_en')
            ->sum('monto');

        $totalSemanaPasada = (float) PagoVenta::whereBetween('fecha_pago', [$inicioSemanaPasada->toDateString(), $finSemanaPasada->toDateString()])
            ->whereIn('user_id', $userIds)
            ->whereNull('anulado_en')
            ->sum('monto');

        return [
            'esta_semana' => round($totalEstaSemana, 2),
            'semana_pasada' => round($totalSemanaPasada, 2),
            'delta' => $totalSemanaPasada > 0
                ? round((($totalEstaSemana - $totalSemanaPasada) / $totalSemanaPasada) * 100, 1)
                : ($totalEstaSemana > 0 ? 100.0 : 0.0),
        ];
    }

    /**
     * Total cobrado por día, para los últimos $dias días terminando en $fecha
     * (incluida). Rellena con 0 los días sin pagos, para una línea continua.
     *
     * @param  int[]  $cobradorIds
     */
    public static function tendenciaDiaria(string $fecha, int $dias = 7, array $cobradorIds = []): array
    {
        $fechaFin = Carbon::parse($fecha);
        $fechaInicio = $fechaFin->copy()->subDays($dias - 1);

        $porDia = PagoVenta::whereBetween('fecha_pago', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
            ->whereIn('user_id', self::userIdsParaReportes($cobradorIds))
            ->whereNull('anulado_en')
            ->selectRaw('fecha_pago, SUM(monto) AS total')
            ->groupBy('fecha_pago')
            ->pluck('total', 'fecha_pago');

        $resultado = [];
        for ($d = $fechaInicio->copy(); $d->lte($fechaFin); $d->addDay()) {
            $resultado[] = [
                'fecha' => $d->format('d/m'),
                'total' => round((float) ($porDia[$d->toDateString()] ?? 0), 2),
            ];
        }

        return $resultado;
    }

    /**
     * Total cobrado por cada cobrador en la fecha indicada, de mayor a menor
     * — para comparar entre cobradores. Si se pasan $cobradorIds, se limita
     * la comparación a esos (para que coincida con lo que se está filtrando).
     *
     * @param  int[]  $cobradorIds
     */
    public static function comparacionCobradores(string $fecha, array $cobradorIds = []): array
    {
        $fechaCarbon = Carbon::parse($fecha);

        $porUser = PagoVenta::whereDate('fecha_pago', $fechaCarbon)
            ->whereNull('anulado_en')
            ->selectRaw('user_id, SUM(monto) AS total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        return Cobrador::where('activo', true)
            ->where('excluir_reportes', false)
            ->when(! empty($cobradorIds), fn ($q) => $q->whereIn('id', $cobradorIds))
            ->get()
            ->map(fn (Cobrador $c) => [
                'nombre' => trim($c->nombre.' '.$c->apellido),
                'total' => round((float) ($porUser[$c->user_id] ?? 0), 2),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * Monto cobrado por método de pago en la fecha indicada.
     *
     * @param  int[]  $cobradorIds
     */
    public static function desglosePorMetodo(string $fecha, array $cobradorIds = []): array
    {
        $fechaCarbon = Carbon::parse($fecha);

        return PagoVenta::whereDate('fecha_pago', $fechaCarbon)
            ->whereIn('user_id', self::userIdsParaReportes($cobradorIds))
            ->whereNull('anulado_en')
            ->selectRaw('metodo_pago, SUM(monto) AS total')
            ->groupBy('metodo_pago')
            ->pluck('total', 'metodo_pago')
            ->map(fn ($v) => round((float) $v, 2))
            ->all();
    }

    /**
     * Vales (gastos) registrados por cada cobrador en la fecha indicada, para
     * poder cuadrar cuánto efectivo debe entregar (lo cobrado menos lo que ya
     * gastó de esa plata). Incluye vales en cualquier estado — el efectivo
     * salió de su mano ese día sin importar si administración lo aprueba
     * después; la aprobación solo afecta si se descuenta de su comisión
     * semanal (ver LiquidacionSemanal, que sí filtra por aprobado).
     *
     * Solo cuenta los vales con descuenta_cobro_diario=true: los gastos chicos
     * que el empleado ya pagó de lo cobrado ese día (imprevisto de calle,
     * gasolina, consumo — siempre true cuando vienen del móvil). Los gastos
     * grandes que el administrador registra directo en el sistema (reparación
     * mayor, pagada aparte por la empresa) quedan fuera de este cálculo.
     */
    public static function valesPorCobrador(string $fecha): Collection
    {
        $vales = Vale::whereDate('fecha_gasto', Carbon::parse($fecha))
            ->where('descuenta_cobro_diario', true)
            ->selectRaw("user_id,
                SUM(CASE WHEN tipo = 'consumo' THEN monto ELSE 0 END) AS total_consumo,
                SUM(CASE WHEN tipo = 'vehiculo' THEN monto ELSE 0 END) AS total_vehiculo,
                SUM(monto) AS total_gastado")
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        if ($vales->isEmpty()) {
            return collect();
        }

        return Cobrador::whereIn('user_id', $vales->keys())
            ->where('activo', true)
            ->where('excluir_reportes', false)
            ->get()
            ->map(fn (Cobrador $c) => [
                'cobrador'       => $c,
                'total_consumo'  => round((float) $vales[$c->user_id]->total_consumo, 2),
                'total_vehiculo' => round((float) $vales[$c->user_id]->total_vehiculo, 2),
                'total_gastado'  => round((float) $vales[$c->user_id]->total_gastado, 2),
            ])
            ->values();
    }

    /** @param int[] $cobradorIds */
    private static function contarPendientes(Carbon $fechaCarbon, array $cobradorIds = []): int
    {
        $diasEs = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
        $diaFecha = $diasEs[$fechaCarbon->dayOfWeekIso - 1];
        $semanaFecha = ConfiguracionSistema::instance()->semanaParaFecha($fechaCarbon);

        $rutasIds = RutaCobro::where('dia_semana', $diaFecha)
            ->whereIn('cobrador_id', self::cobradorIdsParaReportes($cobradorIds))
            ->when($semanaFecha !== null, fn ($q) => $q->where(
                fn ($q2) => $q2->whereNull('semana_ciclo')->orWhere('semana_ciclo', $semanaFecha)
            ))
            ->pluck('id');

        $clientesConPago = PagoVenta::whereDate('fecha_pago', $fechaCarbon)->whereNull('anulado_en')->pluck('cliente_id');
        $clientesConVisita = VisitaCobro::whereDate('created_at', $fechaCarbon)->pluck('cliente_id');
        $clientesAtendidos = $clientesConPago->merge($clientesConVisita)->unique();

        return Cliente::whereIn('ruta_cobro_id', $rutasIds)
            ->whereNotIn('id', $clientesAtendidos)
            ->whereHas('gestionesCobro', fn ($q) => $q->whereIn('estado', ['pendiente', 'parcialmente_cobrado']))
            ->count();
    }
}
