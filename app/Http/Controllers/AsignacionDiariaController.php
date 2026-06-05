<?php

namespace App\Http\Controllers;

use App\Models\AsignacionDiaria;
use App\Models\Categoria;
use App\Models\DetalleAsignacion;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Vendedor;
use Illuminate\Http\Request;

class AsignacionDiariaController extends Controller
{
    public function crear()
    {
        return view('asignacion-diaria.crear', [
            'vendedores' => Vendedor::where('activo', true)->whereNotNull('user_id')->orderBy('nombre')->get(),
            'sucursales' => Sucursal::orderBy('nombre')->get(),
            'categorias' => Categoria::orderBy('nombre')->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->paginate(12),
        ]);
    }

    public function editar(AsignacionDiaria $asignacion)
    {
        return view('asignacion-diaria.editar', [
            'asignacion' => $asignacion,
            'vendedores' => Vendedor::where('activo', true)->whereNotNull('user_id')->orderBy('nombre')->get(),
            'sucursales' => Sucursal::orderBy('nombre')->get(),
            'categorias' => Categoria::orderBy('nombre')->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->paginate(12),
        ]);
    }

    public function guardar(Request $request)
    {
        $validated = $request->validate([
            'vendedor_id' => 'required|exists:vendedores,id',
            'sucursal_id' => 'required|exists:sucursales,id',
            'fecha' => 'required|date',
            'detalles' => 'required|array',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad_asignada' => 'required|numeric|min:1',
            'detalles.*.precio_venta' => 'required|numeric',
        ]);

        $asignacion = AsignacionDiaria::create([
            'vendedor_id' => $validated['vendedor_id'],
            'sucursal_id' => $validated['sucursal_id'],
            'fecha' => $validated['fecha'],
            'observaciones' => $request->observaciones ?? '',
            'estado' => 'activa',
        ]);

        foreach ($validated['detalles'] as $detalle) {
            DetalleAsignacion::create([
                'asignacion_id' => $asignacion->id,
                'producto_id' => $detalle['producto_id'],
                'cantidad_asignada' => $detalle['cantidad_asignada'],
                'precio_venta' => $detalle['precio_venta'],
            ]);
        }

        return redirect()->route('filament.administrativo.resources.asignaciones-diarias.view', [
            'tenant' => auth()->user()->sucursales()->first()->id,
            'record' => $asignacion->id,
        ])->with('success', 'Asignación creada correctamente');
    }

    public function actualizar(Request $request, AsignacionDiaria $asignacion)
    {
        $validated = $request->validate([
            'vendedor_id' => 'required|exists:vendedores,id',
            'sucursal_id' => 'required|exists:sucursales,id',
            'fecha' => 'required|date',
            'detalles' => 'required|array',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad_asignada' => 'required|numeric|min:1',
            'detalles.*.precio_venta' => 'required|numeric',
        ]);

        $asignacion->update([
            'vendedor_id' => $validated['vendedor_id'],
            'sucursal_id' => $validated['sucursal_id'],
            'fecha' => $validated['fecha'],
            'observaciones' => $request->observaciones ?? '',
        ]);

        $asignacion->detalles()->delete();
        foreach ($validated['detalles'] as $detalle) {
            DetalleAsignacion::create([
                'asignacion_id' => $asignacion->id,
                'producto_id' => $detalle['producto_id'],
                'cantidad_asignada' => $detalle['cantidad_asignada'],
                'precio_venta' => $detalle['precio_venta'],
            ]);
        }

        return redirect()->route('filament.administrativo.resources.asignaciones-diarias.view', [
            'tenant' => auth()->user()->sucursales()->first()->id,
            'record' => $asignacion->id,
        ])->with('success', 'Asignación actualizada correctamente');
    }
}
