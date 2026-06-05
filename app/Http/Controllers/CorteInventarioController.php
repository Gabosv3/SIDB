<?php

namespace App\Http\Controllers;

use App\Models\AsignacionDiaria;
use Illuminate\Routing\Controller;
use Barryvdh\DomPDF\Facade\Pdf;

class CorteInventarioController extends Controller
{
    public function mostrar($tenant, AsignacionDiaria $asignacion)
    {
        // Validar que la asignación esté liquidada
        if (!$asignacion->liquidada_at) {
            abort(403, 'Esta asignación aún no ha sido liquidada.');
        }

        return view('corte-inventario', [
            'tenant' => $tenant,
            'asignacion' => $asignacion,
        ]);
    }

    public function generarPdf($tenant, AsignacionDiaria $asignacion)
    {
        // Validar que la asignación esté liquidada
        if (!$asignacion->liquidada_at) {
            abort(403, 'Esta asignación aún no ha sido liquidada.');
        }

        $pdf = Pdf::loadView('corte-inventario-pdf', [
            'asignacion' => $asignacion,
            'tenant' => $tenant,
        ]);

        $filename = sprintf(
            'Corte-Inventario_%s_%s_%s.pdf',
            $asignacion->vendedor->nombre,
            $asignacion->fecha->format('Y-m-d'),
            now()->format('His')
        );

        return $pdf->download($filename);
    }
}
