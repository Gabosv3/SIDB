<?php

namespace App\Http\Controllers;

use App\Models\AsignacionDiaria;
use App\Models\Garantia;
use App\Services\ClientesInactivosService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    /**
     * Reporte consolidado del día
     */
    public function reporteDiario($tenant)
    {
        $asignaciones = AsignacionDiaria::where('sucursal_id', $tenant)
            ->whereDate('fecha', today())
            ->with(['vendedor', 'sucursal', 'detalles.producto'])
            ->get();

        $pdf = Pdf::loadView('reporte-diario-pdf', [
            'asignaciones' => $asignaciones,
            'tenant' => $tenant,
            'fecha' => today(),
        ]);

        return $pdf->download('Reporte-Diario_' . today()->format('Y-m-d') . '.pdf');
    }

    /**
     * Reporte por vendedor
     */
    public function reporteVendedor($tenant, $vendedor)
    {
        $asignaciones = AsignacionDiaria::where('sucursal_id', $tenant)
            ->where('vendedor_id', $vendedor)
            ->whereDate('fecha', today())
            ->with(['vendedor', 'sucursal', 'detalles.producto'])
            ->get();

        $pdf = Pdf::loadView('reporte-vendedor-pdf', [
            'asignaciones' => $asignaciones,
            'tenant' => $tenant,
            'fecha' => today(),
        ]);

        $vendedorNombre = $asignaciones->first()?->vendedor->nombre ?? 'Vendedor';
        return $pdf->download("Reporte-Vendedor_{$vendedorNombre}_" . today()->format('Y-m-d') . '.pdf');
    }

    /**
     * Reporte de cortes liquidados
     */
    public function reporteLiquidados($tenant)
    {
        $asignaciones = AsignacionDiaria::where('sucursal_id', $tenant)
            ->where('estado', 'liquidada')
            ->whereDate('liquidada_at', today())
            ->with(['vendedor', 'sucursal', 'detalles.producto'])
            ->orderBy('liquidada_at', 'desc')
            ->get();

        $pdf = Pdf::loadView('reporte-liquidados-pdf', [
            'asignaciones' => $asignaciones,
            'tenant' => $tenant,
            'fecha' => today(),
        ]);

        return $pdf->download('Reporte-Liquidados_' . today()->format('Y-m-d') . '.pdf');
    }

    /**
     * Reporte imprimible de garantías de la semana (todas, sin importar
     * estado) -- para llevar en papel en la ruta de recolección/entrega.
     */
    public function reporteGarantiasSemana($tenant, Request $request)
    {
        $dentroDeLaSemana = $request->query('fecha') ? Carbon::parse($request->query('fecha')) : today();
        $inicio = $dentroDeLaSemana->copy()->startOfWeek(Carbon::MONDAY);
        $fin = $dentroDeLaSemana->copy()->endOfWeek(Carbon::SUNDAY);

        $garantias = Garantia::where('sucursal_id', $tenant)
            ->whereBetween('fecha_reporte', [$inicio->toDateString(), $fin->toDateString()])
            ->with(['cliente', 'cobrador', 'venta.detalles.producto'])
            ->orderBy('fecha_reporte')
            ->get();

        $pdf = Pdf::loadView('reporte-garantias-semana-pdf', [
            'garantias' => $garantias,
            'tenant' => $tenant,
            'inicio' => $inicio,
            'fin' => $fin,
        ]);

        return $pdf->stream('Garantias-Semana_' . $inicio->format('Y-m-d') . '.pdf');
    }

    /**
     * Listado imprimible de clientes con saldo pendiente que no han sido
     * visitados ni han abonado en el periodo indicado -- mismos filtros que
     * la página "Clientes Inactivos" del panel.
     */
    public function reporteClientesInactivos($tenant, Request $request)
    {
        $dias = (int) $request->query('dias', 30);
        $rutaId = $request->query('ruta_id') ? (int) $request->query('ruta_id') : null;
        $cobradorId = $request->query('cobrador_id') ? (int) $request->query('cobrador_id') : null;

        $filas = ClientesInactivosService::listar($dias, $rutaId, $cobradorId)
            ->filter(fn ($fila) => $fila['cliente']->sucursal_id == $tenant)
            ->values();

        $pdf = Pdf::loadView('reporte-clientes-inactivos-pdf', [
            'filas' => $filas,
            'dias' => $dias,
            'fecha' => today(),
        ]);

        return $pdf->stream('Clientes-Inactivos_' . today()->format('Y-m-d') . '.pdf');
    }
}
