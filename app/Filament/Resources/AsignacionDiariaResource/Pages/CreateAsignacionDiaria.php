<?php

namespace App\Filament\Resources\AsignacionDiariaResource\Pages;

use App\Filament\Resources\AsignacionDiariaResource;
use App\Models\AsignacionDiaria;
use App\Models\Categoria;
use App\Models\DetalleAsignacion;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Vendedor;
use Filament\Resources\Pages\Page;

class CreateAsignacionDiaria extends Page
{
    protected static string $resource = AsignacionDiariaResource::class;

    protected string $view = 'filament.resources.asignacion-diaria-resource.pages.create-asignacion-diaria';

    public $vendedor_id;
    public $sucursal_id;
    public $fecha;
    public $observaciones = '';
    public $detalles = [];
    public $categoria_id;
    public $search = '';

    public function mount()
    {
        $this->fecha = today()->format('Y-m-d');
    }

    public function updatedVendedorId()
    {
        $vendedor = Vendedor::find($this->vendedor_id);
        if ($vendedor?->sucursal_id) {
            $this->sucursal_id = $vendedor->sucursal_id;
        }
    }

    public function agregarProducto($productoId)
    {
        $producto = Producto::find($productoId);
        if (!$producto) return;

        $key = "producto_{$productoId}";
        if (!isset($this->detalles[$key])) {
            $this->detalles[$key] = [
                'producto_id'       => $productoId,
                'producto'          => $producto,
                'cantidad_asignada' => 1,
                'precio_venta'      => $producto->precio_venta,
            ];
        } else {
            $this->detalles[$key]['cantidad_asignada']++;
        }
    }

    public function incrementarProducto($key)
    {
        if (isset($this->detalles[$key])) {
            $this->detalles[$key]['cantidad_asignada']++;
        }
    }

    public function decrementarProducto($key)
    {
        if (isset($this->detalles[$key])) {
            $this->detalles[$key]['cantidad_asignada']--;
            if ($this->detalles[$key]['cantidad_asignada'] <= 0) {
                unset($this->detalles[$key]);
            }
        }
    }

    public function removerProducto($key)
    {
        unset($this->detalles[$key]);
    }

    public function save()
    {
        if (!$this->vendedor_id || !$this->sucursal_id || !$this->fecha) {
            session()->flash('error', 'Debe llenar todos los campos requeridos');
            return;
        }

        if (empty($this->detalles)) {
            session()->flash('error', 'Debe agregar al menos un producto');
            return;
        }

        $asignacion = AsignacionDiaria::create([
            'vendedor_id'   => $this->vendedor_id,
            'sucursal_id'   => $this->sucursal_id,
            'fecha'         => $this->fecha,
            'observaciones' => $this->observaciones,
            'estado'        => 'activa',
        ]);

        foreach ($this->detalles as $detalle) {
            DetalleAsignacion::create([
                'asignacion_id'     => $asignacion->id,
                'producto_id'       => $detalle['producto_id'],
                'cantidad_asignada' => $detalle['cantidad_asignada'],
                'precio_venta'      => $detalle['precio_venta'],
            ]);
        }

        return redirect()->route('filament.administrativo.resources.asignaciones-diarias.view', ['record' => $asignacion->id]);
    }

    public function getViewData(): array
    {
        return [
            'vendedores'  => Vendedor::where('activo', true)->whereNotNull('user_id')->orderBy('nombre')->get(),
            'sucursales'  => Sucursal::orderBy('nombre')->get(),
            'categorias'  => Categoria::orderBy('nombre')->get(),
            'productos'   => Producto::where('activo', true)->orderBy('nombre')->get(),
        ];
    }
}
