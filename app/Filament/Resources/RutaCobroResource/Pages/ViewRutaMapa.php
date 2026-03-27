<?php

namespace App\Filament\Resources\RutaCobroResource\Pages;

use App\Filament\Resources\RutaCobroResource;
use App\Models\RutaCobro;
use Filament\Resources\Pages\Page;

class ViewRutaMapa extends Page
{
    protected static string $resource = RutaCobroResource::class;

    protected string $view = 'filament.pages.ruta-mapa';

    public RutaCobro $record;
}
