<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\View;

class ProductSearchGrid extends View
{
    public static function make(string $name = 'productSearchGrid'): static
    {
        return parent::make($name)->view('filament.forms.components.product-search-grid');
    }
}
