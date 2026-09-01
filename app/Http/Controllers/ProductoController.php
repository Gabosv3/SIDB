<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionSistema;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * Hoja de conteo físico de inventario: todos los productos activos con
     * su stock actual del sistema, agrupados por categoría, con una columna
     * en blanco para anotar el conteo real al hacer inventario a mano. No
     * actualiza nada — es solo para imprimir y comparar contra el sistema
     * después de contar.
     */
    public function generarConteoInventario(Request $request, $tenant)
    {
        $categoriaIds = array_filter(array_map('intval', (array) $request->get('categoria_id', [])));

        $productos = Producto::where('activo', true)
            ->when(! empty($categoriaIds), fn ($q) => $q->whereIn('categoria_id', $categoriaIds))
            ->with('categoria:id,nombre')
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'stock', 'categoria_id']);

        $porCategoria = $productos->groupBy(fn (Producto $p) => $p->categoria?->nombre ?? 'Sin categoría')
            ->sortKeys();

        $pdf = Pdf::loadView('productos.conteo-inventario-pdf', [
            'porCategoria' => $porCategoria,
            'totalProductos' => $productos->count(),
            'config' => ConfiguracionSistema::instance(),
            'fechaEmision' => now(),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('Conteo-de-inventario-'.now()->format('Y-m-d').'.pdf');
    }
}
