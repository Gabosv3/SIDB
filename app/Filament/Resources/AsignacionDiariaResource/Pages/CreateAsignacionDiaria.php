<?php

namespace App\Filament\Resources\AsignacionDiariaResource\Pages;

use App\Filament\Resources\AsignacionDiariaResource;
use Filament\Resources\Pages\Page;

class CreateAsignacionDiaria extends Page
{
    protected static string $resource = AsignacionDiariaResource::class;

    protected static string $view = 'filament.resources.asignacion-diaria-resource.pages.create-asignacion-diaria';
}
