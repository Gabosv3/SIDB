<?php

namespace App\Filament\Forms\Components;

use App\Models\Categoria;
use App\Models\Producto;
use Filament\Forms\Components\Component;
use Filament\Forms\Get;
use Filament\Forms\Set;

class ProductSearchGrid extends Component
{
    protected string $view = 'filament.forms.components.product-search-grid';

    public function getChildComponents(): array
    {
        return [];
    }
}
