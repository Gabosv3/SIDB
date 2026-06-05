<?php

namespace App\Http\Controllers;

use App\Models\AsignacionDiaria;
use Illuminate\Routing\Controller;
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
}
