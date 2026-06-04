<?php

namespace App\Filament\Resources\AsignacionDiariaResource\Pages;

use App\Filament\Resources\AsignacionDiariaResource;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\View\View;

class CreateAsignacionDiaria extends Page
{
    protected static string $resource = AsignacionDiariaResource::class;

    protected static ?string $title = 'Crear Asignación Diaria';

    public function render(): View
    {
        return view('filament.resources.asignacion-diaria-resource.pages.create-asignacion-diaria');
    }
}
