<?php

namespace App\Http\Controllers;

use App\Models\Cobrador;
use App\Models\PagoVenta;
use App\Models\PosDevice;
use App\Models\VisitaCobro;
use App\Services\ResumenCobrosDiaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PosMonitorController extends Controller
{
    public function index(Request $request, $tenant)
    {
        $fecha      = $request->get('fecha') ?: today()->toDateString();
        $cobradorId = $request->get('cobrador_id') ?: null;
        $puedeLiberar = $request->user()?->can('Gestionar:DispositivosPos') ?? false;

        $devices = PosDevice::with(['cobrador', 'user'])->where('activo', true)->orderBy('nombre')->get();

        $ligeroHoy = ResumenCobrosDiaService::ligero(today()->toDateString());
        foreach ($devices as $d) {
            $datos = $d->user_id ? $ligeroHoy->get($d->user_id) : null;
            $d->cobrado_hoy = (float) ($datos->cobrado ?? 0);
            $d->pagos_hoy = (int) ($datos->pagos ?? 0);
            $d->clientes_hoy = (int) ($datos->clientes ?? 0);
        }

        $ventasHoy = (float) PagoVenta::whereDate('fecha_pago', today())->sum('monto');
        $ventasAyer = (float) PagoVenta::whereDate('fecha_pago', today()->subDay())->sum('monto');
        $cobrosDia = PagoVenta::whereDate('fecha_pago', today())->distinct('cliente_id')->count('cliente_id');
        $cobrosAyer = PagoVenta::whereDate('fecha_pago', today()->subDay())->distinct('cliente_id')->count('cliente_id');

        $conexionesPorHora = array_fill(0, 24, 0);
        foreach ($devices as $d) {
            if ($d->ultimo_ping && $d->ultimo_ping->isToday()) {
                $conexionesPorHora[$d->ultimo_ping->hour]++;
            }
        }

        $alertasCount = $devices->filter(fn ($d) => in_array($d->estado_calc, ['sin_conexion', 'bateria_baja', 'apagado']))->count();

        $resumen = ResumenCobrosDiaService::resumen($fecha, $cobradorId);
        $totalesResumen = ResumenCobrosDiaService::totales($resumen);
        $cobradores = Cobrador::where('activo', true)->where('excluir_reportes', false)->orderBy('nombre')->get();

        return view('pos.monitor', compact(
            'devices', 'ventasHoy', 'ventasAyer',
            'cobrosDia', 'cobrosAyer',
            'conexionesPorHora', 'alertasCount', 'tenant',
            'fecha', 'cobradorId', 'resumen', 'totalesResumen', 'cobradores',
            'puedeLiberar'
        ));
    }

    /**
     * Libera un dispositivo (le quita el usuario/cobrador vinculado) para que
     * otro empleado pueda tomarlo con su próximo login. La protección contra
     * secuestro en PosController::heartbeat() no deja que esto pase solo con
     * iniciar sesión — tiene que liberarse desde aquí primero.
     */
    public function liberarDispositivo(Request $request, $tenant, PosDevice $device)
    {
        $device->update([
            'user_id' => null,
            'cobrador_id' => null,
        ]);

        return response()->json([
            'ok' => true,
            'mensaje' => "Dispositivo \"{$device->nombre}\" liberado.",
        ]);
    }

    public function data(Request $request, $tenant)
    {
        $devices = PosDevice::with(['cobrador', 'user'])->where('activo', true)->orderBy('nombre')->get();
        $ligeroHoy = ResumenCobrosDiaService::ligero(today()->toDateString());

        $ventasHoy = (float) PagoVenta::whereDate('fecha_pago', today())->sum('monto');
        $ventasAyer = (float) PagoVenta::whereDate('fecha_pago', today()->subDay())->sum('monto');
        $cobrosDia = PagoVenta::whereDate('fecha_pago', today())->distinct('cliente_id')->count('cliente_id');
        $cobrosAyer = PagoVenta::whereDate('fecha_pago', today()->subDay())->distinct('cliente_id')->count('cliente_id');

        $conexionesPorHora = array_fill(0, 24, 0);
        foreach ($devices as $d) {
            if ($d->ultimo_ping && $d->ultimo_ping->isToday()) {
                $conexionesPorHora[$d->ultimo_ping->hour]++;
            }
        }

        $stats = [
            'activos' => $devices->filter(fn ($d) => $d->estado_calc === 'activo')->count(),
            'sin_conexion' => $devices->filter(fn ($d) => $d->estado_calc === 'sin_conexion')->count(),
            'bateria_baja' => $devices->filter(fn ($d) => $d->estado_calc === 'bateria_baja')->count(),
            'total' => $devices->count(),
            'apagados' => $devices->filter(fn ($d) => $d->estado_calc === 'apagado')->count(),
            'ventas_hoy' => $ventasHoy,
            'ventas_ayer' => $ventasAyer,
            'cobros_dia' => $cobrosDia,
            'cobros_ayer' => $cobrosAyer,
            'pct_ventas' => $ventasAyer > 0 ? round((($ventasHoy - $ventasAyer) / $ventasAyer) * 100, 1) : 0,
            'pct_cobros' => $cobrosAyer > 0 ? round((($cobrosDia - $cobrosAyer) / $cobrosAyer) * 100, 1) : 0,
        ];

        $devicesData = $devices->map(function ($d) use ($ligeroHoy) {
            $estado = $d->estado_calc;
            $bat = $d->bateria ?? 0;
            $datos = $d->user_id ? $ligeroHoy->get($d->user_id) : null;

            return [
                'id' => $d->id,
                'nombre' => $d->nombre,
                'serial' => $d->serial,
                'estado' => $estado,
                'ultimo_ping' => $d->ultimo_ping?->diffForHumans() ?? 'Nunca',
                'bateria' => $bat,
                'bateria_color' => $bat > 50 ? '#16a34a' : ($bat > 20 ? '#d97706' : '#dc2626'),
                'tiene_internet' => (bool) $d->tiene_internet,
                'latitud' => $d->latitud,
                'longitud' => $d->longitud,
                'usuario' => optional($d->user)->name,
                'user_id' => $d->user_id,
                'cobrador' => $d->cobrador ? $d->cobrador->nombre.' '.$d->cobrador->apellido : null,
                'app_version' => $d->app_version,
                'cobrado_hoy' => (float) ($datos->cobrado ?? 0),
                'pagos_hoy' => (int) ($datos->pagos ?? 0),
                'clientes_hoy' => (int) ($datos->clientes ?? 0),
            ];
        })->values();

        return response()->json([
            'stats' => $stats,
            'devices' => $devicesData,
            'conexiones_por_hora' => array_values($conexionesPorHora),
            'alertas_count' => $devices->filter(fn ($d) => in_array($d->estado_calc, ['sin_conexion', 'bateria_baja', 'apagado']))->count(),
            'timestamp' => now()->format('H:i:s'),
        ]);
    }

    public function resumen(Request $request, $tenant)
    {
        $fecha = $request->get('fecha') ?: today()->toDateString();
        $cobradorId = $request->get('cobrador_id') ? (int) $request->get('cobrador_id') : null;
        $ayer = Carbon::parse($fecha)->subDay()->toDateString();

        $resumen = ResumenCobrosDiaService::resumen($fecha, $cobradorId);
        $totalesResumen = ResumenCobrosDiaService::totales($resumen);
        $hoySimple = ResumenCobrosDiaService::totalesSimples($fecha, $cobradorId);
        $ayerSimple = ResumenCobrosDiaService::totalesSimples($ayer, $cobradorId);
        $general = ResumenCobrosDiaService::resumenGeneral($fecha, $cobradorId);
        $cobradores = Cobrador::where('activo', true)->where('excluir_reportes', false)->orderBy('nombre')->get();
        $actividad = $this->actividadReciente($fecha);

        // ── KPIs nuevos ──────────────────────────────────────────────────
        $totalVales = ResumenCobrosDiaService::totalValesDia($fecha, $cobradorId);
        $morosidad = ResumenCobrosDiaService::morosidad($cobradorId);
        $comparativoSemanal = ResumenCobrosDiaService::comparativoSemanal($fecha, $cobradorId);

        // ── Datos para gráficas ──────────────────────────────────────────
        $graficoTendencia = ResumenCobrosDiaService::tendenciaDiaria($fecha, 14, $cobradorId);
        $graficoCobradores = ResumenCobrosDiaService::comparacionCobradores($fecha);
        $graficoMetodos = ResumenCobrosDiaService::desglosePorMetodo($fecha, $cobradorId);

        return view('pos.resumen', compact(
            'tenant', 'fecha', 'cobradorId', 'cobradores',
            'resumen', 'totalesResumen', 'hoySimple', 'ayerSimple', 'general', 'actividad',
            'totalVales', 'morosidad', 'comparativoSemanal',
            'graficoTendencia', 'graficoCobradores', 'graficoMetodos'
        ));
    }

    private function actividadReciente(string $fecha, int $limit = 8): array
    {
        $fechaCarbon = Carbon::parse($fecha);

        $pagos = PagoVenta::whereDate('fecha_pago', $fechaCarbon)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($p) => [
                'color' => '#16a34a',
                'titulo' => 'Pago registrado',
                'detalle' => (optional($p->user)->name ?? 'Cobrador').' · $'.number_format((float) $p->monto, 2),
                'hora' => $p->created_at,
            ])
            ->toBase();

        $visitas = VisitaCobro::whereDate('created_at', $fechaCarbon)
            ->with('cliente:id,nombre,apellido')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($v) => [
                'color' => '#d97706',
                'titulo' => 'Cliente visitado sin cobro',
                'detalle' => $v->cliente?->nombre_completo ?? '—',
                'hora' => $v->created_at,
            ])
            ->toBase();

        $alertas = PosDevice::where('activo', true)
            ->whereNotNull('ultimo_ping')
            ->get()
            ->filter(fn ($d) => in_array($d->estado_calc, ['sin_conexion', 'bateria_baja', 'apagado']))
            ->map(fn ($d) => [
                'color' => '#dc2626',
                'titulo' => match ($d->estado_calc) {
                    'bateria_baja' => 'Alerta de batería baja',
                    'sin_conexion' => 'Dispositivo sin conexión',
                    default => 'Dispositivo apagado',
                },
                'detalle' => $d->nombre,
                'hora' => $d->ultimo_ping,
            ])
            ->toBase();

        return $pagos->merge($visitas)->merge($alertas)
            ->filter(fn ($a) => $a['hora'] !== null)
            ->sortByDesc('hora')
            ->take($limit)
            ->values()
            ->all();
    }
}
