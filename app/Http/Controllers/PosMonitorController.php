<?php

namespace App\Http\Controllers;

use App\Models\PagoVenta;
use App\Models\PosDevice;
use Illuminate\Http\Request;

class PosMonitorController extends Controller
{
    public function index(Request $request, $tenant)
    {
        $devices   = PosDevice::with(['cobrador', 'user'])->where('activo', true)->orderBy('nombre')->get();

        $ventasHoy  = (float) PagoVenta::whereDate('fecha_pago', today())->sum('monto');
        $ventasAyer = (float) PagoVenta::whereDate('fecha_pago', today()->subDay())->sum('monto');
        $cobrosDia  = PagoVenta::whereDate('fecha_pago', today())->distinct('cliente_id')->count('cliente_id');
        $cobrosAyer = PagoVenta::whereDate('fecha_pago', today()->subDay())->distinct('cliente_id')->count('cliente_id');

        $conexionesPorHora = array_fill(0, 24, 0);
        foreach ($devices as $d) {
            if ($d->ultimo_ping && $d->ultimo_ping->isToday()) {
                $conexionesPorHora[$d->ultimo_ping->hour]++;
            }
        }

        $alertasCount = $devices->filter(fn ($d) => in_array($d->estado_calc, ['sin_conexion', 'bateria_baja', 'apagado']))->count();

        return view('pos.monitor', compact(
            'devices', 'ventasHoy', 'ventasAyer',
            'cobrosDia', 'cobrosAyer',
            'conexionesPorHora', 'alertasCount', 'tenant'
        ));
    }

    public function data(Request $request, $tenant)
    {
        $devices = PosDevice::with(['cobrador', 'user'])->where('activo', true)->orderBy('nombre')->get();

        $ventasHoy  = (float) PagoVenta::whereDate('fecha_pago', today())->sum('monto');
        $ventasAyer = (float) PagoVenta::whereDate('fecha_pago', today()->subDay())->sum('monto');
        $cobrosDia  = PagoVenta::whereDate('fecha_pago', today())->distinct('cliente_id')->count('cliente_id');
        $cobrosAyer = PagoVenta::whereDate('fecha_pago', today()->subDay())->distinct('cliente_id')->count('cliente_id');

        $conexionesPorHora = array_fill(0, 24, 0);
        foreach ($devices as $d) {
            if ($d->ultimo_ping && $d->ultimo_ping->isToday()) {
                $conexionesPorHora[$d->ultimo_ping->hour]++;
            }
        }

        $stats = [
            'activos'      => $devices->filter(fn ($d) => $d->estado_calc === 'activo')->count(),
            'sin_conexion' => $devices->filter(fn ($d) => $d->estado_calc === 'sin_conexion')->count(),
            'bateria_baja' => $devices->filter(fn ($d) => $d->estado_calc === 'bateria_baja')->count(),
            'total'        => $devices->count(),
            'apagados'     => $devices->filter(fn ($d) => $d->estado_calc === 'apagado')->count(),
            'ventas_hoy'   => $ventasHoy,
            'ventas_ayer'  => $ventasAyer,
            'cobros_dia'   => $cobrosDia,
            'cobros_ayer'  => $cobrosAyer,
            'pct_ventas'   => $ventasAyer > 0 ? round((($ventasHoy - $ventasAyer) / $ventasAyer) * 100, 1) : 0,
            'pct_cobros'   => $cobrosAyer > 0 ? round((($cobrosDia - $cobrosAyer) / $cobrosAyer) * 100, 1) : 0,
        ];

        $devicesData = $devices->map(function ($d) {
            $estado = $d->estado_calc;
            $bat    = $d->bateria ?? 0;
            return [
                'id'           => $d->id,
                'nombre'       => $d->nombre,
                'serial'       => $d->serial,
                'estado'       => $estado,
                'ultimo_ping'  => $d->ultimo_ping?->diffForHumans() ?? 'Nunca',
                'bateria'      => $bat,
                'bateria_color'=> $bat > 50 ? '#16a34a' : ($bat > 20 ? '#d97706' : '#dc2626'),
                'tiene_internet' => (bool) $d->tiene_internet,
                'latitud'      => $d->latitud,
                'longitud'     => $d->longitud,
                'usuario'      => optional($d->user)->name,
                'cobrador'     => $d->cobrador ? $d->cobrador->nombre . ' ' . $d->cobrador->apellido : null,
                'app_version'  => $d->app_version,
            ];
        })->values();

        return response()->json([
            'stats'              => $stats,
            'devices'            => $devicesData,
            'conexiones_por_hora' => array_values($conexionesPorHora),
            'alertas_count'      => $devices->filter(fn ($d) => in_array($d->estado_calc, ['sin_conexion', 'bateria_baja', 'apagado']))->count(),
            'timestamp'          => now()->format('H:i:s'),
        ]);
    }
}
