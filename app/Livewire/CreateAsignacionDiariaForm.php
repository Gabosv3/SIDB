<?php

namespace App\Livewire;

use App\Models\AsignacionDiaria;
use App\Models\Categoria;
use App\Models\DetalleAsignacion;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Vendedor;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CreateAsignacionDiariaForm extends Component
{
    #[Validate('required|exists:vendedores,id')]
    public $vendedor_id = null;

    public $sucursal_id = null;

    #[Validate('required|date')]
    public $fecha;

    public $observaciones = '';

    public $search = '';

    public $categoria_id = null;

    public $productos = [];

    public $detalles = [];

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

    public function updatedSearch()
    {
        $this->loadProductos();
    }

    public function updatedCategoriaId()
    {
        $this->loadProductos();
    }

    public function loadProductos()
    {
        $query = Producto::where('activo', true);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nombre', 'like', "%{$this->search}%")
                  ->orWhere('codigo', 'like', "%{$this->search}%");
            });
        }

        if ($this->categoria_id) {
            $query->where('categoria_id', $this->categoria_id);
        }

        $this->productos = $query->orderBy('nombre')->get()->toArray();
    }

    public function agregarProducto($productoId)
    {
        $producto = Producto::find($productoId);
        if (!$producto) return;

        $key = "producto_{$productoId}";
        if (!isset($this->detalles[$key])) {
            $this->detalles[$key] = [
                'producto_id'      => $productoId,
                'producto'         => $producto,
                'cantidad_asignada'=> 1,
                'precio_venta'     => $producto->precio_venta,
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

    public function getTotalAsignacion()
    {
        return collect($this->detalles)->sum(function ($detalle) {
            return $detalle['cantidad_asignada'] * $detalle['precio_venta'];
        });
    }

    public function save()
    {
        $this->validate();

        if (empty($this->detalles)) {
            $this->addError('detalles', 'Debes agregar al menos un producto');
            return;
        }

        $asignacion = AsignacionDiaria::create([
            'vendedor_id'  => $this->vendedor_id,
            'sucursal_id'  => $this->sucursal_id,
            'fecha'        => $this->fecha,
            'observaciones'=> $this->observaciones,
            'estado'       => 'activa',
        ]);

        foreach ($this->detalles as $detalle) {
            DetalleAsignacion::create([
                'asignacion_id'     => $asignacion->id,
                'producto_id'       => $detalle['producto_id'],
                'cantidad_asignada' => $detalle['cantidad_asignada'],
                'precio_venta'      => $detalle['precio_venta'],
            ]);
        }

        return $this->redirect(route('filament.admin.resources.asignaciones-diarias.view', ['record' => $asignacion->id]), navigate: true);
    }

    public function render()
    {
        return view('livewire.create-asignacion-diaria-form', [
            'vendedores'  => Vendedor::where('activo', true)->whereNotNull('user_id')->orderBy('nombre')->get(),
            'sucursales'  => Sucursal::orderBy('nombre')->get(),
            'categorias'  => Categoria::orderBy('nombre')->get(),
            'totalAsignacion' => $this->getTotalAsignacion(),
            'totalUnidades' => collect($this->detalles)->sum('cantidad_asignada'),
        ]);
    }
}
