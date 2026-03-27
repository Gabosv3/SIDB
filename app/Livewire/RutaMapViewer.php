<?php

namespace App\Livewire;

use App\Models\RutaCobro;
use Livewire\Component;

class RutaMapViewer extends Component
{
    public RutaCobro $ruta;

    public function mount(RutaCobro $ruta)
    {
        $this->ruta = $ruta;
    }

    public function render()
    {
        return view('livewire.ruta-map-viewer', [
            'clientes' => $this->ruta->clientes()->get(),
        ]);
    }
}
