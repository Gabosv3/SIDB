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
    public function crear($tenant)
    {
        // Obtener todos los vendedores con sus asignaciones de hoy
        $vendedores = Vendedor::where('activo', true)
            ->whereNotNull('user_id')
            ->orderBy('nombre')
            ->get()
            ->map(function ($v) {
                $tieneAsignacionHoy = AsignacionDiaria::where('vendedor_id', $v->id)
                    ->whereDate('fecha', today())
                    ->where('estado', 'activa')
                    ->exists();
                $v->tiene_asignacion_hoy = $tieneAsignacionHoy;
                return $v;
            });

        return view('asignacion-diaria.crear', [
            'tenant' => $tenant,
            'vendedores' => $vendedores,
            'sucursales' => Sucursal::orderBy('nombre')->get(),
            'categorias' => Categoria::orderBy('nombre')->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->paginate(12),
        ]);
    }

    public function editar($tenant, AsignacionDiaria $asignacion)
    {
        $detalles = $asignacion->detalles->map(function($d) {
            return [
                'producto_id' => $d->producto_id,
                'nombre' => $d->producto->nombre,
                'codigo' => $d->producto->codigo,
                'imagen' => $d->producto->imagen,
                'cantidad_asignada' => (int)$d->cantidad_asignada,
                'cantidad_vendida' => (int)($d->cantidad_vendida ?? 0),
                'precio_venta' => (float)$d->precio_venta,
                'stock_disponible' => (int)$d->producto->stock
            ];
        })->values();

        return view('asignacion-diaria.editar', [
            'tenant' => $tenant,
            'asignacion' => $asignacion,
            'detallesJson' => json_encode($detalles),
            'vendedores' => Vendedor::where('activo', true)->whereNotNull('user_id')->orderBy('nombre')->get(),
            'sucursales' => Sucursal::orderBy('nombre')->get(),
            'categorias' => Categoria::orderBy('nombre')->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->paginate(12),
        ]);
    }

    public function guardar(Request $request, $tenant)
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

        // Validación: No puede haber dos asignaciones del mismo vendedor en el mismo día
        $vendedorYaTieneAsignacionHoy = AsignacionDiaria::where('vendedor_id', $validated['vendedor_id'])
            ->whereDate('fecha', $validated['fecha'])
            ->where('estado', 'activa')
            ->exists();

        if ($vendedorYaTieneAsignacionHoy) {
            return redirect()->back()->with('error', 'Este vendedor ya tiene una asignación activa para esta fecha.');
        }

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
            'tenant' => $tenant,
            'record' => $asignacion->id,
        ])->with('success', 'Asignación creada correctamente');
    }

    public function actualizar(Request $request, $tenant, AsignacionDiaria $asignacion)
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

        // Validación: No puede reducir cantidad_asignada si ya se vendió
        foreach ($validated['detalles'] as $detalle) {
            $detalleActual = $asignacion->detalles()
                ->where('producto_id', $detalle['producto_id'])
                ->first();

            if ($detalleActual && $detalleActual->cantidad_vendida > 0) {
                if ($detalle['cantidad_asignada'] < $detalleActual->cantidad_vendida) {
                    return redirect()->back()->with('error',
                        "No puedes reducir la cantidad asignada del producto '{$detalleActual->producto->nombre}' porque ya se vendieron {$detalleActual->cantidad_vendida} unidades.");
                }
            }
        }

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
            'tenant' => $tenant,
            'record' => $asignacion->id,
        ])->with('success', 'Asignación actualizada correctamente');
    }
}
